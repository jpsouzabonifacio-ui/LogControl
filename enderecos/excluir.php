
<?php
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id = ?");
$stmt->execute([$id]);
$endereco = $stmt->fetch();

if (!$endereco) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM enderecos WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: index.php?sucesso=excluido');
        exit;

    } catch (PDOException $e) {
        $erro = 'Não foi possível excluir este endereço. Ele pode estar vinculado a um frete cadastrado.';
    }
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Excluir Endereço - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-5">

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <h3 class="text-danger mb-3">Excluir Endereço</h3>

            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?= e($erro) ?>
                </div>
            <?php endif; ?>

            <p>Tem certeza de que deseja excluir este endereço?</p>

            <div class="alert alert-warning">
                <strong>Identificação:</strong>
                <?= e($endereco['identificacao']) ?><br>

                <strong>Destinatário:</strong>
                <?= e($endereco['destinatario']) ?><br>

                <strong>Cidade:</strong>
                <?= e($endereco['cidade']) ?> -
                <?= e($endereco['estado']) ?>
            </div>

            <form method="POST">
                <button type="submit" class="btn btn-danger">
                    Sim, excluir
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
