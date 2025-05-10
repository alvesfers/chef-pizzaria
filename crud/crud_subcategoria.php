<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// GET por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tb_subcategoria WHERE id_subcategoria = ?");
    $stmt->execute([$id]);
    $sc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sc) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Subcategoria não encontrada']);
        exit;
    }
    echo json_encode(['status' => 'ok', 'subcategoria' => $sc]);
    exit;
}

// lê JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']);
    exit;
}

// DELETE lógico + cleanup
if (!empty($input['action']) && $input['action'] === 'delete') {
    $id = intval($input['id_subcategoria']);
    // inativa
    $pdo->prepare("UPDATE tb_subcategoria 
                     SET subcategoria_ativa = 0 
                   WHERE id_subcategoria = ?")
        ->execute([$id]);
    // remove relações
    $pdo->prepare("DELETE FROM tb_subcategoria_categoria WHERE id_subcategoria = ?")
        ->execute([$id]);
    $pdo->prepare("DELETE FROM tb_subcategoria_produto   WHERE id_subcategoria = ?")
        ->execute([$id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// valida nome
$nome = trim($input['nome_subcategoria'] ?? '');
if ($nome === '') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Nome obrigatório']);
    exit;
}

// UPDATE
if (!empty($input['id_subcategoria'])) {
    $id   = intval($input['id_subcategoria']);
    $stmt = $pdo->prepare("
        UPDATE tb_subcategoria
           SET nome_subcategoria = ?
         WHERE id_subcategoria = ?
    ");
    $stmt->execute([$nome, $id]);
    echo json_encode([
        'status'       => 'ok',
        'subcategoria' => ['id_subcategoria' => $id, 'nome_subcategoria' => $nome]
    ]);
    exit;
}

// INSERT
$stmt = $pdo->prepare("
    INSERT INTO tb_subcategoria 
      (nome_subcategoria, tipo_subcategoria, subcategoria_ativa)
    VALUES (?, '', 1)
");
$stmt->execute([$nome]);
$id = $pdo->lastInsertId();
echo json_encode([
    'status'       => 'ok',
    'subcategoria' => ['id_subcategoria' => $id, 'nome_subcategoria' => $nome]
]);
