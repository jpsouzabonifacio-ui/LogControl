
<?php
require_once __DIR__ . '/../config/database.php';

$filtroStatus = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT
        f.*,
        t.nome AS transportador,
        o.identificacao AS origem,
        d.identificacao AS destino,
        COUNT(DISTINCT fp.id) AS total_itens,
        COALESCE(SUM(fp.quantidade * p.peso_kg), 0) AS peso_total
    FROM fretes f
    INNER JOIN transportadores t ON t.id = f.transportador_id
    INNER JOIN enderecos o ON o.id = f.origem_id
    INNER JOIN enderecos d ON d.id = f.destino_id
    LEFT JOIN frete_produtos fp ON fp.frete_id = f.id
    LEFT JOIN produtos p ON p.id = fp.produto_id
    WHERE 1=1
";

$params = [];

if ($filtroStatus !== '') {
    $sql .= " AND f.status = :status";
    $params[':status'] = $filtroStatus;
}

if ($busca !== '') {
    $sql .= " AND (
        t.nome LIKE :busca
        OR o.identificacao LIKE :busca
        OR d.identificacao LIKE :busca
    )";

    $params[':busca'] = "%$busca%";
}

$sql .= "
    GROUP BY f.id
    ORDER BY f.data_frete DESC, f.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$fretes = $stmt->fetchAll();

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Fretes - LogiControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gerenciamento de Fretes</h2>
            <p class="text-muted">Controle de transportes e entregas</p>
        </div>

        <a href="criar.php" class="btn btn-primary">
            + Novo Frete
        </a>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">
            Operação realizada com sucesso!
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="GET" class="row g-3 mb-4">

                <div class="col-md-5">
                    <label class="form-label">Pesquisar</label>

                    <input
                        type="text"
                        name="busca"
                        class="form-control"
                        placeholder="Transportador ou endereço"
                        value="<?= e($busca) ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>

                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>

                        <?php
                        $statusOpcoes = [
                            'Pendente',
                            'Em trânsito',
                            'Entregue',
                            'Cancelado'
                        ];
                        ?>

                        <?php foreach ($statusOpcoes as $status): ?>
                            <option value="<?= e($status) ?>"
                                <?= $filtroStatus === $status ? 'selected' : '' ?>>
                                <?= e($status) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-secondary">
                        Filtrar
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Transportador</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Data</th>
                            <th>Previsão</th>
                            <th>Produtos</th>
                            <th>Peso Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($fretes) > 0): ?>

                        <?php foreach ($fretes as $frete): ?>

                            <tr>
                                <td>#<?= (int)$frete['id'] ?></td>

                                <td><?= e($frete['transportador']) ?></td>

                                <td><?= e($frete['origem']) ?></td>

                                <td><?= e($frete['destino']) ?></td>

                                <td>
                                    <?= date('d/m/Y', strtotime($frete['data_frete'])) ?>
                                </td>

                                <td>
                                    <?= $frete['previsao_entrega']
                                        ? date('d/m/Y', strtotime($frete['previsao_entrega']))
                                        : '-' ?>
                                </td>

                                <td><?= (int)$frete['total_itens'] ?></td>

                                <td>
                                    <?= number_format((float)$frete['peso_total'], 2, ',', '.') ?> kg
                                </td>

                                <td>
                                    <?php
                                    $cores = [
                                        'Pendente' => 'warning',
                                        'Em trânsito' => 'primary',
                                        'Entregue' => 'success',
                                        'Cancelado' => 'danger'
                                    ];

                                    $cor = $cores[$frete['status']] ?? 'secondary';
                                    ?>

                                    <span class="badge bg-<?= $cor ?>">
                                        <?= e($frete['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="editar.php?id=<?= (int)$frete['id'] ?>"
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <a href="excluir.php?id=<?= (int)$frete['id'] ?>"
                                       class="btn btn-sm btn-danger">
                                        Excluir
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Nenhum frete encontrado.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <p class="text-muted mb-0">
                Total de fretes encontrados:
                <strong><?= count($fretes) ?></strong>
            </p>

        </div>
    </div>

    <div class="mt-3">
        <a href="../index.php" class="btn btn-outline-secondary">
            Voltar ao inicio
        </a>
    </div>

</div>

</body>
</html>
