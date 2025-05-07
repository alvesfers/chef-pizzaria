<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../assets/conexao.php';

header('Content-Type: application/json');


// read JSON or form data
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;

// extract and normalize phone
$phone = isset($data['phone'])
    ? preg_replace('/\D/', '', $data['phone'])
    : '';

if (strlen($phone) < 10) {
    echo json_encode(['exists' => false]);
    exit;
}

// try to find the user by telefone_usuario (stripped of non-digits)
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
