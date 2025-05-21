<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

function duplicarProduto($nome, $categoriaId, $valor, $descricao, $qtd_sabores, $tipo_calc, $ativo, $qtd_produto, $subcategorias)
{
    global $pdo;
    $insProdSub = $pdo->prepare("INSERT INTO tb_subcategoria_produto (id_produto, id_subcategoria) VALUES (?, ?)");

    $dupData = [
        'nome_produto'       => $nome,
        'id_categoria'       => $categoriaId,
        'valor_produto'      => $valor,
        'qtd_produto'        => $qtd_produto,
        'descricao_produto'  => $descricao,
        'qtd_sabores'        => $qtd_sabores,
        'tipo_calculo_preco' => $tipo_calc,
        'produto_ativo'      => $ativo,
    ];
    $cols = implode(', ', array_keys($dupData));
    $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($dupData)));
    $stmtDup = $pdo->prepare("INSERT INTO tb_produto ($cols) VALUES ($vals)");
    $stmtDup->execute($dupData);
    $dupId = $pdo->lastInsertId();

    foreach ($subcategorias as $sc) {
        $insProdSub->execute([$dupId, intval($sc)]);
    }
}

// --- BUSCA PRODUTO POR ID ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id_subcategoria FROM tb_subcategoria_produto WHERE id_produto = ?");
    $stmt->execute([$id]);
    $produto['subcategorias'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['status' => 'ok', 'produto' => $produto]);
    exit;
}

// --- DELETE ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']);
    exit;
}

if (($input['action'] ?? '') === 'delete') {
    $id = (int) $input['id_produto'];
    $pdo->prepare("UPDATE tb_produto SET produto_ativo = 0 WHERE id_produto = ?")->execute([$id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// --- CREATE / UPDATE ---
$nome         = trim($input['nome_produto'] ?? '');
$id_categoria = intval($input['id_categoria'] ?? 0);
$valor        = floatval(str_replace(',', '.', $input['valor_produto'] ?? '0'));
$qtd_sabores  = intval($input['qtd_sabores'] ?? 1);
$qtd_produto  = intval($input['qtd_produto'] ?? 0);
$descricao    = $input['descricao_produto'] ?? '';
$tipo_calc    = $input['tipo_calculo_preco'] ?? 'maior';
$ativo        = !empty($input['produto_ativo']) ? 1 : 0;

if (!$nome || !$id_categoria || $valor <= 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos']);
    exit;
}

$data = [
    'nome_produto'       => $nome,
    'id_categoria'       => $id_categoria,
    'valor_produto'      => $valor,
    'qtd_produto'        => $qtd_produto,
    'descricao_produto'  => $descricao,
    'qtd_sabores'        => $qtd_sabores,
    'tipo_calculo_preco' => $tipo_calc,
    'produto_ativo'      => $ativo,
];

// INSERT ou UPDATE
if (!empty($input['id_produto'])) {
    $id = intval($input['id_produto']);
    $sets = array_map(fn($col) => "$col = :$col", array_keys($data));
    $sql  = "UPDATE tb_produto SET " . implode(', ', $sets) . " WHERE id_produto = :id";
    $stmt = $pdo->prepare($sql);
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $sql = "INSERT INTO tb_produto ($cols) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}

// --- SUBCATEGORIAS ---
$pdo->prepare("DELETE FROM tb_subcategoria_produto WHERE id_produto = ?")->execute([$id]);

$chkCatSub = $pdo->prepare("SELECT 1 FROM tb_subcategoria_categoria WHERE id_categoria = ? AND id_subcategoria = ?");
$insCatSub = $pdo->prepare("INSERT INTO tb_subcategoria_categoria (id_categoria, id_subcategoria) VALUES (?, ?)");
$insProdSub = $pdo->prepare("INSERT INTO tb_subcategoria_produto (id_produto, id_subcategoria) VALUES (?, ?)");

foreach ($input['subcategorias'] ?? [] as $sc) {
    $scId = intval($sc);
    $chkCatSub->execute([$id_categoria, $scId]);
    if (!$chkCatSub->fetchColumn()) {
        $insCatSub->execute([$id_categoria, $scId]);
    }
    $insProdSub->execute([$id, $scId]);
}

// --- CATEGORIAS RELACIONADAS ---
$rel = $pdo->prepare("SELECT id_categoria_relacionada, label_relacionada FROM tb_categoria_relacionada WHERE id_categoria = ?");
$rel->execute([$id_categoria]);
$relacionadas = $rel->fetchAll(PDO::FETCH_ASSOC);

foreach ($relacionadas as $r) {
    $relId = $r['id_categoria_relacionada'];
    if (!empty($input["include_{$relId}"])) {
        $valor = floatval(str_replace(',', '.', $input["valor_{$relId}"] ?? '0'));
        duplicarProduto($r['label_relacionada'] . ' de ' . $nome, $relId, $valor, $descricao, $qtd_sabores, $tipo_calc, $ativo, $qtd_produto, $input['subcategorias'] ?? []);
    }
}

echo json_encode(['status' => 'ok', 'id' => $id]);
