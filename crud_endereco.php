<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$idUsuario = $_SESSION['usuario']['id'];

// Verificar ação
$acao = $_POST['acao'] ?? null;

if ($acao !== 'cadastrar') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
    exit;
}

// Receber dados do formulário
$cep                = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$rua                = trim($_POST['rua'] ?? '');
$numero             = trim($_POST['numero'] ?? '');
$complemento        = trim($_POST['complemento'] ?? '');
$pontoReferencia    = trim($_POST['ponto_referencia'] ?? '');
$bairro             = trim($_POST['bairro'] ?? '');
$apelido            = trim($_POST['apelido'] ?? '');
$enderecoPrincipal  = isset($_POST['endereco_principal']) ? 1 : 0;

// Validação básica
if (!$cep || !$rua || !$numero || !$bairro || !$apelido) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// Se for o primeiro endereço do usuário, forçamos como principal
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_endereco WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$totalEnderecos = $stmt->fetchColumn();
if ($totalEnderecos == 0) {
    $enderecoPrincipal = 1;
}

// Se novo endereço for principal, limpar os anteriores
if ($enderecoPrincipal == 1) {
    $pdo->prepare("UPDATE tb_endereco SET endereco_principal = 0 WHERE id_usuario = ?")->execute([$idUsuario]);
}

// Inserir novo endereço
$stmt = $pdo->prepare("
    INSERT INTO tb_endereco 
    (id_usuario, apelido, cep, rua, numero, complemento, bairro, ponto_de_referencia, endereco_principal, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $idUsuario,
    $apelido,
    $cep,
    $rua,
    $numero,
    $complemento,
    $bairro,
    $pontoReferencia,
    $enderecoPrincipal
]);

echo json_encode(['status' => 'ok', 'mensagem' => 'Endereço cadastrado com sucesso.']);
exit;
