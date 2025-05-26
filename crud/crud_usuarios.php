<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'admin') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'listar_usuarios':
        // só admins ativos
        $stmt = $pdo->prepare("
      SELECT id_usuario, nome_usuario, telefone_usuario, tipo_usuario, usuario_ativo, criado_em
        FROM tb_usuario
       WHERE tipo_usuario = 'admin'
       ORDER BY nome_usuario
    ");
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'sucesso', 'dados' => $dados]);
        break;

    case 'get_usuario':
        $id = intval($_POST['id_usuario']);
        $stmt = $pdo->prepare("
      SELECT id_usuario, nome_usuario, telefone_usuario, tipo_usuario, usuario_ativo
        FROM tb_usuario
       WHERE id_usuario = ?
    ");
        $stmt->execute([$id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            echo json_encode(['status' => 'sucesso', 'dados' => $u]);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não encontrado']);
        }
        break;

    case 'salvar_usuario':
        $id       = intval($_POST['id_usuario']);
        $nome     = trim($_POST['nome_usuario'] ?? '');
        $tel      = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        $tipo     = $_POST['tipo_usuario'] ?? '';
        $ativo    = intval($_POST['usuario_ativo'] ?? 0);
        $senha    = $_POST['senha_usuario'] ?? '';

        if (!$nome || !$tel || !$tipo || (!$id && !$senha)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha todos os campos obrigatórios']);
            exit;
        }

        // edição
        if ($id) {
            $params = [$nome, $tel, $tipo, $ativo];
            $sql = "UPDATE tb_usuario
                 SET nome_usuario=?, telefone_usuario=?, tipo_usuario=?, usuario_ativo=?";
            if ($senha) {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql .= ", senha_usuario=?";
                $params[] = $hash;
            }
            $sql .= " WHERE id_usuario=?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                echo json_encode(['status' => 'sucesso', 'mensagem' => 'Usuário atualizado']);
            } else {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Falha ao atualizar']);
            }
        }
        // novo
        else {
            // não duplica telefone
            $chk = $pdo->prepare("SELECT COUNT(*) FROM tb_usuario WHERE telefone_usuario = ?");
            $chk->execute([$tel]);
            if ($chk->fetchColumn() > 0) {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Telefone já cadastrado']);
                exit;
            }
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
        INSERT INTO tb_usuario 
          (nome_usuario, telefone_usuario, senha_usuario, tipo_usuario, usuario_ativo, criado_em)
        VALUES (?,?,?,?,?,NOW())
      ");
            if ($stmt->execute([$nome, $tel, $hash, $tipo, $ativo])) {
                echo json_encode(['status' => 'sucesso', 'mensagem' => 'Usuário criado']);
            } else {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Falha ao criar usuário']);
            }
        }
        break;

    case 'deletar_usuario':
        // marca inativo
        $id = intval($_POST['id_usuario']);
        $stmt = $pdo->prepare("UPDATE tb_usuario SET usuario_ativo = 0 WHERE id_usuario = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['status' => 'sucesso', 'mensagem' => 'Usuário inativado']);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Falha ao inativar']);
        }
        break;

    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
        break;
}
