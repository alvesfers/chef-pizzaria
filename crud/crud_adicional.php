<?php
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tb_adicional WHERE id_adicional = ?");
    $stmt->execute([$id]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $a
        ? json_encode(['status' => 'ok', 'adicional' => $a])
        : json_encode(['status' => 'erro', 'mensagem' => 'Não encontrado']);
    exit;
}

// JSON input
$in = json_decode(file_get_contents('php://input'), true);
if (!$in) exit(json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']));

// DELETE lógico + cleanup
if (!empty($in['action']) && $in['action'] === 'delete') {
    $id = intval($in['id_adicional']);
    $pdo->prepare("UPDATE tb_adicional SET adicional_ativo = 0 WHERE id_adicional = ?")
        ->execute([$id]);
    $pdo->prepare("DELETE FROM tb_produto_adicional_incluso WHERE id_adicional = ?")
        ->execute([$id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// validar
$nome = trim($in['nome_adicional'] ?? '');
$tipo = intval($in['id_tipo_adicional'] ?? 0);
$valor = floatval($in['valor_adicional'] ?? 0);
if ($nome === '' || $tipo <= 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos']);
    exit;
}
$ativo = intval($in['adicional_ativo'] ?? 1);

$data = [
    'id_tipo_adicional' => $tipo,
    'nome_adicional'   => $nome,
    'valor_adicional'  => $valor,
    'adicional_ativo'  => $ativo,
];

if (!empty($in['id_adicional'])) {
    $id = intval($in['id_adicional']);
    $sets = implode(', ', array_map(fn($c) => "$c=:$c", array_keys($data)));
    $stmt = $pdo->prepare("UPDATE tb_adicional SET $sets WHERE id_adicional = :id");
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols = implode(', ', array_keys($data));
    $ph   = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $stmt = $pdo->prepare("INSERT INTO tb_adicional ($cols) VALUES ($ph)");
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}
$data['id_adicional'] = $id;
echo json_encode(['status' => 'ok', 'adicional' => $data]);
