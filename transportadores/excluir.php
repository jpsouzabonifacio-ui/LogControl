
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
    try {
        $stmt = $pdo->prepare("DELETE FROM transportadores WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: index.php?sucesso=excluido');
        exit;

    } catch (PDOException $e) {
        $erro = 'Não foi possível excluir este transportador. Ele pode estar vinculado a um frete cadastrado.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Excluir Transportador - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-5">

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <h3 class="text-danger mb-3">
                Excluir Transportador
            </h3>

            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?= e($erro) ?>
                </div>
            <?php endif; ?>

            <p>
                Tem certeza de que deseja excluir este transportador?
            </p>

            <div class="alert alert-warning">

                <strong>Nome:</strong>
                <?= e($transportador['nome']) ?><br>

                <strong>Documento:</strong>
                <?= e($transportador['documento']) ?><br>

                <strong>Veículo:</strong>
                <?= e($transportador['tipo_veiculo']) ?><br>

                <strong>Placa:</strong>
                <?= e($transportador['placa']) ?>

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
