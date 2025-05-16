<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once __DIR__ . '/../assets/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'salvar_logo') {
    $destino = __DIR__ . '/../assets/images/logo';
    if (!empty($_FILES['file']['tmp_name'])) {
        move_uploaded_file($_FILES['file']['tmp_name'], $destino);
        echo json_encode(['status' => 'ok', 'mensagem' => 'Logo atualizada']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Arquivo inválido']);
    }
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? $_GET['action'] ?? null;

switch ($action) {
    case 'get_dados_loja':
        $loja = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $horarios = $pdo->query("SELECT * FROM tb_horario_atendimento ORDER BY FIELD(dia_semana, 'segunda','terça','quarta','quinta','sexta','sábado','domingo')")->fetchAll(PDO::FETCH_ASSOC);
        $regras = $pdo->query("SELECT * FROM tb_regras_frete ORDER BY id_regra DESC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'ok',
            'dados_loja' => $loja,
            'horarios' => $horarios,
            'regras_frete' => $regras
        ]);
        break;

    case 'salvar_dados':
        $dados = $input['dados'] ?? [];
        $campos = [
            'nome_loja',
            'cep',
            'endereco_completo',
            'tema',
            'instagram',
            'whatsapp',
            'google',
            'preco_base',
            'preco_km',
            'limite_entrega',
            'tempo_entrega',
            'tempo_retirada',
            'usar_horarios'
        ];

        $set = [];
        $valores = [];
        foreach ($campos as $c) {
            $set[] = "$c = ?";
            $valores[] = isset($dados[$c]) ? trim($dados[$c]) : null;

        }

        $pdo->prepare("UPDATE tb_dados_loja SET " . implode(', ', $set) . " WHERE id_loja = 1")->execute($valores);
        echo json_encode(['status' => 'ok', 'mensagem' => 'Dados salvos com sucesso']);
        break;

    case 'salvar_horarios':
        $horarios = $input['horarios'] ?? [];
        $stmt = $pdo->prepare("
            UPDATE tb_horario_atendimento
               SET hora_abertura = ?, hora_fechamento = ?, ativo = ?
             WHERE id_horario = ?
        ");
        foreach ($horarios as $h) {
            $stmt->execute([
                $h['hora_abertura'],
                $h['hora_fechamento'],
                $h['ativo'],
                $h['id_horario']
            ]);
        }
        echo json_encode(['status' => 'ok', 'mensagem' => 'Horários atualizados']);
        break;

    case 'salvar_regras':
        $regras = $input['regras'] ?? [];
        $stmtInsert = $pdo->prepare("
            INSERT INTO tb_regras_frete (
                nome_regra, tipo_regra, valor_minimo, distancia_maxima,
                valor_desconto, dia_semana, ativo, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) 
        ");
        $stmtUpdate = $pdo->prepare("
            UPDATE tb_regras_frete SET
                nome_regra = ?, tipo_regra = ?, valor_minimo = ?, distancia_maxima = ?,
                valor_desconto = ?, dia_semana = ?, ativo = ?, updated_at = NOW()
            WHERE id_regra = ?
        ");
        foreach ($regras as $r) {
            $ativo = isset($r['ativo']) && $r['ativo'] ? 1 : 0;
            if (!empty($r['id_regra'])) {
                $stmtUpdate->execute([
                    $r['nome_regra'],
                    $r['tipo_regra'],
                    $r['valor_minimo'],
                    $r['distancia_maxima'],
                    $r['valor_desconto'],
                    $r['dia_semana'],
                    $ativo,
                    $r['id_regra']
                ]);
            } else {
                $stmtInsert->execute([
                    $r['nome_regra'],
                    $r['tipo_regra'],
                    $r['valor_minimo'],
                    $r['distancia_maxima'],
                    $r['valor_desconto'],
                    $r['dia_semana'],
                    $ativo
                ]);
            }
        }
        echo json_encode(['status' => 'ok', 'mensagem' => 'Regras salvas com sucesso']);
        break;

    case 'salvar_tema':
        $tema = $_POST['tema'] ?? '';
        $temasValidos = [
            'light',
            'dark',
            'cupcake',
            'bumblebee',
            'emerald',
            'corporate',
            'synthwave',
            'retro',
            'cyberpunk',
            'valentine',
            'halloween',
            'garden',
            'forest',
            'aqua',
            'lofi',
            'pastel',
            'fantasy',
            'wireframe',
            'black',
            'luxury',
            'dracula',
            'cmyk',
            'autumn',
            'business',
            'acid',
            'lemonade',
            'night',
            'coffee',
            'winter',
            'dim',
            'nord',
            'sunset',
            'caramellatte',
            'abyss',
            'silk'
        ];

        if (!in_array($tema, $temasValidos)) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Tema inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tb_dados_loja SET tema = ? LIMIT 1");
        $success = $stmt->execute([$tema]);

        echo json_encode(['status' => $success ? 'ok' : 'erro']);
        exit;
    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
}
