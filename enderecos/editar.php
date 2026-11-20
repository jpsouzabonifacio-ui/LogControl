
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

// Busca o endereço
$stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id = ?");
$stmt->execute([$id]);
$endereco = $stmt->fetch();

if (!$endereco) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacao = mb_strtoupper(trim($_POST['identificacao'] ?? ''), 'UTF-8');
    $destinatario = mb_strtoupper(trim($_POST['destinatario'] ?? ''), 'UTF-8');
    $cep = trim($_POST['cep'] ?? '');
    $rua = mb_strtoupper(trim($_POST['rua'] ?? ''), 'UTF-8');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = mb_strtoupper(trim($_POST['complemento'] ?? ''), 'UTF-8');
    $bairro = mb_strtoupper(trim($_POST['bairro'] ?? ''), 'UTF-8');
    $cidade = mb_strtoupper(trim($_POST['cidade'] ?? ''), 'UTF-8');
    $estado = mb_strtoupper(trim($_POST['estado'] ?? ''), 'UTF-8');
    $referencia = mb_strtoupper(trim($_POST['referencia'] ?? ''), 'UTF-8');

    if (
        $identificacao === '' ||
        $destinatario === '' ||
        $cep === '' ||
        $rua === '' ||
        $numero === '' ||
        $bairro === '' ||
        $cidade === '' ||
        $estado === ''
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!preg_match('/^[A-Z]{2}$/', $estado)) {
        $erro = 'Informe uma UF válida com duas letras.';
    } else {
        try {
            $sql = "UPDATE enderecos SET
                        identificacao = ?,
                        destinatario = ?,
                        cep = ?,
                        rua = ?,
                        numero = ?,
                        complemento = ?,
                        bairro = ?,
                        cidade = ?,
                        estado = ?,
                        referencia = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $identificacao,
                $destinatario,
                $cep,
                $rua,
                $numero,
                $complemento,
                $bairro,
                $cidade,
                $estado,
                $referencia,
                $id
            ]);

            header('Location: index.php?sucesso=editado');
            exit;

        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar o endereço.';
        }
    }

    // Mantém os valores digitados no formulário
    $endereco = array_merge($endereco, [
        'identificacao' => $identificacao,
        'destinatario' => $destinatario,
        'cep' => $cep,
        'rua' => $rua,
        'numero' => $numero,
        'complemento' => $complemento,
        'bairro' => $bairro,
        'cidade' => $cidade,
        'estado' => $estado,
        'referencia' => $referencia
    ]);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Endereço - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Editar Endereço</h2>
        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Identificação *</label>
                <input type="text" name="identificacao"
                    class="form-control" required
                    value="<?= e($endereco['identificacao']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Destinatário *</label>
                <input type="text" name="destinatario"
                    class="form-control" required
                    value="<?= e($endereco['destinatario']) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">CEP *</label>
                <input type="text" name="cep"
                    class="form-control" required
                    value="<?= e($endereco['cep']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Rua *</label>
                <input type="text" name="rua"
                    class="form-control" required
                    value="<?= e($endereco['rua']) ?>">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Número *</label>
                <input type="text" name="numero"
                    class="form-control" required
                    value="<?= e($endereco['numero']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Complemento</label>
                <input type="text" name="complemento"
                    class="form-control"
                    value="<?= e($endereco['complemento']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Bairro *</label>
                <input type="text" name="bairro"
                    class="form-control" required
                    value="<?= e($endereco['bairro']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Cidade *</label>
                <input type="text" name="cidade"
                    class="form-control" required
                    value="<?= e($endereco['cidade']) ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Estado (UF) *</label>
                <input type="text" name="estado"
                    class="form-control" maxlength="2" required
                    value="<?= e($endereco['estado']) ?>">
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Referência</label>
                <textarea name="referencia"
                    class="form-control" rows="3"><?= e($endereco['referencia']) ?></textarea>
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

</body>
</html>
