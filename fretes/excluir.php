<?php
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// Verifica se o frete existe.
$stmt = $pdo->prepare("
    SELECT id
    FROM fretes
    WHERE id = :id
");

$stmt->execute([':id' => $id]);

$frete = $stmt->fetch();

if (!$frete) {
    header('Location: index.php');
    exit;
}

try {

    $pdo->beginTransaction();

    // Exclui os produtos vinculados ao frete.
    $stmt = $pdo->prepare("
        DELETE FROM frete_produtos
        WHERE frete_id = :id
    ");

    $stmt->execute([':id' => $id]);

    // Exclui o frete.
    $stmt = $pdo->prepare("
        DELETE FROM fretes
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    $pdo->commit();

    header('Location: index.php?sucesso=excluido');
    exit;

} catch (PDOException $ex) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Não foi possível excluir o frete. Tente novamente.');
}
