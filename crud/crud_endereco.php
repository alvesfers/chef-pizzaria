<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$idUsuario = $_POST['id_usuario'] ?? $_SESSION['usuario']['id'];

// Verificar ação
$acao = $_POST['acao'] ?? null;

switch ($acao) {
    case 'cadastrar':
        cadastrar($pdo, $idUsuario);
        break;

    case 'editar':
        editar($pdo, $idUsuario);
        break;

    case 'excluir':
        excluir($pdo, $idUsuario);
        break;

    case 'listar':
        listar($pdo);
        break;

    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
        break;
}

function cadastrar($pdo, $idUsuario)
{
    $cep                = preg_replace('/\D/', '', $_POST['cep'] ?? '');
    $rua                = trim($_POST['rua'] ?? '');
    $numero             = trim($_POST['numero'] ?? '');
    $complemento        = trim($_POST['complemento'] ?? '');
    $pontoReferencia    = trim($_POST['ponto_referencia'] ?? '');
    $bairro             = trim($_POST['bairro'] ?? '');
    $apelido            = trim($_POST['apelido'] ?? '');
    $enderecoPrincipal  = isset($_POST['endereco_principal']) ? 1 : 0;
    $dataCriacao = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
    
    if (!$cep || !$rua || !$numero || !$bairro || !$apelido) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_endereco WHERE id_usuario = ?");
    $stmt->execute([$idUsuario]);
    $totalEnderecos = $stmt->fetchColumn();
    if ($totalEnderecos == 0) {
        $enderecoPrincipal = 1;
    }

    if ($enderecoPrincipal == 1) {
        $pdo->prepare("UPDATE tb_endereco SET endereco_principal = 0 WHERE id_usuario = ?")->execute([$idUsuario]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_endereco 
        (id_usuario, apelido, cep, rua, numero, complemento, bairro, ponto_de_referencia, endereco_principal, endereco_ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
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
        $enderecoPrincipal,
        $dataCriacao
    ]);

    echo json_encode(['status' => 'ok', 'mensagem' => 'Endereço cadastrado com sucesso.']);
    exit;
}

function editar($pdo, $idUsuario)
{
    $idEndereco         = $_POST['id_endereco'] ?? null;
    $cep                = preg_replace('/\D/', '', $_POST['cep'] ?? '');
    $rua                = trim($_POST['rua'] ?? '');
    $numero             = trim($_POST['numero'] ?? '');
    $complemento        = trim($_POST['complemento'] ?? '');
    $pontoReferencia    = trim($_POST['ponto_referencia'] ?? '');
    $bairro             = trim($_POST['bairro'] ?? '');
    $apelido            = trim($_POST['apelido'] ?? '');

    if (!$idEndereco || !$cep || !$rua || !$numero || !$bairro || !$apelido) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tb_endereco 
        SET apelido = ?, cep = ?, rua = ?, numero = ?, complemento = ?, bairro = ?, ponto_de_referencia = ?
        WHERE id_endereco = ? AND id_usuario = ?");
    $stmt->execute([
        $apelido,
        $cep,
        $rua,
        $numero,
        $complemento,
        $bairro,
        $pontoReferencia,
        $idEndereco,
        $idUsuario
    ]);

    echo json_encode(['status' => 'ok', 'mensagem' => 'Endereço atualizado com sucesso.']);
    exit;
}

function excluir($pdo, $idUsuario)
{
    $idEndereco = $_POST['id_endereco'] ?? null;

    if (!$idEndereco) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do endereço não informado.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tb_endereco SET endereco_ativo = 0 WHERE id_endereco = ? AND id_usuario = ?");
    $stmt->execute([$idEndereco, $idUsuario]);

    echo json_encode(['status' => 'ok', 'mensagem' => 'Endereço desativado com sucesso.']);
    exit;
}

function listar($pdo)
{
    $idUsuario = $_POST['id_usuario'];

    if (!$idUsuario) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do usuário não informado.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_endereco, rua, numero, bairro, cep, apelido
        FROM tb_endereco
        WHERE id_usuario = ? AND endereco_ativo = 1
        ORDER BY endereco_principal DESC, criado_em DESC
    ");
    $stmt->execute([$idUsuario]);
    $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'ok', 'enderecos' => $enderecos]);
    exit;
}
