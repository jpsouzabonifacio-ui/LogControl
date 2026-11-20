<?php
require_once __DIR__ . '/../config/database.php';

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

$fretes = [];

if (
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim) &&
    $dataInicio <= $dataFim
) {

    $stmt = $pdo->prepare("
        SELECT
            f.id,
            f.data_frete,
            f.previsao_entrega,
            f.status,
            f.observacoes,
            t.nome AS transportador,
            t.placa,
            o.identificacao AS origem,
            d.identificacao AS destino,
            COUNT(DISTINCT fp.id) AS total_itens,
            COALESCE(SUM(fp.quantidade), 0) AS total_unidades,
            COALESCE(SUM(fp.quantidade * p.peso_kg), 0) AS peso_total
        FROM fretes f
        INNER JOIN transportadores t
            ON t.id = f.transportador_id
        INNER JOIN enderecos o
            ON o.id = f.origem_id
        INNER JOIN enderecos d
            ON d.id = f.destino_id
        LEFT JOIN frete_produtos fp
            ON fp.frete_id = f.id
        LEFT JOIN produtos p
            ON p.id = fp.produto_id
        WHERE f.data_frete BETWEEN :inicio AND :fim
        GROUP BY f.id
        ORDER BY f.data_frete ASC, f.id ASC
    ");

    $stmt->execute([
        ':inicio' => $dataInicio,
        ':fim' => $dataFim
    ]);

    $fretes = $stmt->fetchAll();
}

// Exportação CSV.
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {

    if (!isset($stmt) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim) ||
        $dataInicio > $dataFim
    ) {
        die('Período inválido.');
    }

    $nomeArquivo = 'relatorio_fretes_' . $dataInicio . '_a_' . $dataFim . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

    $saida = fopen('php://output', 'w');

    // BOM para abrir corretamente no Excel.
    fwrite($saida, "\xEF\xBB\xBF");

    fputcsv($saida, [
        'ID',
        'Data do frete',
        'Transportador',
        'Placa',
        'Origem',
        'Destino',
        'Previsão de entrega',
        'Produtos diferentes',
        'Total de unidades',
        'Peso total (kg)',
        'Status',
        'Observações'
    ], ';');

    foreach ($fretes as $frete) {

        fputcsv($saida, [
            $frete['id'],
            $frete['data_frete'],
            $frete['transportador'],
            $frete['placa'],
            $frete['origem'],
            $frete['destino'],
            $frete['previsao_entrega'],
            $frete['total_itens'],
            $frete['total_unidades'],
            number_format((float) $frete['peso_total'], 3, ',', '.'),
            $frete['status'],
            $frete['observacoes']
        ], ';');
    }

    fclose($saida);
    exit;
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$totalFretes = count($fretes);
$totalUnidades = 0;
$pesoGeral = 0;

foreach ($fretes as $frete) {
    $totalUnidades += (int) $frete['total_unidades'];
    $pesoGeral += (float) $frete['peso_total'];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relatório de Fretes - LogiControl</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2>Relatório de Fretes</h2>
            <p class="text-muted mb-0">
                Consulte os fretes por período.
            </p>
        </div>

        <a href="../index.php" class="btn btn-outline-secondary">
            Voltar ao início
        </a>

    </div>

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <strong>Filtrar período</strong>
        </div>

        <div class="card-body">

            <form method="GET" class="row g-3">

                <div class="col-md-4">

                    <label class="form-label">Data inicial</label>

                    <input
                        type="date"
                        name="data_inicio"
                        class="form-control"
                        value="<?= e($dataInicio) ?>"
                        required
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">Data final</label>

                    <input
                        type="date"
                        name="data_fim"
                        class="form-control"
                        value="<?= e($dataFim) ?>"
                        required
                    >

                </div>

                <div class="col-md-4 d-flex align-items-end gap-2">

                    <button type="submit" class="btn btn-primary">
                        Filtrar
                    </button>

                    <a
                        href="fretes.php"
                        class="btn btn-outline-secondary"
                    >
                        Limpar
                    </a>

                </div>

            </form>

            <?php if (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) ||
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim) ||
                $dataInicio > $dataFim
            ): ?>

                <div class="alert alert-danger mt-3 mb-0">
                    Informe um período válido. A data inicial não pode ser
                    posterior à data final.
                </div>

            <?php endif; ?>

        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Total de fretes</p>
                    <h3><?= $totalFretes ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Total de unidades</p>
                    <h3><?= $totalUnidades ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Peso total transportado</p>
                    <h3>
                        <?= number_format($pesoGeral, 3, ',', '.') ?> kg
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h5 class="mb-0">Fretes encontrados</h5>

        <?php if (
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim) &&
            $dataInicio <= $dataFim
        ): ?>

            <a
                href="?data_inicio=<?= e($dataInicio) ?>&data_fim=<?= e($dataFim) ?>&exportar=csv"
                class="btn btn-success"
            >
                Baixar relatório CSV
            </a>

        <?php endif; ?>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Transportador</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Produtos</th>
                            <th>Unidades</th>
                            <th>Peso</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($fretes) > 0): ?>

                        <?php foreach ($fretes as $frete): ?>

                            <tr>

                                <td>#<?= e($frete['id']) ?></td>

                                <td>
                                    <?= date('d/m/Y', strtotime($frete['data_frete'])) ?>
                                </td>

                                <td>
                                    <?= e($frete['transportador']) ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= e($frete['placa']) ?>
                                    </small>
                                </td>

                                <td><?= e($frete['origem']) ?></td>

                                <td><?= e($frete['destino']) ?></td>

                                <td><?= e($frete['total_itens']) ?></td>

                                <td><?= e($frete['total_unidades']) ?></td>

                                <td>
                                    <?= number_format(
                                        (float) $frete['peso_total'],
                                        3,
                                        ',',
                                        '.'
                                    ) ?> kg
                                </td>

                                <td>
                                    <?= e($frete['status']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Nenhum frete encontrado nesse período.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
