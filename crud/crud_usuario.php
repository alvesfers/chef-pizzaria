<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../assets/conexao.php';

header('Content-Type: application/json');

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

    case 'buscar_por_telefone':
        buscarPorTelefone($pdo);
        break;

    case 'atualizar_usuario':
        atualizarUsuario($pdo);
        break;

    case 'desativar':
        desativarConta($pdo);
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

    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome_usuario, telefone_usuario, senha_usuario, tipo_usuario) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $telefone, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
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

    $stmt = $pdo->prepare("INSERT INTO tb_usuario (nome_usuario, telefone_usuario, senha_usuario, tipo_usuario) VALUES (?, ?, ?, 'cliente')");
    $stmt->execute([$nome, $telefone, password_hash($senha, PASSWORD_DEFAULT)]);
    $idUsuario = $pdo->lastInsertId();

    $_SESSION['usuario'] = [
        'id' => $idUsuario,
        'nome' => $nome,
        'telefone' => $telefone,
        'tipo_usuario' => 'cliente'
    ];

    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Conta criada e login realizado com sucesso.',
        'redirect' => 'finalizar_pedido.php'
    ]);
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

    if (!$usuario || !password_verify($senha, $usuario['senha_usuario'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone ou senha inválidos.']);
        return;
    }

    $_SESSION['usuario'] = [
        'id' => $usuario['id_usuario'],
        'nome' => $usuario['nome_usuario'],
        'telefone' => $usuario['telefone_usuario'],
        'tipo_usuario' => $usuario['tipo_usuario']
    ];

    echo json_encode(['status' => 'ok', 'mensagem' => 'Login realizado com sucesso!']);
}

function buscarPorTelefone($pdo)
{
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone inválido']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE telefone_usuario = ?");
    $stmt->execute([$telefone]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $_SESSION['usuario'] = [
            'id' => $usuario['id_usuario'],
            'nome' => $usuario['nome_usuario'],
            'telefone' => $usuario['telefone_usuario'],
            'tipo_usuario' => $usuario['tipo_usuario']
        ];
        echo json_encode(['status' => 'ok', 'usuario' => $usuario]);
    } else {
        echo json_encode(['status' => 'nao_encontrado']);
    }
}

function atualizarUsuario($pdo)
{
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
        return;
    }

    $idUsuario = $_SESSION['usuario']['id'];
    $nome = trim($_POST['nome'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';

    if (!$nome || !$telefone) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Nome e telefone são obrigatórios.']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE tb_usuario SET nome_usuario = ?, telefone_usuario = ? WHERE id_usuario = ?");
    $stmt->execute([$nome, $telefone, $idUsuario]);
    $_SESSION['usuario']['nome'] = $nome;
    $_SESSION['usuario']['telefone'] = $telefone;

    if ($senhaAtual && $novaSenha) {
        $stmt = $pdo->prepare("SELECT senha_usuario FROM tb_usuario WHERE id_usuario = ?");
        $stmt->execute([$idUsuario]);
        $senhaHash = $stmt->fetchColumn();

        if (!password_verify($senhaAtual, $senhaHash)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Senha atual incorreta.']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE tb_usuario SET senha_usuario = ? WHERE id_usuario = ?");
        $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $idUsuario]);
    }

    echo json_encode(['status' => 'ok', 'mensagem' => 'Dados atualizados com sucesso.']);
}

function desativarConta($pdo)
{
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
        return;
    }

    $idUsuario = $_SESSION['usuario']['id'];
    $stmt = $pdo->prepare("UPDATE tb_usuario SET usuario_ativo = 0 WHERE id_usuario = ?");
    $stmt->execute([$idUsuario]);

    unset($_SESSION['usuario']);

    echo json_encode(['status' => 'ok', 'mensagem' => 'Conta desativada com sucesso.']);
}
