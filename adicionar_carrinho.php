<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';

$idProduto = $_POST['id_produto'] ?? null;
$quantidade = (int) ($_POST['quantidade'] ?? 1);
$adicionais = $_POST['adicionais'] ?? [];

if (!$idProduto || $quantidade < 1) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
    exit;
}

date_default_timezone_set('America/Sao_Paulo');
$mapaDias = [
    'monday' => 'segunda',
    'tuesday' => 'terça',
    'wednesday' => 'quarta',
    'thursday' => 'quinta',
    'friday' => 'sexta',
    'saturday' => 'sábado',
    'sunday' => 'domingo',
];
$diaSemana = $mapaDias[strtolower(date('l'))];

// Buscar produto
$stmt = $pdo->prepare("SELECT nome_produto, valor_produto FROM tb_produto WHERE id_produto = ? AND produto_ativo = 1");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado.']);
    exit;
}

// Verifica promoção
$stmt = $pdo->prepare("SELECT valor_promocional FROM tb_campanha_produto_dia WHERE id_produto = ? AND dia_semana = ? AND ativo = 1");
$stmt->execute([$idProduto, $diaSemana]);
$promocao = $stmt->fetchColumn();
$valorProduto = $promocao ?: $produto['valor_produto'];

// Adicionais inclusos
$stmt = $pdo->prepare("SELECT id_adicional FROM tb_produto_adicional_incluso WHERE id_produto = ?");
$stmt->execute([$idProduto]);
$inclusos = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_adicional'));

// Adicionais formatados
$adicionaisFormatados = [];
foreach ($adicionais as $idTipo => $idsAdicionais) {
    foreach ($idsAdicionais as $idAdicional) {
        $stmt = $pdo->prepare("SELECT nome_adicional, valor_adicional FROM tb_adicional WHERE id_adicional = ?");
        $stmt->execute([$idAdicional]);
        $add = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($add) {
            $adicionaisFormatados[] = [
                'id' => $idAdicional,
                'nome' => $add['nome_adicional'],
                'valor' => $add['valor_adicional'],
                'extra' => !in_array((int)$idAdicional, $inclusos)
            ];
        }
    }
}

// Salva no carrinho
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$_SESSION['carrinho'][] = [
    'id_produto' => $idProduto,
    'nome_produto' => $produto['nome_produto'],
    'quantidade' => $quantidade,
    'valor_unitario' => $valorProduto,
    'adicionais' => $adicionaisFormatados
];

echo json_encode(['status' => 'ok']);
exit;
