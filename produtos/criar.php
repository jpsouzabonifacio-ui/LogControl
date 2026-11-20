
<?php
require_once __DIR__ . '/../config/database.php';

$erros = [];

$nome = '';
$codigo = '';
$descricao = '';
$peso = '';
$unidade = 'Unidade';
$ativo = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $peso = str_replace(',', '.', trim($_POST['peso_kg'] ?? ''));
    $unidade = trim($_POST['unidade'] ?? '');
    $ativo = $_POST['ativo'] ?? '1';

    if ($nome === '') {
        $erros[] = 'Informe o nome do produto.';
    }

    if ($codigo === '') {
        $erros[] = 'Informe o código do produto.';
    }

    if (!is_numeric($peso) || (float) $peso < 0) {
        $erros[] = 'Informe um peso válido.';
    }

    if (!in_array($unidade, ['Unidade', 'Caixa', 'Kg', 'Litro', 'Metro'], true)) {
        $erros[] = 'Selecione uma unidade válida.';
    }

    if (!in_array($ativo, ['0', '1'], true)) {
        $erros[] = 'Selecione um status válido.';
    }

    if (empty($erros)) {

        try {
            $sql = "
                INSERT INTO produtos
                (nome, codigo, descricao, peso_kg, unidade, ativo)
                VALUES
                (:nome, :codigo, :descricao, :peso, :unidade, :ativo)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nome' => $nome,
                ':codigo' => $codigo,
                ':descricao' => $descricao,
                ':peso' => $peso,
                ':unidade' => $unidade,
                ':ativo' => (int) $ativo
            ]);

            header('Location: index.php?sucesso=criado');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros[] = 'Este código já está cadastrado.';
            } else {
                $erros[] = 'Não foi possível cadastrar o produto.';
            }
        }
    }
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo Produto | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Novo produto</h2>
        <p class="text-muted">Preencha os dados para cadastrar um produto.</p>
    </div>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nome do produto *</label>
                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            maxlength="150"
                            value="<?= e($nome) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Código *</label>
                        <input
                            type="text"
                            name="codigo"
                            class="form-control"
                            maxlength="50"
                            value="<?= e($codigo) ?>"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea
                            name="descricao"
                            class="form-control"
                            rows="3"
                        ><?= e($descricao) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Peso (kg) *</label>
                        <input
                            type="number"
                            name="peso_kg"
                            class="form-control"
                            min="0"
                            step="0.001"
                            value="<?= e($peso) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Unidade *</label>
                        <select name="unidade" class="form-select" required>
                            <?php foreach (['Unidade', 'Caixa', 'Kg', 'Litro', 'Metro'] as $opcao): ?>
                                <option
                                    value="<?= e($opcao) ?>"
                                    <?= $unidade === $opcao ? 'selected' : '' ?>
                                >
                                    <?= e($opcao) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="ativo" class="form-select" required>
                            <option value="1" <?= $ativo === '1' ? 'selected' : '' ?>>
                                Ativo
                            </option>
                            <option value="0" <?= $ativo === '0' ? 'selected' : '' ?>>
                                Inativo
                            </option>
                        </select>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar produto
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
