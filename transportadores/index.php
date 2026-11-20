<?php
require_once __DIR__ . '/../config/database.php';

$busca = trim($_GET['busca'] ?? '');

$sql = "SELECT * FROM transportadores";

if ($busca !== '') {
    $sql .= " WHERE nome LIKE :busca
              OR documento LIKE :busca
              OR placa LIKE :busca";
}

$sql .= " ORDER BY nome ASC";

$stmt = $pdo->prepare($sql);

if ($busca !== '') {
    $stmt->bindValue(':busca', "%$busca%");
}

$stmt->execute();
$transportadores = $stmt->fetchAll();

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Transportadores - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Transportadores</h2>
            <p class="text-muted">Gerenciamento de transportadores cadastrados</p>
        </div>

        <a href="criar.php" class="btn btn-primary">
            + Novo Transportador
        </a>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">
            Operação realizada com sucesso!
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="busca"
                        class="form-control"
                        placeholder="Pesquisar por nome, documento ou placa"
                        value="<?= e($busca) ?>"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-secondary">
                        Pesquisar
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Documento</th>
                            <th>Telefone</th>
                            <th>Veículo</th>
                            <th>Placa</th>
                            <th>Capacidade (kg)</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($transportadores) > 0): ?>

                        <?php foreach ($transportadores as $t): ?>
                            <tr>
                                <td><?= e($t['nome']) ?></td>

                                <td>
                                    <?= $t['tipo'] === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' ?>
                                </td>

                                <td><?= e($t['documento']) ?></td>

                                <td><?= e($t['telefone']) ?></td>

                                <td><?= e($t['tipo_veiculo']) ?></td>

                                <td><?= e($t['placa']) ?></td>

                                <td><?= number_format((float)$t['capacidade_kg'], 2, ',', '.') ?></td>

                                <td>
                                    <?php if ($t['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a
                                        href="editar.php?id=<?= (int)$t['id'] ?>"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Editar
                                    </a>

                                    <a
                                        href="excluir.php?id=<?= (int)$t['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                    >
                                        Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Nenhum transportador encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <p class="text-muted mb-0">
                Total de transportadores encontrados:
                <strong><?= count($transportadores) ?></strong>
            </p>

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
