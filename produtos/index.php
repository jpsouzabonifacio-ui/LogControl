
<?php
require_once __DIR__ . '/../config/database.php';

// Pesquisa de produtos
$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT *
    FROM produtos
    WHERE nome LIKE :busca
       OR codigo LIKE :busca
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':busca' => "%{$busca}%"
]);

$produtos = $stmt->fetchAll();

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Produtos</h2>
            <p class="text-muted mb-0">Gerencie os produtos cadastrados.</p>
        </div>

        <a href="criar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Novo produto
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
                        placeholder="Pesquisar por nome ou código..."
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
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Peso</th>
                            <th>Unidade</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($produtos) > 0): ?>

                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?= e($produto['codigo']) ?></td>

                                    <td>
                                        <strong><?= e($produto['nome']) ?></strong>
                                        <div class="small text-muted">
                                            <?= e($produto['descricao']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $produto['peso_kg'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?> kg
                                    </td>

                                    <td><?= e($produto['unidade']) ?></td>

                                    <td>
                                        <?php if ($produto['ativo']): ?>
                                            <span class="badge text-bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            href="editar.php?id=<?= (int) $produto['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Editar"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <a
                                            href="excluir.php?id=<?= (int) $produto['id'] ?>"
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
                                    Nenhum produto encontrado.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-muted small">
                Total encontrado: <?= count($produtos) ?> produto(s)
            </div>

        </div>
    </div>

    <div class="mt-3">
        <a href="../index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Voltar ao inicio
        </a>
    </div>

</div>

</body>
</html>
