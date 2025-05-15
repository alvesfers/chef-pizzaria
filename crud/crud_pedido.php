<?php
// crud/crud_pedido.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../assets/conexao.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;

$action = $data['action'] ?? $data['acao'] ?? '';

$u      = $_SESSION['usuario'];
$idUser = $u['id'] ?? $u['id_usuario'] ?? null;

function registrarLogStatus($pdo, $idPedido, $antigo, $novo, $motivo = null)
{
    $pdo->prepare("
      INSERT INTO tb_pedido_status_log
        (id_pedido, status_anterior, status_novo, motivo)
      VALUES (?, ?, ?, ?)
    ")->execute([$idPedido, $antigo, $novo, $motivo]);
}

// 1) GET_PENDENTES (painel atendimento)
if ($action === 'get_pendentes') {
    $orders = $pdo->query("
      SELECT p.*, COALESCE(u.nome_usuario, p.nome_cliente) AS cliente
        FROM tb_pedido p
        LEFT JOIN tb_usuario u USING(id_usuario)
       ORDER BY p.criado_em DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'ok', 'pedidos' => $orders]);
    exit;
}

// 2) ATUALIZAR_STATUS (painel atendimento)
if ($action === 'atualizar_status') {
    $id   = $data['id_pedido']     ?? null;
    $novo = $data['status_pedido'] ?? null;
    if (!$id || !$novo) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Parâmetros inválidos.']);
        exit;
    }

    // captura status antigo
    $antigo = $pdo
        ->prepare("SELECT status_pedido FROM tb_pedido WHERE id_pedido = ?")
        ->execute([$id])
        ? $pdo->lastInsertId() /* dummy */
        : null;
    $stmt = $pdo->prepare("SELECT status_pedido FROM tb_pedido WHERE id_pedido = ?");
    $stmt->execute([$id]);
    $antigo = $stmt->fetchColumn();

    // atualiza
    $pdo->prepare("UPDATE tb_pedido SET status_pedido = ? WHERE id_pedido = ?")
        ->execute([$novo, $id]);

    registrarLogStatus($pdo, $id, $antigo, $novo);

    echo json_encode(['status' => 'ok']);
    exit;
}

// 3) ATRIBUIR_ENTREGADOR (painel atendimento)
if ($action === 'atribuir_entregador') {
    $id  = $data['id_pedido']      ?? null;
    $ent = $data['id_entregador']  ?? null;
    if (!$id) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do pedido não informado.']);
        exit;
    }
    $pdo->prepare("UPDATE tb_pedido SET id_entregador = ? WHERE id_pedido = ?")
        ->execute([$ent, $id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// 4) CANCELAR (fluxo cliente)
if ($action === 'cancelar') {
    $id  = $data['id_pedido'] ?? null;
    $mot = $data['motivo']    ?? 'Cancelado pelo cliente.';
    if (!$id) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do pedido não informado.']);
        exit;
    }

    $q = $pdo->prepare("
      SELECT status_pedido
        FROM tb_pedido
       WHERE id_pedido = ? AND id_usuario = ?
    ");
    $q->execute([$id, $idUser]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Pedido não encontrado.']);
        exit;
    }
    if ($row['status_pedido'] !== 'pendente') {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Não é possível cancelar.']);
        exit;
    }

    registrarLogStatus($pdo, $id, $row['status_pedido'], 'cancelado', $mot);
    $pdo->prepare("
      UPDATE tb_pedido
         SET status_pedido = 'cancelado',
             cancelado_em = NOW(),
             motivo_cancelamento = ?
       WHERE id_pedido = ?
    ")->execute([$mot, $id]);

    echo json_encode(['status' => 'ok', 'mensagem' => 'Pedido cancelado com sucesso.']);
    exit;
}

// 5) CRIAR_PEDIDO_BALCAO (fluxo admin via JSON)
if ($action === 'criar_pedido_balcao') {
    $items       = $data['items']            ?? [];
    $nomeCliente = $data['nome_cliente']     ?? $u['nome_usuario'];
    $telCliente  = $data['telefone_cliente'] ?? $u['telefone_usuario'];
    $tipoEntrega = $data['tipo_entrega']     ?? 'retirada';
    $formaPgto   = $data['forma_pagamento']  ?? '';
    $valorFrete  = ($tipoEntrega == 'entrega') ? floatval($data['valor_frete']) : 0;

    if (empty($items)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Carrinho vazio.']);
        exit;
    }

    // monta endereço
    $endereco = 'Retirada na loja';
    if ($tipoEntrega === 'entrega' && !empty($data['id_endereco'])) {
        $r = $pdo->prepare("
          SELECT rua, numero, bairro
            FROM tb_endereco
           WHERE id_endereco = ? AND id_usuario = ?
        ");
        $r->execute([$data['id_endereco'], $idUser]);
        if ($e = $r->fetch(PDO::FETCH_ASSOC)) {
            $endereco = "{$e['rua']}, {$e['numero']} - {$e['bairro']}";
        }
    }

    // soma produtos
    $valorProd = 0;
    foreach ($items as $it) {
        $valorProd += ($it['price'] ?? 0) * ($it['qty'] ?? 1);
    }

    try {
        $pdo->beginTransaction();

        // insere pedido
        $ins = $pdo->prepare("
          INSERT INTO tb_pedido (
            id_usuario, nome_cliente, telefone_cliente, endereco,
            tipo_entrega, forma_pagamento, valor_total, valor_frete,
            status_pedido, criado_em
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
        ");
        $ins->execute([
            $idUser,
            $nomeCliente,
            $telCliente,
            $endereco,
            $tipoEntrega,
            $formaPgto,
            $valorProd,
            $valorFrete
        ]);
        $idPedido = $pdo->lastInsertId();

        // statements para iteração
        $insItem  = $pdo->prepare("
          INSERT INTO tb_item_pedido
            (id_pedido, id_produto, nome_exibicao, quantidade, valor_unitario)
          VALUES (?, ?, ?, ?, ?)
        ");
        $insSabor = $pdo->prepare("
          INSERT INTO tb_item_pedido_sabor
            (id_item_pedido, id_produto, proporcao)
          VALUES (?, ?, ?)
        ");
        $insAdd   = $pdo->prepare("
          INSERT INTO tb_item_adicional
            (id_item_pedido, id_adicional, nome_adicional, valor_adicional)
          VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $it) {
            // insere item
            $insItem->execute([
                $idPedido,
                $it['prod']['id_produto'] ?? null,
                $it['prod']['nome']        ?? '',
                $it['qty']                 ?? 1,
                $it['price']               ?? 0
            ]);
            $idItem = $pdo->lastInsertId();

            // sabores
            if (!empty($it['flavorId'])) {
                $insSabor->execute([$idItem, $it['flavorId'], 100]);
            }

            // adicionais
            foreach ($it['addonsIds'] ?? [] as $aid) {
                $r = $pdo->prepare("
                  SELECT nome_adicional, valor_adicional
                    FROM tb_adicional
                   WHERE id_adicional = ?
                ");
                $r->execute([$aid]);
                if ($ad = $r->fetch(PDO::FETCH_ASSOC)) {
                    $insAdd->execute([
                        $idItem,
                        $aid,
                        $ad['nome_adicional'],
                        $ad['valor_adicional']
                    ]);
                }
            }
        }

        registrarLogStatus($pdo, $idPedido, null, 'pendente');
        $pdo->commit();
        unset($_SESSION['carrinho']);

        // link WhatsApp
        $wl      = $pdo->query("SELECT whatsapp FROM tb_dados_loja LIMIT 1")->fetchColumn();
        $telLoja = preg_replace('/\D/', '', $wl);
        $totalG  = $valorProd + $valorFrete;
        $msg     = "Olá! Novo pedido #{$idPedido}\n\n"
            . "Cliente: {$nomeCliente}\n"
            . "Telefone: {$telCliente}\n"
            . "Entrega: {$tipoEntrega}\n"
            . "Endereço: {$endereco}\n\n"
            . "Produtos: R$ " . number_format($valorProd, 2, ',', '.') . "\n"
            . "Frete: R$ " . number_format($valorFrete, 2, ',', '.') . "\n"
            . "Total: R$ " . number_format($totalG, 2, ',', '.') . "\n"
            . "Pagamento: {$formaPgto}";
        $link = "https://wa.me/55{$telLoja}?text=" . urlencode($msg);

        echo json_encode([
            'status'        => 'ok',
            'mensagem'      => 'Pedido criado com sucesso.',
            'id_pedido'     => $idPedido,
            'link_whatsapp' => $link
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao criar pedido.']);
    }
    exit;
}

// 6) CONFIRMAR (fluxo cliente via formulário)
if ($action === 'confirmar') {
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (empty($carrinho)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Carrinho vazio.']);
        exit;
    }

    // dados
    $tipoEntrega = $data['tipo_entrega']            ?? 'retirada';
    $idEnd       = $data['id_endereco_selecionado'] ?? null;
    $valorFrete  = ($tipoEntrega == 'entrega') ? floatval($data['valor_frete']) : 0;
    $formaPgto   = $data['forma_pagamento']         ?? '';
    $nomeCli     = $u['nome_usuario']  ?? ($u['nome'] ?? '');
    $telCli      = $u['telefone_usuario'] ?? ($u['telefone'] ?? '');

    // monta endereço
    $endereco = 'Retirada na loja';
    if ($tipoEntrega === 'entrega' && $idEnd) {
        $r = $pdo->prepare("
          SELECT rua, numero, bairro
            FROM tb_endereco
           WHERE id_endereco = ? AND id_usuario = ?
        ");
        $r->execute([$idEnd, $idUser]);
        if ($e = $r->fetch(PDO::FETCH_ASSOC)) {
            $endereco = "{$e['rua']}, {$e['numero']} - {$e['bairro']}";
        }
    }

    // soma produtos
    $valorProd = 0;
    foreach ($carrinho as $it) {
        $valorProd += ($it['valor_unitario'] * $it['quantidade']);
    }

    try {
        $pdo->beginTransaction();

        // insere pedido
        $ins = $pdo->prepare("
          INSERT INTO tb_pedido (
            id_usuario, nome_cliente, telefone_cliente, endereco,
            tipo_entrega, forma_pagamento, valor_total, valor_frete,
            status_pedido, criado_em
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
        ");
        $ins->execute([
            $idUser,
            $nomeCli,
            $telCli,
            $endereco,
            $tipoEntrega,
            $formaPgto,
            $valorProd,
            $valorFrete
        ]);
        $idPedido = $pdo->lastInsertId();

        // prepara inserções
        $insItem  = $pdo->prepare("
          INSERT INTO tb_item_pedido
            (id_pedido, id_produto, nome_exibicao, quantidade, valor_unitario)
          VALUES (?, ?, ?, ?, ?)
        ");
        $insSabor = $pdo->prepare("
          INSERT INTO tb_item_pedido_sabor
            (id_item_pedido, id_produto, proporcao)
          VALUES (?, ?, ?)
        ");
        $insAdd   = $pdo->prepare("
          INSERT INTO tb_item_adicional
            (id_item_pedido, id_adicional, nome_adicional, valor_adicional)
          VALUES (?, ?, ?, ?)
        ");

        foreach ($carrinho as $it) {
            // insere item
            $insItem->execute([
                $idPedido,
                $it['id_produto'],
                $it['nome_produto'],
                $it['quantidade'],
                $it['valor_unitario']
            ]);
            $idItem = $pdo->lastInsertId();

            // sabores
            if (!empty($it['sabores'])) {
                foreach ($it['sabores'] as $sab) {
                    $insSabor->execute([$idItem, $sab['id'], 100]);
                }
            }
            // adicionais
            foreach ($it['adicionais'] as $add) {
                $insAdd->execute([
                    $idItem,
                    $add['id'],
                    $add['nome'],
                    $add['valor']
                ]);
            }
        }

        registrarLogStatus($pdo, $idPedido, null, 'pendente');
        $pdo->commit();
        unset($_SESSION['carrinho']);

        // link WhatsApp
        $wl      = $pdo->query("SELECT whatsapp FROM tb_dados_loja LIMIT 1")->fetchColumn();
        $telLoja = preg_replace('/\D/', '', $wl);
        $totalG  = $valorProd + $valorFrete;
        $msg     = "Olá! Novo pedido #{$idPedido}\n\n"
            . "Cliente: {$nomeCli}\n"
            . "Telefone: {$telCli}\n"
            . "Entrega: {$tipoEntrega}\n"
            . "Endereço: {$endereco}\n\n"
            . "Produtos: R$ " . number_format($valorProd, 2, ',', '.') . "\n"
            . "Frete: R$ " . number_format($valorFrete, 2, ',', '.') . "\n"
            . "Total: R$ " . number_format($totalG, 2, ',', '.') . "\n"
            . "Pagamento: {$formaPgto}";
        $link = "https://wa.me/55{$telLoja}?text=" . urlencode($msg);

        echo json_encode([
            'status'        => 'ok',
            'mensagem'      => 'Pedido confirmado com sucesso.',
            'id_pedido'     => $idPedido,
            'link_whatsapp' => $link
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao confirmar pedido.']);
    }
    exit;
}

// 7) GET_PEDIDO (detalhes para impressão)
if ($action === 'get_pedido') {
    $id = $data['id_pedido'] ?? null;
    if (!$id) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID não informado.']);
        exit;
    }
    $stmt = $pdo->prepare("
      SELECT p.*, COALESCE(u.nome_usuario, p.nome_cliente) AS cliente
        FROM tb_pedido p
        LEFT JOIN tb_usuario u USING(id_usuario)
       WHERE p.id_pedido = ?
    ");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Pedido não encontrado.']);
        exit;
    }

    // busca itens, sabores e adicionais
    $itens = [];
    $q1 = $pdo->prepare("
      SELECT id_item_pedido, nome_exibicao, quantidade, valor_unitario
        FROM tb_item_pedido
       WHERE id_pedido = ?
    ");
    $q1->execute([$id]);
    foreach ($q1->fetchAll(PDO::FETCH_ASSOC) as $it) {
        // sabores
        $q2 = $pdo->prepare("
          SELECT p.nome_produto AS sabor
            FROM tb_item_pedido_sabor ips
            JOIN tb_produto p USING(id_produto)
           WHERE ips.id_item_pedido = ?
        ");
        $q2->execute([$it['id_item_pedido']]);
        $sabores = array_column($q2->fetchAll(PDO::FETCH_ASSOC), 'sabor');

        // adicionais
        $q3 = $pdo->prepare("
          SELECT nome_adicional, valor_adicional
            FROM tb_item_adicional
           WHERE id_item_pedido = ?
        ");
        $q3->execute([$it['id_item_pedido']]);
        $adicionais = $q3->fetchAll(PDO::FETCH_ASSOC);

        $it['sabores']    = $sabores;
        $it['adicionais'] = $adicionais;
        $itens[]          = $it;
    }

    echo json_encode([
        'status' => 'ok',
        'pedido' => $pedido,
        'itens'  => $itens
    ]);
    exit;
}

// Ação desconhecida
echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
exit;
