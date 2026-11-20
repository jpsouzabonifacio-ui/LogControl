
<?php
require_once __DIR__ . '/../config/database.php';

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// Buscar transportador
$stmt = $pdo->prepare("SELECT * FROM transportadores WHERE id = ?");
$stmt->execute([$id]);
$transportador = $stmt->fetch();

if (!$transportador) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = mb_strtoupper(trim($_POST['nome'] ?? ''), 'UTF-8');
    $tipo = $_POST['tipo'] ?? '';
    $documento = preg_replace('/[^0-9]/', '', $_POST['documento'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo_veiculo = mb_strtoupper(trim($_POST['tipo_veiculo'] ?? ''), 'UTF-8');
    $placa = mb_strtoupper(trim($_POST['placa'] ?? ''), 'UTF-8');
    $capacidade_kg = str_replace(',', '.', $_POST['capacidade_kg'] ?? '0');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (
        $nome === '' ||
        !in_array($tipo, ['PF', 'PJ'], true) ||
        $documento === '' ||
        $telefone === '' ||
        $tipo_veiculo === '' ||
        $placa === '' ||
        !is_numeric($capacidade_kg) ||
        (float)$capacidade_kg <= 0
    ) {
        $erro = 'Preencha corretamente todos os campos obrigatórios.';

    } elseif (
        ($tipo === 'PF' && strlen($documento) !== 11) ||
        ($tipo === 'PJ' && strlen($documento) !== 14)
    ) {
        $erro = 'CPF deve ter 11 números e CNPJ deve ter 14 números.';

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';

    } else {
        try {
            $sql = "UPDATE transportadores SET
                        nome = ?,
                        tipo = ?,
                        documento = ?,
                        telefone = ?,
                        email = ?,
                        tipo_veiculo = ?,
                        placa = ?,
                        capacidade_kg = ?,
                        ativo = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nome,
                $tipo,
                $documento,
                $telefone,
                $email !== '' ? $email : null,
                $tipo_veiculo,
                $placa,
                $capacidade_kg,
                $ativo,
                $id
            ]);

            header('Location: index.php?sucesso=editado');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Este documento já está cadastrado para outro transportador.';
            } else {
                $erro = 'Erro ao atualizar o transportador.';
            }
        }
    }

    // Manter os dados preenchidos em caso de erro
    $transportador = array_merge($transportador, [
        'nome' => $nome,
        'tipo' => $tipo,
        'documento' => $documento,
        'telefone' => $telefone,
        'email' => $email,
        'tipo_veiculo' => $tipo_veiculo,
        'placa' => $placa,
        'capacidade_kg' => $capacidade_kg,
        'ativo' => $ativo
    ]);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Editar Transportador - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Editar Transportador</h2>

        <a href="index.php" class="btn btn-secondary">
            Voltar
        </a>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <form method="POST">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Pessoa *</label>

                        <select name="tipo" class="form-select" required>
                            <option value="PF" <?= $transportador['tipo'] === 'PF' ? 'selected' : '' ?>>
                                Pessoa Física
                            </option>

                            <option value="PJ" <?= $transportador['tipo'] === 'PJ' ? 'selected' : '' ?>>
                                Pessoa Jurídica
                            </option>
                        </select>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nome / Razão Social *</label>

                        <input type="text" name="nome"
                            class="form-control" required
                            value="<?= e($transportador['nome']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">CPF / CNPJ *</label>

                        <input type="text" name="documento"
                            class="form-control"
                            maxlength="14"
                            inputmode="numeric"
                            pattern="[0-9]{11}|[0-9]{14}"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            required
                            value="<?= e($transportador['documento']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefone *</label>

                        <input type="text" name="telefone"
                            class="form-control" required
                            value="<?= e($transportador['telefone']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-mail</label>

                        <input type="email" name="email"
                            class="form-control"
                            value="<?= e($transportador['email']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Veículo *</label>

                        <select name="tipo_veiculo" class="form-select" required>
                            <?php
                            $veiculos = [
                                'CAMINHÃO',
                                'CARRETA',
                                'VAN',
                                'UTILITÁRIO',
                                'MOTO',
                                'OUTRO'
                            ];
                            ?>

                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?= e($veiculo) ?>"
                                    <?= $transportador['tipo_veiculo'] === $veiculo ? 'selected' : '' ?>>
                                    <?= e($veiculo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Placa *</label>

                        <input type="text" name="placa"
                            class="form-control" maxlength="8" required
                            value="<?= e($transportador['placa']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capacidade de Carga (kg) *</label>

                        <input type="number" name="capacidade_kg"
                            class="form-control" min="0.01" step="0.01" required
                            value="<?= e($transportador['capacidade_kg']) ?>">
                    </div>

                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="ativo"
                                class="form-check-input" id="ativo"
                                <?= $transportador['ativo'] ? 'checked' : '' ?>>

                            <label class="form-check-label" for="ativo">
                                Transportador ativo
                            </label>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">
                    Salvar Alterações
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Cancelar
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
