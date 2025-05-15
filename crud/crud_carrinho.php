<?php
// arquivo: crud_carrinho.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../assets/conexao.php';

// Garante o array de carrinho na sessão
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'add':
        $idProduto  = intval($input['id_produto'] ?? 0);
        $quantidade = max(1, intval($input['quantidade'] ?? 1));

        $saboresInput    = $input['sabores']    ?? [];
        $sabores         = is_array($saboresInput)    ? array_map('intval', $saboresInput) : [];

        $adicionaisInput = $input['adicionais'] ?? [];
        $adicionais      = is_array($adicionaisInput) ? $adicionaisInput : [];

        if (!$idProduto) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Produto inválido.']);
            exit;
        }

        date_default_timezone_set('America/Sao_Paulo');
        $mapaDias = [
            'monday'    => 'segunda',
            'tuesday'   => 'terça',
            'wednesday' => 'quarta',
            'thursday'  => 'quinta',
            'friday'    => 'sexta',
            'saturday'  => 'sábado',
            'sunday'    => 'domingo',
        ];
        $diaSemana = $mapaDias[strtolower(date('l'))] ?? '';

        $stmt = $pdo->prepare("
            SELECT nome_produto, valor_produto, tipo_calculo_preco, qtd_sabores
              FROM tb_produto
             WHERE id_produto    = ?
               AND produto_ativo = 1
        ");
        $stmt->execute([$idProduto]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado.']);
            exit;
        }

        $nomeProduto    = $prod['nome_produto'];
        $tipoCalculo    = $prod['tipo_calculo_preco'];
        $qtdSaboresProd = intval($prod['qtd_sabores']);
        $valorBase      = floatval($prod['valor_produto']);

        // Aplica promoção se houver
        $stmt = $pdo->prepare("
            SELECT valor_promocional
              FROM tb_campanha_produto_dia
             WHERE id_produto = ?
               AND dia_semana = ?
               AND ativo      = 1
        ");
        $stmt->execute([$idProduto, $diaSemana]);
        $promo = $stmt->fetchColumn();
        if ($promo !== false) {
            $valorBase = floatval($promo);
        }

        // Processa sabores
        $saboresFormatados = [];
        if ($qtdSaboresProd > 1) {
            if (count($sabores) !== $qtdSaboresProd) {
                echo json_encode([
                    'status'   => 'erro',
                    'mensagem' => "Escolha exatamente {$qtdSaboresProd} sabor(es)."
                ]);
                exit;
            }
            $valoresSab = [];
            $stmtS = $pdo->prepare("
                SELECT nome_produto, valor_produto
                  FROM tb_produto
                 WHERE id_produto    = ?
                   AND produto_ativo = 1
            ");
            foreach ($sabores as $idSabor) {
                $stmtS->execute([$idSabor]);
                $row = $stmtS->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $v = floatval($row['valor_produto']);
                    $saboresFormatados[] = [
                        'id'    => $idSabor,
                        'nome'  => $row['nome_produto'],
                        'valor' => $v
                    ];
                    $valoresSab[] = $v;
                }
            }
            if ($tipoCalculo === 'media') {
                $valorBase = array_sum($valoresSab) / count($valoresSab);
            } else {
                $valorBase = max($valoresSab);
            }
        }

        // Limites de inclusos
        $stmt = $pdo->prepare("
            SELECT id_tipo_adicional, max_inclusos
              FROM tb_produto_tipo_adicional
             WHERE id_produto = ?
        ");
        $stmt->execute([$idProduto]);
        $tipoLimits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Inclusos padrão
        $stmt = $pdo->prepare("
            SELECT id_adicional
              FROM tb_produto_adicional_incluso
             WHERE id_produto = ?
        ");
        $stmt->execute([$idProduto]);
        $defaultInclusos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        // Formata adicionais
        $adicionaisFormatados = [];
        $stmtA = $pdo->prepare("
            SELECT nome_adicional, valor_adicional
              FROM tb_adicional
             WHERE id_adicional    = ?
               AND adicional_ativo = 1
        ");
        foreach ($adicionais as $tipoId => $ids) {
            $tipoId = intval($tipoId);
            $maxInc = intval($tipoLimits[$tipoId] ?? 0);
            $ids = array_map('intval', $ids);
            usort($ids, function ($a, $b) use ($defaultInclusos) {
                return (in_array($b, $defaultInclusos) ? 1 : 0)
                    - (in_array($a, $defaultInclusos) ? 1 : 0);
            });
            foreach ($ids as $idx => $aid) {
                $stmtA->execute([$aid]);
                $row = $stmtA->fetch(PDO::FETCH_ASSOC);
                if (!$row) continue;
                $isExtra = ($idx >= $maxInc);
                $adicionaisFormatados[] = [
                    'id'    => $aid,
                    'nome'  => $row['nome_adicional'],
                    'valor' => floatval($row['valor_adicional']),
                    'extra' => $isExtra
                ];
            }
        }

        // Calcula valor unitário
        $valorUnitario = $valorBase;
        foreach ($adicionaisFormatados as $a) {
            if ($a['extra']) {
                $valorUnitario += $a['valor'];
            }
        }

        // Chave única
        $key = md5(
            $idProduto . '-' .
                implode(',', array_column($saboresFormatados,   'id')) . '-' .
                implode(',', array_column($adicionaisFormatados, 'id'))
        );

        $item = [
            'id_produto'     => $idProduto,
            'nome_produto'   => $nomeProduto,
            'quantidade'     => $quantidade,
            'valor_unitario' => $valorUnitario,
            'sabores'        => $saboresFormatados,
            'adicionais'     => $adicionaisFormatados
        ];

        if (isset($_SESSION['carrinho'][$key])) {
            $_SESSION['carrinho'][$key]['quantidade'] += $quantidade;
        } else {
            $_SESSION['carrinho'][$key] = $item;
        }

        echo json_encode([
            'status'   => 'ok',
            'carrinho' => $_SESSION['carrinho']
        ]);
        exit;

    case 'update':
        $key = $input['key'] ?? '';
        $delta = intval($input['quantidade'] ?? 0);

        if (!isset($_SESSION['carrinho'][$key])) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Item não encontrado.']);
            exit;
        }

        $atual = $_SESSION['carrinho'][$key]['quantidade'];

        if ($delta === 999) {
            $_SESSION['carrinho'][$key]['quantidade']++;
        } elseif ($delta === -1) {
            $_SESSION['carrinho'][$key]['quantidade'] = max(1, $atual - 1);
        } elseif ($delta >= 1) {
            $_SESSION['carrinho'][$key]['quantidade'] = $delta;
        }

        echo json_encode(['status' => 'ok', 'carrinho' => $_SESSION['carrinho']]);
        exit;

    case 'remove':
        $key = $input['key'] ?? '';
        if (isset($_SESSION['carrinho'][$key])) {
            unset($_SESSION['carrinho'][$key]);
            echo json_encode(['status' => 'ok', 'carrinho' => $_SESSION['carrinho']]);
        } else {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Item não existe.']);
        }
        exit;

    case 'clear':
        $_SESSION['carrinho'] = [];
        echo json_encode(['status' => 'ok', 'carrinho' => []]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
        exit;
}
