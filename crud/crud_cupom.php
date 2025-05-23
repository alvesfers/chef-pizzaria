<?php
// crud/crud_cupom.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../assets/conexao.php';


// lê JSON ou POST
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;
$action = $data['action'] ?? '';

// validação de data
function validarData($d)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

try {
    switch ($action) {
        case 'listar':
            $rows = $pdo->query("SELECT * FROM tb_cupom ORDER BY id_cupom DESC")
                ->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'ok', 'cupoms' => $rows]);
            break;

        case 'cadastrar':
            // campos obrigatórios
            foreach (['codigo', 'tipo', 'valor'] as $f) {
                if (empty($data[$f])) {
                    throw new Exception("Campo {$f} é obrigatório.");
                }
            }
            $codigo       = trim($data['codigo']);
            $descricao    = trim($data['descricao'] ?? '');
            $tipo         = in_array($data['tipo'], ['porcentagem', 'fixo']) ? $data['tipo'] : throw new Exception('Tipo inválido.');
            $valor        = floatval($data['valor']);
            $uso_unico    = !empty($data['uso_unico']) ? 1 : 0;
            $quantidade_usos = isset($data['quantidade_usos']) ? intval($data['quantidade_usos']) : null;
            $minimo_pedido   = isset($data['minimo_pedido']) ? floatval($data['minimo_pedido']) : 0;
            $valido_de    = $data['valido_de'] ?? date('Y-m-d');
            $valido_ate   = $data['valido_ate'] ?? null;
            $cupom_ativo  = !empty($data['cupom_ativo']) ? 1 : 0;

            if (!validarData($valido_de)) throw new Exception('Data válido_de inválida.');
            if ($valido_ate && !validarData($valido_ate)) throw new Exception('Data válido_ate inválida.');

            // código único
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_cupom WHERE codigo = ?");
            $stmt->execute([$codigo]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Código já existe.');
            }

            $ins = $pdo->prepare("
                INSERT INTO tb_cupom
                  (codigo, descricao, tipo, valor, uso_unico, quantidade_usos, minimo_pedido, valido_de, valido_ate, cupom_ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $codigo,
                $descricao,
                $tipo,
                $valor,
                $uso_unico,
                $quantidade_usos,
                $minimo_pedido,
                $valido_de,
                $valido_ate,
                $cupom_ativo
            ]);
            echo json_encode(['status' => 'ok', 'mensagem' => 'Cupom cadastrado com sucesso.']);
            break;

        case 'obter':
            $id = intval($data['id_cupom'] ?? 0);
            if (!$id) throw new Exception('ID inválido.');
            $stmt = $pdo->prepare("SELECT * FROM tb_cupom WHERE id_cupom = ?");
            $stmt->execute([$id]);
            $cupom = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cupom) throw new Exception('Cupom não encontrado.');
            echo json_encode(['status' => 'ok', 'cupom' => $cupom]);
            break;

        case 'atualizar':
            $id = intval($data['id_cupom'] ?? 0);
            if (!$id) throw new Exception('ID inválido.');
            // recolher campos (sem repetir validações completas)
            $fields = [];
            $params = [];
            foreach (['codigo', 'descricao', 'tipo', 'valor', 'uso_unico', 'quantidade_usos', 'minimo_pedido', 'valido_de', 'valido_ate', 'cupom_ativo'] as $f) {
                if (isset($data[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = ($f === 'valor' || $f === 'minimo_pedido') ? floatval($data[$f])
                        : ($f === 'uso_unico' || $f === 'cupom_ativo' ? (int)$data[$f]
                            : ($f === 'quantidade_usos' ? intval($data[$f]) : trim($data[$f])));
                }
            }
            if (empty($fields)) throw new Exception('Nenhum campo para atualizar.');
            $params[] = $id;
            $sql = "UPDATE tb_cupom SET " . implode(', ', $fields) . " WHERE id_cupom = ?";
            $pdo->prepare($sql)->execute($params);
            echo json_encode(['status' => 'ok', 'mensagem' => 'Cupom atualizado.']);
            break;

        case 'deletar':
            $id = intval($data['id_cupom'] ?? 0);
            if (!$id) throw new Exception('ID inválido.');
            // apenas desativa
            $pdo->prepare("UPDATE tb_cupom SET cupom_ativo = 0 WHERE id_cupom = ?")
                ->execute([$id]);
            echo json_encode(['status' => 'ok', 'mensagem' => 'Cupom desativado.']);
            break;

        case 'validar':
            // usado na finalização de pedido
            $codigo   = trim($data['codigo'] ?? '');
            $subtotal = floatval($data['subtotal'] ?? 0);
            if ($codigo === '') throw new Exception('Código de cupom obrigatório.');

            $stmt = $pdo->prepare("
                SELECT *
                  FROM tb_cupom
                 WHERE codigo = ?
                   AND cupom_ativo = 1
                   AND valido_de <= CURDATE()
                   AND (valido_ate IS NULL OR valido_ate >= CURDATE())
                   AND (quantidade_usos IS NULL OR quantidade_usos > 0)
                   AND minimo_pedido <= ?
            ");
            $stmt->execute([$codigo, $subtotal]);
            $cup = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cup) {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Cupom inválido ou não aplicável.']);
                break;
            }
            // calcula desconto
            if ($cup['tipo'] === 'porcentagem') {
                $desconto = $subtotal * ($cup['valor'] / 100);
            } else {
                $desconto = min($subtotal, floatval($cup['valor']));
            }
            // decrementa uso
            if ($cup['uso_unico'] || ($cup['quantidade_usos'] !== null && $cup['quantidade_usos'] <= 1)) {
                $pdo->prepare("UPDATE tb_cupom SET cupom_ativo = 0 WHERE id_cupom = ?")
                    ->execute([$cup['id_cupom']]);
            } elseif ($cup['quantidade_usos'] !== null) {
                $pdo->prepare("UPDATE tb_cupom SET quantidade_usos = quantidade_usos - 1 WHERE id_cupom = ?")
                    ->execute([$cup['id_cupom']]);
            }
            echo json_encode([
                'status'   => 'ok',
                'desconto' => round($desconto, 2)
            ]);
            break;

        default:
            echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
