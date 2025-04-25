<?php
session_start();
require_once 'conexao.php';

$acao = $_POST['acao'] ?? null;

if (!$acao) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação não informada.']);
    exit;
}

switch ($acao) {
    case 'cadastrar':
        cadastrar($pdo);
        break;

    case 'cadastrar_e_logar':
        cadastrarELogar($pdo);
        break;

    case 'login':
        login($pdo);
        break;


    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
        break;
}

function usuarioExiste($pdo, $telefone)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_usuario WHERE telefone_usuario = ?");
    $stmt->execute([$telefone]);
    return $stmt->fetchColumn() > 0;
}

function cadastrar($pdo)
{
    $nome = trim($_POST['nome'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo_usuario'] ?? 'cliente';

    if (!$nome || !$telefone || !$senha) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
        return;
    }

    if (usuarioExiste($pdo, $telefone)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone já cadastrado.']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome, telefone_usuario, senha, tipo_usuario) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $telefone, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
    $idUsuario = $pdo->lastInsertId();

    if ($tipo === 'funcionario') {
        $funcao = $_POST['funcao'] ?? '';
        $forma_pgto = $_POST['forma_pagamento'] ?? '';
        $valor_pgto = floatval($_POST['valor_pagamento'] ?? 0.00);

        $stmt = $pdo->prepare("INSERT INTO tb_funcionario (id_usuario, funcao, forma_pagamento, valor_pagamento) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idUsuario, $funcao, $forma_pgto, $valor_pgto]);
    }

    echo json_encode(['status' => 'ok', 'mensagem' => 'Usuário cadastrado com sucesso.']);
}

function cadastrarELogar($pdo)
{
    $nome = trim($_POST['nome'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$telefone || !$senha) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
        return;
    }

    if (usuarioExiste($pdo, $telefone)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone já cadastrado.']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome, telefone_usuario, senha, tipo_usuario) VALUES (?, ?, ?, 'cliente')");
    $stmt->execute([$nome, $telefone, password_hash($senha, PASSWORD_DEFAULT)]);
    $idUsuario = $pdo->lastInsertId();

    $_SESSION['usuario'] = [
        'id' => $idUsuario,
        'nome' => $nome,
        'telefone' => $telefone,
        'tipo_usuario' => 'cliente'
    ];

    echo json_encode(['status' => 'ok', 'mensagem' => 'Conta criada e login realizado com sucesso.']);
}

function login($pdo)
{
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$telefone || !$senha) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE telefone_usuario = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone ou senha inválidos.']);
        return;
    }

    // Logar
    $_SESSION['usuario'] = [
        'id' => $usuario['id_usuario'],
        'nome' => $usuario['nome'],
        'telefone' => $usuario['telefone'],
        'tipo_usuario' => $usuario['tipo_usuario']
    ];

    echo json_encode(['status' => 'ok', 'mensagem' => 'Login realizado com sucesso!']);
}
