
<?php
require_once __DIR__ . '/../config/database.php';

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$erros = [];

$identificacao = '';
$destinatario = '';
$cep = '';
$rua = '';
$numero = '';
$complemento = '';
$bairro = '';
$cidade = '';
$estado = '';
$referencia = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identificacao = trim($_POST['identificacao'] ?? '');
    $destinatario = trim($_POST['destinatario'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $rua = trim($_POST['rua'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $referencia = trim($_POST['referencia'] ?? '');
   

    if ($identificacao === '') {
        $erros[] = 'Informe a identificação do endereço.';
    }

    if ($destinatario === '') {
        $erros[] = 'Informe o destinatário.';
    }

    if ($cep === '') {
        $erros[] = 'Informe o CEP.';
    }

    if ($rua === '') {
        $erros[] = 'Informe a rua.';
    }

    if ($numero === '') {
        $erros[] = 'Informe o número.';
    }

    if ($bairro === '') {
        $erros[] = 'Informe o bairro.';
    }

    if ($cidade === '') {
        $erros[] = 'Informe a cidade.';
    }

    if (!preg_match('/^[A-Z]{2}$/', $estado)) {
        $erros[] = 'Informe uma UF válida com duas letras.';
    }

    if (empty($erros)) {

        try {

            $sql = "
                INSERT INTO enderecos (
                    identificacao,
                    destinatario,
                    cep,
                    rua,
                    numero,
                    complemento,
                    bairro,
                    cidade,
                    estado,
                    referencia
                ) VALUES (
                    :identificacao,
                    :destinatario,
                    :cep,
                    :rua,
                    :numero,
                    :complemento,
                    :bairro,
                    :cidade,
                    :estado,
                    :referencia
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':identificacao' => $identificacao,
                ':destinatario' => $destinatario,
                ':cep' => $cep,
                ':rua' => $rua,
                ':numero' => $numero,
                ':complemento' => $complemento,
                ':bairro' => $bairro,
                ':cidade' => $cidade,
                ':estado' => $estado,
                ':referencia' => $referencia
            ]);

            header('Location: index.php?sucesso=criado');
            exit;

        } catch (PDOException $e) {

            $erros[] = 'Não foi possível cadastrar o endereço.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo Endereço | LogControl</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Novo endereço</h2>
        <p class="text-muted">
            Preencha os dados do endereço para cadastro.
        </p>
    </div>

    <?php if (!empty($erros)): ?>

        <div class="alert alert-danger">
            <ul class="mb-0">

                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>

            </ul>
        </div>

    <?php endif; ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Identificação *</label>

                        <input
                            type="text"
                            name="identificacao"
                            class="form-control"
                            maxlength="100"
                            placeholder="Ex: Centro de distribuição"
                            value="<?= e($identificacao) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Destinatário *</label>

                        <input
                            type="text"
                            name="destinatario"
                            class="form-control"
                            maxlength="150"
                            value="<?= e($destinatario) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CEP *</label>

                        <input
                            type="text"
                            name="cep"
                            class="form-control"
                            maxlength="9"
                            placeholder="00000-000"
                            value="<?= e($cep) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rua *</label>

                        <input
                            type="text"
                            name="rua"
                            class="form-control"
                            maxlength="150"
                            value="<?= e($rua) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Número *</label>

                        <input
                            type="text"
                            name="numero"
                            class="form-control"
                            maxlength="20"
                            value="<?= e($numero) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Complemento</label>

                        <input
                            type="text"
                            name="complemento"
                            class="form-control"
                            maxlength="100"
                            value="<?= e($complemento) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Bairro *</label>

                        <input
                            type="text"
                            name="bairro"
                            class="form-control"
                            maxlength="100"
                            value="<?= e($bairro) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Cidade *</label>

                        <input
                            type="text"
                            name="cidade"
                            class="form-control"
                            maxlength="100"
                            value="<?= e($cidade) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado (UF) *</label>

                        <input
                            type="text"
                            name="estado"
                            class="form-control"
                            maxlength="2"
                            placeholder="PR"
                            value="<?= e($estado) ?>"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Ponto de referência</label>

                        <textarea
                            name="referencia"
                            class="form-control"
                            rows="3"
                            maxlength="255"
                        ><?= e($referencia) ?></textarea>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">

                    <a href="index.php" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Salvar endereço
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>
