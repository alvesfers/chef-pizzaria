<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tb_tipo_adicional WHERE id_tipo_adicional = ?");
    $stmt->execute([$id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $t
        ? json_encode(['status' => 'ok', 'tipoAdicional' => $t])
        : json_encode(['status' => 'erro', 'mensagem' => 'Não encontrado']);
    exit;
}

// JSON input
$in = json_decode(file_get_contents('php://input'), true);
if (!$in) exit(json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']));

// DELETE lógico
if (!empty($in['action']) && $in['action'] === 'delete') {
    $id = intval($in['id_tipo_adicional']);
    $pdo->prepare("UPDATE tb_tipo_adicional SET tipo_ativo = 0 WHERE id_tipo_adicional = ?")
        ->execute([$id]);
    // opcional: clean relacionamentos
    echo json_encode(['status' => 'ok']);
    exit;
}

// validar
$nome = trim($in['nome_tipo_adicional'] ?? '');
if ($nome === '') exit(json_encode(['status' => 'erro', 'mensagem' => 'Nome obrigatório']));

$data = [
    'nome_tipo_adicional' => $nome,
    'obrigatorio'       => intval($in['obrigatorio'] ?? 0),
    'multipla_escolha'  => intval($in['multipla_escolha'] ?? 0),
    'max_escolha'       => intval($in['max_escolha'] ?? 0),
    'tipo_ativo'        => intval($in['tipo_ativo'] ?? 1),
];

if (!empty($in['id_tipo_adicional'])) {
    $id = intval($in['id_tipo_adicional']);
    $sets = implode(', ', array_map(fn($c) => "$c=:$c", array_keys($data)));
    $stmt = $pdo->prepare("UPDATE tb_tipo_adicional SET $sets WHERE id_tipo_adicional = :id");
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols = implode(', ', array_keys($data));
    $ph   = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $stmt = $pdo->prepare("INSERT INTO tb_tipo_adicional ($cols) VALUES ($ph)");
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}
$data['id_tipo_adicional'] = $id;
echo json_encode(['status' => 'ok', 'tipoAdicional' => $data]);
