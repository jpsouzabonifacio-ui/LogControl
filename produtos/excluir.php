
<?php
require_once __DIR__ . '/../config/database.php';

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
$stmt->execute([':id' => $id]);

$produto = $stmt->fetch();

if (!$produto) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['confirmar'] ?? '') !== 'sim') {
        header('Location: index.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        header('Location: index.php?sucesso=excluido');
        exit;

    } catch (PDOException $e) {
        $erro = 'Não foi possível excluir o produto. Ele pode estar vinculado a um frete.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Excluir Produto | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm mx-auto" style="max-width: 600px;">

        <div class="card-body p-4">

            <div class="text-center mb-4">
                <i class="bi bi-exclamation-triangle text-danger display-3"></i>

                <h2 class="fw-bold mt-3">Excluir produto?</h2>

                <p class="text-muted">
                    Você está prestes a excluir o seguinte produto:
                </p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?= e($erro) ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning">
                <strong><?= e($produto['nome']) ?></strong><br>
                Código: <?= e($produto['codigo']) ?><br>
                Peso: <?= number_format((float) $produto['peso_kg'], 2, ',', '.') ?> kg
            </div>

            <p class="text-center text-muted">
                Esta ação não poderá ser desfeita.
            </p>

            <form method="POST" class="d-flex justify-content-center gap-2">

                <input type="hidden" name="confirmar" value="sim">

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>
                    Confirmar exclusão
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>
