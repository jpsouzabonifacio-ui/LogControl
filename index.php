<?php
require_once __DIR__ . '/config/database.php';

// Função para proteger a saída HTML
function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// Buscar indicadores
$totalProdutos = (int) $pdo->query(
    "SELECT COUNT(*) FROM produtos WHERE ativo = 1"
)->fetchColumn();

$totalTransportadores = (int) $pdo->query(
    "SELECT COUNT(*) FROM transportadores WHERE ativo = 1"
)->fetchColumn();

$totalPendentes = (int) $pdo->query(
    "SELECT COUNT(*) FROM fretes WHERE status = 'Pendente'"
)->fetchColumn();

$totalTransito = (int) $pdo->query(
    "SELECT COUNT(*) FROM fretes WHERE status = 'Em trânsito'"
)->fetchColumn();

$totalFretes = (int) $pdo->query(
    "SELECT COUNT(*) FROM fretes"
)->fetchColumn();

// Buscar os últimos fretes
$sql = "
    SELECT
        f.id,
        f.data_frete,
        f.status,
        t.nome AS transportador,
        eo.cidade AS cidade_origem,
        ed.cidade AS cidade_destino,
        COUNT(fp.id) AS total_itens
    FROM fretes f
    INNER JOIN transportadores t
        ON t.id = f.transportador_id
    INNER JOIN enderecos eo
        ON eo.id = f.origem_id
    INNER JOIN enderecos ed
        ON ed.id = f.destino_id
    LEFT JOIN frete_produtos fp
        ON fp.frete_id = f.id
    GROUP BY
        f.id,
        f.data_frete,
        f.status,
        t.nome,
        eo.cidade,
        ed.cidade
    ORDER BY f.id DESC
    LIMIT 5
";

$fretes = $pdo->query($sql)->fetchAll();

function classeStatus($status) {
    switch ($status) {
        case 'Pendente':
            return 'warning';

        case 'Em trânsito':
            return 'primary';

        case 'Entregue':
            return 'success';

        case 'Cancelado':
            return 'danger';

        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="d-flex">

    <!-- MENU LATERAL -->
    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <i class="bi bi-truck"></i>
            <span>LogControl</span>
        </div>

        <small class="menu-title">MENU PRINCIPAL</small>

        <a href="index.php" class="menu-link active">
            <i class="bi bi-grid"></i>
            Dashboard
        </a>

        <a href="produtos/index.php" class="menu-link">
            <i class="bi bi-box-seam"></i>
            Produtos
        </a>

        <a href="enderecos/index.php" class="menu-link">
            <i class="bi bi-geo-alt"></i>
            Endereços
        </a>

        <a href="transportadores/index.php" class="menu-link">
            <i class="bi bi-truck"></i>
            Transportadores
        </a>

        <a href="fretes/index.php" class="menu-link">
            <i class="bi bi-clipboard-check"></i>
            Fretes
        </a>

        <a href="relatorios/fretes.php" class="menu-link">
            <i class="bi bi-file-earmark-bar-graph"></i>
            Relatórios
        </a>

        <div class="sidebar-footer">
            <i class="bi bi-shield-check"></i>
            Sistema Logístico
            <small>Versão 1.0</small>
        </div>

    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="main-content flex-grow-1">

        <!-- CABEÇALHO -->
        <nav class="topbar d-flex justify-content-between align-items-center">

            <div class="d-flex align-items-center gap-3">

                <button class="btn btn-light d-lg-none"
                        type="button"
                        onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <div>
                    <h5 class="mb-0 fw-bold">Dashboard</h5>
                    <small class="text-secondary">
                        Visão geral da operação logística
                    </small>
                </div>

            </div>

            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person"></i>
                </div>

                <div class="d-none d-sm-block">
                    <strong>Administrador</strong>
                    <small>Gestor do sistema</small>
                </div>
            </div>

        </nav>

        <div class="container-fluid p-4">

            <!-- BOAS-VINDAS -->
            <div class="welcome-area mb-4">
                <h4>Bem-vindo ao LogControl!</h4>
                <p>
                    Acompanhe as principais informações
                    da sua operação logística.
                </p>
            </div>

            <!-- INDICADORES -->
            <div class="row g-4 mb-4">

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="bi bi-box-seam"></i>
                        </div>

                        <span>Produtos cadastrados</span>

                        <h3><?= $totalProdutos ?></h3>

                        <small>Produtos ativos no sistema</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="bi bi-truck"></i>
                        </div>

                        <span>Transportadores</span>

                        <h3><?= $totalTransportadores ?></h3>

                        <small>Transportadores ativos</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="bi bi-clock-history"></i>
                        </div>

                        <span>Fretes pendentes</span>

                        <h3><?= $totalPendentes ?></h3>

                        <small>Aguardando andamento</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </div>

                        <span>Fretes em trânsito</span>

                        <h3><?= $totalTransito ?></h3>

                        <small>Entregas em andamento</small>
                    </div>
                </div>

            </div>

            <!-- TABELA DE FRETES -->
            <div class="card dashboard-table border-0 shadow-sm">

                <div class="card-header bg-white border-0 p-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div>
                            <h5 class="fw-bold mb-1">Fretes recentes</h5>

                            <small class="text-secondary">
                                Últimos registros realizados no sistema
                            </small>
                        </div>

                        <a href="fretes/index.php" class="btn btn-primary">
                            <i class="bi bi-eye me-1"></i>
                            Ver todos
                        </a>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Frete</th>
                                <th>Transportador</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Data</th>
                                <th>Produtos</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (count($fretes) > 0): ?>

                            <?php foreach ($fretes as $frete): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            #<?= escapar($frete['id']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= escapar($frete['transportador']) ?>
                                    </td>

                                    <td>
                                        <?= escapar($frete['cidade_origem']) ?>
                                    </td>

                                    <td>
                                        <?= escapar($frete['cidade_destino']) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            'd/m/Y',
                                            strtotime($frete['data_frete'])
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int)$frete['total_itens'] ?>
                                    </td>

                                    <td>
                                        <span class="badge text-bg-<?= classeStatus($frete['status']) ?>">
                                            <?= escapar($frete['status']) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="text-center py-5">

                                    <i class="bi bi-inbox fs-1 text-secondary"></i>

                                    <p class="text-secondary mt-2 mb-0">
                                        Nenhum frete cadastrado ainda.
                                    </p>

                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="card-footer bg-white border-0 p-3">
                    <small class="text-secondary">
                        Total de fretes cadastrados:
                        <strong><?= $totalFretes ?></strong>
                    </small>
                </div>

            </div>

            <!-- ATALHOS -->
            <div class="mt-4">

                <h5 class="fw-bold mb-3">Acesso rápido</h5>

                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <a href="produtos/cadastrar.php"
                           class="quick-link">

                            <i class="bi bi-plus-circle"></i>

                            <div>
                                <strong>Novo produto</strong>
                                <small>Cadastrar um produto</small>
                            </div>

                        </a>

                    </div>

                    <div class="col-12 col-md-4">

                        <a href="transportadores/cadastrar.php"
                           class="quick-link">

                            <i class="bi bi-person-plus"></i>

                            <div>
                                <strong>Novo transportador</strong>
                                <small>Cadastrar transportador</small>
                            </div>

                        </a>

                    </div>

                    <div class="col-12 col-md-4">

                        <a href="fretes/cadastrar.php"
                           class="quick-link">

                            <i class="bi bi-truck"></i>

                            <div>
                                <strong>Novo frete</strong>
                                <small>Gerar um novo frete</small>
                            </div>

                        </a>

                    </div>

                </div>

            </div>

            <footer class="text-center text-secondary mt-5 mb-2">
                <small>
                    © <?= date('Y') ?> LogControl -
                    Gerenciador Logístico
                </small>
            </footer>

        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
