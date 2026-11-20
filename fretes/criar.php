<?php
require_once __DIR__ . '/../config/database.php';

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$erro = '';

$transportadores = $pdo->query("
    SELECT id, nome, placa, capacidade_kg
    FROM transportadores
    WHERE ativo = 1
    ORDER BY nome
")->fetchAll();

$enderecos = $pdo->query("
    SELECT id, identificacao, cidade, estado
    FROM enderecos
    ORDER BY identificacao
")->fetchAll();

$produtos = $pdo->query("
    SELECT id, nome, codigo, peso_kg
    FROM produtos
    WHERE ativo = 1
    ORDER BY nome
")->fetchAll();

$linhasProdutos = [
    ['produto_id' => '', 'quantidade' => 1]
];

$transportadorId = '';
$origemId = '';
$destinoId = '';
$dataFrete = date('Y-m-d');
$previsaoEntrega = '';
$status = 'Pendente';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $transportadorId = (int) ($_POST['transportador_id'] ?? 0);
    $origemId = (int) ($_POST['origem_id'] ?? 0);
    $destinoId = (int) ($_POST['destino_id'] ?? 0);

    $dataFrete = $_POST['data_frete'] ?? '';
    $previsaoEntrega = $_POST['previsao_entrega'] ?? '';
    $status = $_POST['status'] ?? 'Pendente';
    $observacoes = trim($_POST['observacoes'] ?? '');

    $produtosSelecionados = $_POST['produtos'] ?? [];
    $quantidades = $_POST['quantidades'] ?? [];

    $linhasProdutos = [];

    foreach ($produtosSelecionados as $indice => $produtoId) {
        $linhasProdutos[] = [
            'produto_id' => $produtoId,
            'quantidade' => $quantidades[$indice] ?? 1
        ];
    }

    if (empty($linhasProdutos)) {
        $linhasProdutos = [
            ['produto_id' => '', 'quantidade' => 1]
        ];
    }

    $statusPermitidos = [
        'Pendente',
        'Em trânsito',
        'Entregue',
        'Cancelado'
    ];

    if (!$transportadorId || !$origemId || !$destinoId) {
        $erro = 'Selecione o transportador, a origem e o destino.';
    } elseif ($origemId === $destinoId) {
        $erro = 'A origem e o destino devem ser diferentes.';
    } elseif (empty($dataFrete)) {
        $erro = 'Informe a data do frete.';
    } elseif (
        $previsaoEntrega !== '' &&
        $previsaoEntrega < $dataFrete
    ) {
        $erro = 'A previsão de entrega não pode ser anterior à data do frete.';
    } elseif (!in_array($status, $statusPermitidos, true)) {
        $erro = 'Status inválido.';
    } elseif (count($produtosSelecionados) === 0) {
        $erro = 'Adicione pelo menos um produto ao frete.';
    } else {

        // Verifica o transportador e sua capacidade.
        $stmt = $pdo->prepare("
            SELECT id, capacidade_kg
            FROM transportadores
            WHERE id = :id AND ativo = 1
        ");
        $stmt->execute([':id' => $transportadorId]);
        $transportador = $stmt->fetch();

        // Verifica os endereços.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM enderecos
            WHERE id IN (:origem, :destino)
        ");
        $stmt->execute([
            ':origem' => $origemId,
            ':destino' => $destinoId
        ]);

        $totalEnderecos = (int) $stmt->fetchColumn();

        if (!$transportador) {
            $erro = 'Transportador não encontrado ou inativo.';
        } elseif ($totalEnderecos !== 2) {
            $erro = 'Um dos endereços selecionados não foi encontrado.';
        } else {

            $pesoTotal = 0;
            $itensFrete = [];
            $idsProdutos = array_map('intval', $produtosSelecionados);

            if (count($idsProdutos) !== count(array_unique($idsProdutos))) {
                $erro = 'Não selecione o mesmo produto mais de uma vez.';
            } elseif (in_array(0, $idsProdutos, true)) {
                $erro = 'Selecione produtos válidos.';
            } else {

                // Busca os produtos ativos selecionados.
                $placeholders = implode(
                    ',',
                    array_fill(0, count($idsProdutos), '?')
                );

                $stmt = $pdo->prepare("
                    SELECT id, nome, peso_kg
                    FROM produtos
                    WHERE ativo = 1
                    AND id IN ($placeholders)
                ");

                $stmt->execute($idsProdutos);
                $produtosEncontrados = $stmt->fetchAll();

                if (count($produtosEncontrados) !== count($idsProdutos)) {
                    $erro = 'Um ou mais produtos não foram encontrados ou estão inativos.';
                } else {

                    $produtosPorId = [];

                    foreach ($produtosEncontrados as $produto) {
                        $produtosPorId[$produto['id']] = $produto;
                    }

                    foreach ($linhasProdutos as $linha) {

                        $produtoId = (int) $linha['produto_id'];
                        $quantidade = filter_var(
                            $linha['quantidade'],
                            FILTER_VALIDATE_INT
                        );

                        if (
                            !$produtoId ||
                            !$quantidade ||
                            $quantidade < 1 ||
                            !isset($produtosPorId[$produtoId])
                        ) {
                            $erro = 'Verifique os produtos e suas quantidades.';
                            break;
                        }

                        $produto = $produtosPorId[$produtoId];

                        $pesoTotal +=
                            (float) $produto['peso_kg'] * $quantidade;

                        $itensFrete[] = [
                            'produto_id' => $produtoId,
                            'quantidade' => $quantidade
                        ];
                    }

                    // Verifica a capacidade do transportador.
                    if (
                        $erro === '' &&
                        $transportador['capacidade_kg'] !== null &&
                        $pesoTotal > (float) $transportador['capacidade_kg']
                    ) {
                        $erro = 'O peso total da carga ultrapassa a capacidade do transportador.';
                    }

                    // Salva o frete e seus produtos.
                    if ($erro === '') {
                        try {
                            $pdo->beginTransaction();

                            $stmt = $pdo->prepare("
                                INSERT INTO fretes (
                                    transportador_id,
                                    origem_id,
                                    destino_id,
                                    data_frete,
                                    previsao_entrega,
                                    status,
                                    observacoes
                                ) VALUES (
                                    :transportador,
                                    :origem,
                                    :destino,
                                    :data_frete,
                                    :previsao,
                                    :status,
                                    :observacoes
                                )
                            ");

                            $stmt->execute([
                                ':transportador' => $transportadorId,
                                ':origem' => $origemId,
                                ':destino' => $destinoId,
                                ':data_frete' => $dataFrete,
                                ':previsao' => $previsaoEntrega ?: null,
                                ':status' => $status,
                                ':observacoes' => $observacoes
                            ]);

                            $freteId = $pdo->lastInsertId();

                            $stmtProduto = $pdo->prepare("
                                INSERT INTO frete_produtos (
                                    frete_id,
                                    produto_id,
                                    quantidade
                                ) VALUES (
                                    :frete_id,
                                    :produto_id,
                                    :quantidade
                                )
                            ");

                            foreach ($itensFrete as $item) {
                                $stmtProduto->execute([
                                    ':frete_id' => $freteId,
                                    ':produto_id' => $item['produto_id'],
                                    ':quantidade' => $item['quantidade']
                                ]);
                            }

                            $pdo->commit();

                            header('Location: index.php?sucesso=criado');
                            exit;

                        } catch (PDOException $ex) {

                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            $erro = 'Não foi possível cadastrar o frete. Verifique os dados e tente novamente.';
                        }
                    }
                }
            }
        }
    }
}

$cadastroDisponivel =
    count($transportadores) > 0 &&
    count($enderecos) >= 2 &&
    count($produtos) > 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo Frete - LogiControl</title>

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
            <h2>Novo Frete</h2>
            <p class="text-muted mb-0">
                Cadastre um novo frete e seus produtos.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <?php if (!$cadastroDisponivel): ?>

        <div class="alert alert-warning">
            <strong>Não é possível cadastrar o frete ainda.</strong>
            <p class="mb-0 mt-2">
                É necessário ter pelo menos um transportador ativo,
                dois endereços cadastrados e um produto ativo.
            </p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            Voltar para fretes
        </a>

    <?php else: ?>

    <form method="POST">

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <strong>Informações do frete</strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Transportador</label>

                        <select
                            name="transportador_id"
                            id="transportador_id"
                            class="form-select"
                            required
                        >
                            <option value="">Selecione</option>

                            <?php foreach ($transportadores as $t): ?>
                                <option
                                    value="<?= $t['id'] ?>"
                                    data-capacidade="<?= e($t['capacidade_kg'] ?? '') ?>"
                                    <?= (string) $transportadorId === (string) $t['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($t['nome']) ?>
                                    - <?= e($t['placa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <small class="text-muted" id="capacidadeInfo">
                            Selecione um transportador para visualizar a capacidade.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-select" required>
                            <?php foreach (
                                ['Pendente', 'Em trânsito', 'Entregue', 'Cancelado']
                                as $opcao
                            ): ?>
                                <option
                                    value="<?= e($opcao) ?>"
                                    <?= $status === $opcao ? 'selected' : '' ?>
                                >
                                    <?= e($opcao) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Endereço de origem</label>

                        <select name="origem_id" class="form-select" required>
                            <option value="">Selecione</option>

                            <?php foreach ($enderecos as $endereco): ?>
                                <option
                                    value="<?= $endereco['id'] ?>"
                                    <?= (string) $origemId === (string) $endereco['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($endereco['identificacao']) ?>
                                    - <?= e($endereco['cidade']) ?>/<?= e($endereco['estado']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Endereço de destino</label>

                        <select name="destino_id" class="form-select" required>
                            <option value="">Selecione</option>

                            <?php foreach ($enderecos as $endereco): ?>
                                <option
                                    value="<?= $endereco['id'] ?>"
                                    <?= (string) $destinoId === (string) $endereco['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($endereco['identificacao']) ?>
                                    - <?= e($endereco['cidade']) ?>/<?= e($endereco['estado']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Data do frete</label>

                        <input
                            type="date"
                            name="data_frete"
                            class="form-control"
                            value="<?= e($dataFrete) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Previsão de entrega</label>

                        <input
                            type="date"
                            name="previsao_entrega"
                            class="form-control"
                            value="<?= e($previsaoEntrega) ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observações</label>

                        <textarea
                            name="observacoes"
                            class="form-control"
                            rows="3"
                        ><?= e($observacoes) ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">

            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Produtos do frete</strong>

                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    id="adicionarProduto"
                >
                    + Adicionar produto
                </button>
            </div>

            <div class="card-body">

                <div id="listaProdutos">

                    <?php foreach ($linhasProdutos as $linha): ?>

                        <div class="row g-2 mb-3 linha-produto align-items-end">

                            <div class="col-md-7">
                                <label class="form-label">Produto</label>

                                <select
                                    name="produtos[]"
                                    class="form-select produto-select"
                                    required
                                >
                                    <option value="">Selecione um produto</option>

                                    <?php foreach ($produtos as $produto): ?>
                                        <option
                                            value="<?= $produto['id'] ?>"
                                            data-peso="<?= e($produto['peso_kg']) ?>"
                                            <?= (string) $linha['produto_id'] === (string) $produto['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($produto['nome']) ?>
                                            (<?= e($produto['codigo']) ?>)
                                            - <?= number_format((float) $produto['peso_kg'], 3, ',', '.') ?> kg
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Quantidade</label>

                                <input
                                    type="number"
                                    name="quantidades[]"
                                    class="form-control quantidade-input"
                                    min="1"
                                    step="1"
                                    value="<?= e($linha['quantidade']) ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger w-100 remover-produto"
                                >
                                    Remover
                                </button>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="border rounded p-3 mt-3">
                    <div class="d-flex justify-content-between">
                        <strong>Peso total da carga:</strong>

                        <strong id="pesoTotal">0,000 kg</strong>
                    </div>

                    <small class="text-muted" id="avisoCapacidade"></small>
                </div>

            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">

            <a href="index.php" class="btn btn-outline-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-success">
                Salvar frete
            </button>

        </div>

    </form>

    <?php endif; ?>

</div>

<template id="modeloProduto">
    <div class="row g-2 mb-3 linha-produto align-items-end">

        <div class="col-md-7">
            <label class="form-label">Produto</label>

            <select
                name="produtos[]"
                class="form-select produto-select"
                required
            >
                <option value="">Selecione um produto</option>

                <?php foreach ($produtos as $produto): ?>
                    <option
                        value="<?= $produto['id'] ?>"
                        data-peso="<?= e($produto['peso_kg']) ?>"
                    >
                        <?= e($produto['nome']) ?>
                        (<?= e($produto['codigo']) ?>)
                        - <?= number_format((float) $produto['peso_kg'], 3, ',', '.') ?> kg
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Quantidade</label>

            <input
                type="number"
                name="quantidades[]"
                class="form-control quantidade-input"
                min="1"
                step="1"
                value="1"
                required
            >
        </div>

        <div class="col-md-2">
            <button
                type="button"
                class="btn btn-outline-danger w-100 remover-produto"
            >
                Remover
            </button>
        </div>

    </div>
</template>

<script>
const listaProdutos = document.getElementById('listaProdutos');
const modeloProduto = document.getElementById('modeloProduto');

const botaoAdicionar = document.getElementById('adicionarProduto');
const transportadorSelect = document.getElementById('transportador_id');

const pesoTotalElemento = document.getElementById('pesoTotal');
const capacidadeInfo = document.getElementById('capacidadeInfo');
const avisoCapacidade = document.getElementById('avisoCapacidade');

function calcularPesoTotal() {

    let pesoTotal = 0;

    document.querySelectorAll('.linha-produto').forEach(linha => {

        const select = linha.querySelector('.produto-select');
        const quantidadeInput = linha.querySelector('.quantidade-input');

        const opcao = select.selectedOptions[0];

        const peso = opcao
            ? Number(opcao.dataset.peso || 0)
            : 0;

        const quantidade = Number(quantidadeInput.value || 0);

        if (quantidade > 0) {
            pesoTotal += peso * quantidade;
        }
    });

    pesoTotalElemento.textContent =
        pesoTotal.toLocaleString('pt-BR', {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        }) + ' kg';

    verificarCapacidade(pesoTotal);
}

function verificarCapacidade(pesoTotal) {

    const opcao = transportadorSelect.selectedOptions[0];

    if (!opcao || !opcao.value) {
        capacidadeInfo.textContent =
            'Selecione um transportador para visualizar a capacidade.';

        avisoCapacidade.textContent = '';
        return;
    }

    const capacidadeTexto = opcao.dataset.capacidade;

    if (capacidadeTexto === '') {
        capacidadeInfo.textContent =
            'Capacidade do transportador não informada.';

        avisoCapacidade.textContent = '';
        return;
    }

    const capacidade = Number(capacidadeTexto);

    capacidadeInfo.textContent =
        'Capacidade do veículo: ' +
        capacidade.toLocaleString('pt-BR') + ' kg';

    if (pesoTotal > capacidade) {
        avisoCapacidade.textContent =
            'Atenção: o peso total ultrapassa a capacidade do veículo.';

        avisoCapacidade.className = 'text-danger d-block mt-2';
    } else {
        avisoCapacidade.textContent =
            'Carga dentro da capacidade do veículo.';

        avisoCapacidade.className = 'text-success d-block mt-2';
    }
}

botaoAdicionar.addEventListener('click', function () {

    const novaLinha = modeloProduto.content.cloneNode(true);

    listaProdutos.appendChild(novaLinha);

    calcularPesoTotal();
});

listaProdutos.addEventListener('click', function (evento) {

    if (evento.target.classList.contains('remover-produto')) {

        const linhas = document.querySelectorAll('.linha-produto');

        if (linhas.length > 1) {
            evento.target.closest('.linha-produto').remove();
            calcularPesoTotal();
        } else {
            alert('O frete precisa ter pelo menos um produto.');
        }
    }
});

listaProdutos.addEventListener('change', function () {
    calcularPesoTotal();
});

listaProdutos.addEventListener('input', function () {
    calcularPesoTotal();
});

transportadorSelect.addEventListener('change', function () {
    calcularPesoTotal();
});

calcularPesoTotal();
</script>

</body>
</html>
