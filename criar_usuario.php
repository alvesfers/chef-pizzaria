<?php
session_start();
require_once 'conexao.php';

$nome = trim($_POST['nome'] ?? '');
$telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
$redirect = $_POST['redirect'] ?? 'index.php';

if (!$nome || !$telefone) {
    $_SESSION['erro'] = 'Preencha todos os campos.';
    header("Location: carrinho.php");
    exit;
}

// Verifica se usuário já existe
$stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE telefone = ?");
$stmt->execute([$telefone]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    // Cria novo usuário
    $senha = password_hash($telefone, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome, telefone, senha, tipo_usuario) VALUES (?, ?, ?, 'cliente')");
    $stmt->execute([$nome, $telefone, $senha]);

    // Busca o novo usuário
    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE telefone = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['mensagem'] = 'Cadastro criado com sucesso!';
}

$_SESSION['usuario'] = [
    'id' => $usuario['id_usuario'],
    'nome' => $usuario['nome'],
    'telefone' => $usuario['telefone'],
    'tipo' => $usuario['tipo_usuario']
];

header("Location: $redirect");
exit;
