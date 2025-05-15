<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../assets/conexao.php';

header('Content-Type: application/json');

$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;
$action = $data['action'] ?? null;

// Ação: Buscar por telefone
if ($action === 'get_by_phone') {
    $phone = isset($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : '';
    if (strlen($phone) < 10) {
        echo json_encode(['exists' => false]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_usuario, nome_usuario 
        FROM tb_usuario 
        WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone_usuario,' ',''),'-',''),'(',''),')','') = ? 
        LIMIT 1
    ");
    $stmt->execute([$phone]);

    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'exists' => true,
            'id'     => (int)$user['id_usuario'],
            'name'   => $user['nome_usuario']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}

// Ação: Criar novo usuário
if ($action === 'create_user') {
    $phone = isset($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : '';
    $name = trim($data['name'] ?? '');

    if (strlen($phone) < 10 || strlen($name) < 2) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Nome ou telefone inválido']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome_usuario, telefone_usuario, senha_usuario, usuario_ativo) VALUES (?, ?, ?, 1)");
    $senha = password_hash($phone, PASSWORD_DEFAULT); // senha padrão: o próprio telefone

    if ($stmt->execute([$name, $phone, $senha])) {
        $id = $pdo->lastInsertId();
        echo json_encode(['status' => 'ok', 'id' => (int)$id]);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar no banco']);
    }
    exit;
}

// Qualquer outra ação inválida
echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
exit;
