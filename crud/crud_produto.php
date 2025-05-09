<?php
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// --- BUSCA PRODUTO POR ID (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // 1) Produto principal
    $stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado']);
        exit;
    }

    // 2) Subcategorias vinculadas
    $stmt = $pdo->prepare("
        SELECT id_subcategoria
          FROM tb_subcategoria_produto
         WHERE id_produto = ?
    ");
    $stmt->execute([$id]);
    $produto['subcategorias'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3) Adicionais inclusos
    $stmt = $pdo->prepare("
        SELECT id_adicional
          FROM tb_produto_adicional_incluso
         WHERE id_produto = ?
    ");
    $stmt->execute([$id]);
    $inclusos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 4) Tipos de adicionais para esta categoria
    $stmt = $pdo->prepare("
        SELECT
          ta.id_tipo_adicional,
          ta.nome_tipo_adicional,
          COALESCE(pta.obrigatorio, 0)   AS obrigatorio,
          COALESCE(pta.max_inclusos, 0)  AS max_inclusos
        FROM tb_tipo_adicional_categoria tac
        JOIN tb_tipo_adicional ta
          ON ta.id_tipo_adicional = tac.id_tipo_adicional
        LEFT JOIN tb_produto_tipo_adicional pta
          ON pta.id_produto = ?
         AND pta.id_tipo_adicional = ta.id_tipo_adicional
        WHERE tac.id_categoria = ?
    ");
    $stmt->execute([$id, $produto['id_categoria']]);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) Adicionais de cada tipo
    $stmtAdds = $pdo->prepare("
        SELECT id_adicional, nome_adicional, valor_adicional
          FROM tb_adicional
         WHERE id_tipo_adicional = ?
           AND adicional_ativo = 1
    ");
    foreach ($tipos as &$tipo) {
        $stmtAdds->execute([$tipo['id_tipo_adicional']]);
        $adds = $stmtAdds->fetchAll(PDO::FETCH_ASSOC);
        foreach ($adds as &$add) {
            $add['incluso'] = in_array($add['id_adicional'], $inclusos) ? 1 : 0;
        }
        unset($add);
        $tipo['adicionais'] = $adds;
    }
    unset($tipo);

    $produto['tipo_adicionais'] = $tipos;

    echo json_encode(['status' => 'ok', 'produto' => $produto]);
    exit;
}

// --- DELETE (desativa produto) ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']);
    exit;
}
if (($input['action'] ?? '') === 'delete') {
    $id = (int) $input['id_produto'];
    $pdo->prepare("UPDATE tb_produto SET produto_ativo = 0 WHERE id_produto = ?")
        ->execute([$id]);
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

// INSERT ou UPDATE em tb_produto
if (!empty($input['id_produto'])) {
    $id   = intval($input['id_produto']);
    $sets = array_map(fn($col) => "$col = :$col", array_keys($data));
    $sql  = "UPDATE tb_produto SET " . implode(', ', $sets) . " WHERE id_produto = :id";
    $stmt = $pdo->prepare($sql);
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols         = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $sql  = "INSERT INTO tb_produto ($cols) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}

// 1) Subcategorias
$pdo->prepare("DELETE FROM tb_subcategoria_produto WHERE id_produto = ?")
    ->execute([$id]);

$chkCatSub = $pdo->prepare("
    SELECT 1 FROM tb_subcategoria_categoria
     WHERE id_categoria = ? AND id_subcategoria = ?
");
$insCatSub = $pdo->prepare("
    INSERT INTO tb_subcategoria_categoria
      (id_categoria, id_subcategoria)
    VALUES (?, ?)
");
$insProdSub = $pdo->prepare("
    INSERT INTO tb_subcategoria_produto
      (id_produto, id_subcategoria)
    VALUES (?, ?)
");
foreach ($input['subcategorias'] ?? [] as $sc) {
    $scId = intval($sc);
    // garante relação categoria↔subcategoria
    $chkCatSub->execute([$id_categoria, $scId]);
    if (!$chkCatSub->fetchColumn()) {
        $insCatSub->execute([$id_categoria, $scId]);
    }
    // vincula produto↔subcategoria
    $insProdSub->execute([$id, $scId]);
}

// 2) Tipos de adicionais
$pdo->prepare("DELETE FROM tb_produto_tipo_adicional WHERE id_produto = ?")
    ->execute([$id]);
if (!empty($input['tipo_adicional']) && is_array($input['tipo_adicional'])) {
    $stmt2 = $pdo->prepare("
        INSERT INTO tb_produto_tipo_adicional
          (id_produto, id_tipo_adicional, obrigatorio, max_inclusos)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($input['tipo_adicional'] as $tipoId => $info) {
        $obr = !empty($info['obrigatorio']) ? 1 : 0;
        $max = intval($info['max_inclusos'] ?? 0);
        $stmt2->execute([$id, intval($tipoId), $obr, $max]);
    }
}

// 3) Adicionais inclusos
$pdo->prepare("DELETE FROM tb_produto_adicional_incluso WHERE id_produto = ?")
    ->execute([$id]);
if (!empty($input['tipo_adicional']) && is_array($input['tipo_adicional'])) {
    $stmt3 = $pdo->prepare("
        INSERT INTO tb_produto_adicional_incluso
          (id_produto, id_adicional)
        VALUES (?, ?)
    ");
    foreach ($input['tipo_adicional'] as $info) {
        foreach ($info['adicionais'] ?? [] as $addId) {
            $stmt3->execute([$id, intval($addId)]);
        }
    }
}

echo json_encode(['status' => 'ok', 'id' => $id]);
