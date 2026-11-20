
<?php
require_once __DIR__ . '/../config/database.php';

$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT *
    FROM enderecos
    WHERE identificacao LIKE :busca
       OR destinatario LIKE :busca
       OR cep LIKE :busca
       OR cidade LIKE :busca
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':busca' => "%{$busca}%"
]);

$enderecos = $stmt->fetchAll();

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Endereços | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Endereços</h2>
            <p class="text-muted mb-0">
                Gerencie os endereços de origem e destino.
            </p>
        </div>

        <a href="criar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Novo endereço
        </a>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">

                <div class="col-md-10">

                    <input
                        type="text"
                        name="busca"
                        class="form-control"
                        placeholder="Pesquisar por destinatário, cidade ou CEP..."
                        value="<?= e($busca) ?>"
                    >

                </div>

                <div class="col-md-2 d-grid">

                    <button class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i>
                        Pesquisar
                    </button>

                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>
                            <th>Identificação</th>
                            <th>Destinatário</th>
                            <th>Endereço</th>
                            <th>Cidade/UF</th>
                            <th>CEP</th>
                            <th class="text-end">Ações</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($enderecos) > 0): ?>

                            <?php foreach ($enderecos as $endereco): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= e($endereco['identificacao']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e($endereco['destinatario']) ?>
                                    </td>

                                    <td>
                                        <?= e($endereco['rua']) ?>,
                                        <?= e($endereco['numero']) ?>
                                    </td>

                                    <td>
                                        <?= e($endereco['cidade']) ?>/<?= e($endereco['estado']) ?>
                                    </td>

                                    <td>
                                        <?= e($endereco['cep']) ?>
                                    </td>

                                    <td class="text-end">

                                        <a
                                            href="editar.php?id=<?= (int) $endereco['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Editar"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <a
                                            href="excluir.php?id=<?= (int) $endereco['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Excluir"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6" class="text-center py-4 text-muted">
                                    Nenhum endereço encontrado.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small">
                Total encontrado: <?= count($enderecos) ?> endereço(s)
            </div>

        </div>

    </div>

    <div class="mt-3">

        <a href="../index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Voltar ao painel
        </a>

    </div>

</div>

</body>
</html>
