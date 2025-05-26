<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../assets/conexao.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;

if (!$action) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
    exit;
}

function carregarProdutos($pdo)
{
    $categorias = $pdo->query("
        SELECT id_categoria, nome_categoria
        FROM tb_categoria
        WHERE categoria_ativa = 1
        ORDER BY ordem_exibicao
    ")->fetchAll(PDO::FETCH_ASSOC);

    $subcategorias = $pdo->query("
        SELECT sc.id_subcategoria, sc.nome_subcategoria, scc.id_categoria
        FROM tb_subcategoria sc
        JOIN tb_subcategoria_categoria scc USING(id_subcategoria)
        WHERE sc.subcategoria_ativa = 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    $produtos = $pdo->query("
        SELECT id_produto, nome_produto, id_categoria, valor_produto, qtd_sabores
        FROM tb_produto
        WHERE produto_ativo = 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmtSub = $pdo->prepare("
        SELECT id_subcategoria
        FROM tb_subcategoria_produto
        WHERE id_produto = ?
    ");
    foreach ($produtos as &$p) {
        $stmtSub->execute([$p['id_produto']]);
        $p['subcategorias'] = array_map('intval', $stmtSub->fetchAll(PDO::FETCH_COLUMN));
    }

    echo json_encode([
        'status' => 'ok',
        'categorias' => $categorias,
        'subcategorias' => $subcategorias,
        'produtos' => $produtos,
    ]);
    exit;
}

function detalharProduto($pdo, $idProduto)
{
    $stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ? AND produto_ativo = 1");
    $stmt->execute([$idProduto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado']);
        exit;
    }

    $sabores = [];
    if ((int)$produto['qtd_sabores'] > 1) {
        $stmtSabor = $pdo->prepare("
            SELECT p.id_produto, p.nome_produto, p.valor_produto,
                   COALESCE(s.nome_subcategoria, 'Outros') AS nome_subcategoria
            FROM tb_produto p
            LEFT JOIN tb_subcategoria_produto sp ON p.id_produto = sp.id_produto
            LEFT JOIN tb_subcategoria s ON sp.id_subcategoria = s.id_subcategoria
            WHERE p.produto_ativo = 1
              AND p.qtd_sabores <= 1
              AND p.nome_produto NOT LIKE '%combo%'
              AND p.id_categoria = ?
            ORDER BY s.nome_subcategoria, p.nome_produto
        ");
        $stmtSabor->execute([$produto['id_categoria']]);
        $sabores = $stmtSabor->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtTipos = $pdo->prepare("
        SELECT ta.id_tipo_adicional, ta.nome_tipo_adicional,
               pta.obrigatorio, pta.max_inclusos
        FROM tb_produto_tipo_adicional pta
        JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
        WHERE pta.id_produto = ?
    ");
    $stmtTipos->execute([$idProduto]);
    $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

    $adicionaisPorTipo = [];
    $stmtAdd = $pdo->prepare("
        SELECT id_adicional, nome_adicional, valor_adicional
        FROM tb_adicional
        WHERE id_tipo_adicional = ?
          AND adicional_ativo = 1
    ");
    foreach ($tipos as $tipo) {
        $stmtAdd->execute([$tipo['id_tipo_adicional']]);
        $adicionaisPorTipo[] = [
            'tipo' => $tipo,
            'adicionais' => $stmtAdd->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    $stmtIncl = $pdo->prepare("SELECT id_adicional FROM tb_produto_adicional_incluso WHERE id_produto = ?");
    $stmtIncl->execute([$idProduto]);
    $inclusos = array_map('intval', $stmtIncl->fetchAll(PDO::FETCH_COLUMN));

    echo json_encode([
        'status' => 'ok',
        'produto' => $produto,
        'sabores' => $sabores,
        'tipos' => $tipos,
        'adicionais' => $adicionaisPorTipo,
        'inclusos' => $inclusos
    ]);
    exit;
}

function listarPedidos($pdo)
{
    $stmt = $pdo->query("
        SELECT p.id_pedido, p.nome_cliente AS cliente, p.telefone_cliente, p.status_pedido,
               p.valor_total, p.criado_em, p.id_entregador
        FROM tb_pedido p
        ORDER BY p.criado_em DESC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

function criarPedidoBalcao($pdo, $input)
{
    $nome           = trim($input['nome_cliente'] ?? '');
    $fone           = preg_replace('/\D/', '', $input['telefone_cliente'] ?? '');
    $id_funcionario = $_SESSION['usuario']['id'] ?? null;
    $id_usuario     = $input['id_usuario'] ?? null;
    $itens          = $input['items'] ?? [];
    $entrega        = $input['tipo_entrega'] ?? 'retirada';
    $pgto           = $input['forma_pagamento'] ?? null;
    $enderecoTexto  = $entrega === 'entrega' ? trim($input['endereco'] ?? '') : null;
    $frete          = floatval($input['valor_frete'] ?? 0);
    $desconto       = floatval($input['desconto'] ?? 0);
    $dataCriacao = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');

    if (!$nome || !$fone || !is_array($itens) || empty($itens)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados incompletos']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Calcular subtotal
        $subtotal = 0;
        foreach ($itens as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        $valorTotal = max(0, $subtotal + $frete - $desconto);

        // Inserir pedido
        $stmt = $pdo->prepare("
            INSERT INTO tb_pedido
                (
            
                id_usuario,
                id_funcionario, 
                endereco, 
                nome_cliente, 
                telefone_cliente,
                valor_total, 
                tipo_entrega, 
                forma_pagamento, 
                status_pedido, 
                origem,
                valor_frete, 
                desconto_aplicado, 
                criado_em)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 'aceito', 'balcao', ?, ?, ?)
        ");
        $stmt->execute([
            $id_usuario ?: null,
            $id_funcionario ?: null,
            $enderecoTexto ?: null,
            $nome,
            $fone,
            $valorTotal,
            $entrega,
            $pgto,
            $frete,
            $desconto,
            $dataCriacao
        ]);
        $idPedido = $pdo->lastInsertId();

        // Itens do pedido
        $stmtItem  = $pdo->prepare("
            INSERT INTO tb_item_pedido (id_pedido, id_produto, nome_exibicao, quantidade, valor_unitario)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtSabor = $pdo->prepare("
            INSERT INTO tb_item_pedido_sabor (id_item_pedido, id_produto)
            VALUES (?, ?)
        ");
        $insAdd = $pdo->prepare("
            INSERT INTO tb_item_adicional
                (id_item_pedido, id_adicional, nome_adicional, valor_adicional)
            VALUES (?, ?, ?, ?)
        ");


        foreach ($itens as $item) {
            // insere item
            $stmtItem->execute([
                $idPedido,
                $item['prod']['id_produto'],
                $item['prod']['nome_produto'] ?? $item['prod']['nome'],
                $item['qty'],
                $item['price']
            ]);
            $idItem = $pdo->lastInsertId();

            // sabores (ajuste aqui)
            if (!empty($item['flavors'])) {
                foreach ($item['flavors'] as $sabor) {
                    // se veio como array com ['id'], usa-o; senão assume que $sabor já é o ID
                    $idSabor = is_array($sabor)
                        ? (isset($sabor['id']) ? intval($sabor['id']) : 0)
                        : intval($sabor);
                    if ($idSabor > 0) {
                        $stmtSabor->execute([$idItem, $idSabor]);
                    }
                }
            }

            // adicionais
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $add) {
                    // 2) agora passo também o ID do adicional
                    $insAdd->execute([
                        $idItem,
                        $add['id'],          // <-- id_adicional
                        $add['nome'],        // ou $add['nome_adicional']
                        $add['valor']        // ou $add['valor_adicional']
                    ]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'ok',
            'mensagem' => 'Pedido salvo com sucesso!',
            'id_pedido' => $idPedido
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar pedido', 'debug' => $e->getMessage()]);
    }
}

switch ($action) {
    case 'listar_produtos':
    case 'carregar_produtos':
        carregarProdutos($pdo);
        break;

    case 'detalhar_produto':
        $idProduto = (int)($input['id_produto'] ?? 0);
        detalharProduto($pdo, $idProduto);
        break;

    case 'get_pedidos':
        listarPedidos($pdo);
        break;

    case 'criar_pedido_balcao':
        criarPedidoBalcao($pdo, $input);
        break;


    default:
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação não reconhecida']);
        exit;
}
