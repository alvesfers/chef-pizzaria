<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// ——————————————————————————
// 1) BUSCA POR ID
// ——————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // produto
    $stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Produto não encontrado']);
        exit;
    }

    // subcategorias
    $stmt = $pdo->prepare("SELECT id_subcategoria FROM tb_subcategoria_produto WHERE id_produto = ?");
    $stmt->execute([$id]);
    $produto['subcategorias'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // adicionais inclusos
    $stmt = $pdo->prepare("SELECT id_adicional FROM tb_produto_adicional_incluso WHERE id_produto = ?");
    $stmt->execute([$id]);
    $inclusos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // tipos de adicional + itens
    $stmt = $pdo->prepare("
        SELECT ta.id_tipo_adicional, ta.nome_tipo_adicional,
               COALESCE(pta.obrigatorio,0)   AS obrigatorio,
               COALESCE(pta.max_inclusos,0) AS max_inclusos
        FROM tb_tipo_adicional_categoria tac
        JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
        LEFT JOIN tb_produto_tipo_adicional pta
          ON pta.id_produto = ? AND pta.id_tipo_adicional = ta.id_tipo_adicional
        WHERE tac.id_categoria = ?
    ");
    $stmt->execute([$id, $produto['id_categoria']]);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtAdds = $pdo->prepare("
        SELECT id_adicional, nome_adicional, valor_adicional
        FROM tb_adicional
        WHERE id_tipo_adicional = ? AND adicional_ativo = 1
    ");
    foreach ($tipos as &$t) {
        $stmtAdds->execute([$t['id_tipo_adicional']]);
        $adds = $stmtAdds->fetchAll(PDO::FETCH_ASSOC);
        foreach ($adds as &$a) {
            $a['incluso'] = in_array($a['id_adicional'], $inclusos) ? 1 : 0;
        }
        unset($a);
        $t['adicionais'] = $adds;
    }
    unset($t);

    $produto['tipo_adicionais'] = $tipos;

    echo json_encode(['status' => 'ok', 'produto' => $produto]);
    exit;
}

// ——————————————————————————
// 2) DELETE (desativa produto)
// ——————————————————————————
$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['action']) && $input['action'] === 'delete') {
    $id = (int)$input['id_produto'];
    $pdo->prepare("UPDATE tb_produto SET produto_ativo = 0 WHERE id_produto = ?")
        ->execute([$id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// ——————————————————————————
// 3) CREATE / UPDATE
// ——————————————————————————
$nome         = trim($input['nome_produto'] ?? '');
$id_categoria = intval($input['id_categoria'] ?? 0);
$valor        = floatval(str_replace(',', '.', $input['valor_produto'] ?? '0'));
$qtd_sabores  = intval($input['qtd_sabores'] ?? 1);
$qtd_produto  = intval($input['qtd_produto'] ?? -1);
$descricao    = trim($input['descricao_produto'] ?? '');
$raw_tipo = $input['tipo_calculo_preco'] ?? 'maior';
$tipo_calc = in_array($raw_tipo, ['maior', 'media'])
    ? $raw_tipo
    : 'maior';

$ativo        = !empty($input['produto_ativo']) ? 1 : 0;

if (!$nome || !$id_categoria || $valor <= 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos']);
    exit;
}

// busca o nome da categoria (ex: "Pizzas")
$stmtCat = $pdo->prepare("SELECT nome_categoria FROM tb_categoria WHERE id_categoria = ?");
$stmtCat->execute([$id_categoria]);
$categoriaLabel = $stmtCat->fetchColumn() ?: '';

// remove um 's' final (ficará "Pizza" em vez de "Pizzas")
$prefixo = preg_replace('/s$/i', '', $categoriaLabel);

// monta "Pizza de Alves"
$nomeFinal = $prefixo . ' de ' . $nome;

// gera slug a partir de $nomeFinal
$slug = strtolower(
    preg_replace(
        '/[^a-z0-9]+/',
        '-',
        iconv('UTF-8', 'ASCII//TRANSLIT', $nomeFinal)
    )
);

// prepara $data usando $nomeFinal
$data = [
    'id_categoria'       => $id_categoria,
    'nome_produto'       => $nomeFinal,
    'slug_produto'       => $slug,
    'valor_produto'      => $valor,
    'descricao_produto'  => $descricao,
    'produto_ativo'      => $ativo,
    'qtd_produto'        => $qtd_produto,
    'tipo_calculo_preco' => $tipo_calc,
    'qtd_sabores'        => $qtd_sabores,
];

// INSERT ou UPDATE
if (!empty($input['id_produto'])) {
    $id   = intval($input['id_produto']);
    $sets = array_map(fn($col) => "$col=:$col", array_keys($data));
    $sql  = "UPDATE tb_produto SET " . implode(', ', $sets) . " WHERE id_produto=:id";
    $stmt = $pdo->prepare($sql);
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols = implode(', ', array_keys($data));
    $vals = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $sql  = "INSERT INTO tb_produto ($cols) VALUES ($vals)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}

// ——————————————————————————
// 4) Subcategorias
// ——————————————————————————
$pdo->prepare("DELETE FROM tb_subcategoria_produto WHERE id_produto = ?")
    ->execute([$id]);
$insSub = $pdo->prepare("
    INSERT INTO tb_subcategoria_produto (id_produto, id_subcategoria)
    VALUES (?, ?)
");
foreach ((array)($input['subcategorias'] ?? []) as $sc) {
    $insSub->execute([$id, intval($sc)]);
}

// ——————————————————————————
// 5) Tipos de adicional
// ——————————————————————————
$pdo->prepare("DELETE FROM tb_produto_tipo_adicional WHERE id_produto = ?")
    ->execute([$id]);
if (!empty($input['tipo_adicional'])) {
    $insTipo = $pdo->prepare("
      INSERT INTO tb_produto_tipo_adicional
      (id_produto,id_tipo_adicional,obrigatorio,max_inclusos)
      VALUES (?,?,?,?)
    ");
    foreach ($input['tipo_adicional'] as $tid => $info) {
        $insTipo->execute([
            $id,
            intval($tid),
            !empty($info['obrigatorio']) ? 1 : 0,
            intval($info['max_inclusos'] ?? 0)
        ]);
    }
}

// ——————————————————————————
// 6) Adicionais inclusos
// ——————————————————————————
$pdo->prepare("DELETE FROM tb_produto_adicional_incluso WHERE id_produto = ?")
    ->execute([$id]);
if (!empty($input['tipo_adicional'])) {
    $insAdd = $pdo->prepare("
      INSERT INTO tb_produto_adicional_incluso (id_produto, id_adicional)
      VALUES (?,?)
    ");
    foreach ($input['tipo_adicional'] as $info) {
        foreach ((array)($info['adicionais'] ?? []) as $aid) {
            $insAdd->execute([$id, intval($aid)]);
        }
    }
}

// ——————————————————————————
// 7) Categorias relacionadas
// ——————————————————————————
$relStmt = $pdo->prepare("
    SELECT id_categoria_relacionada,
           label_relacionada,
           obrigatorio
    FROM tb_categoria_relacionada
    WHERE id_categoria = ?
");
$relStmt->execute([$id_categoria]);
$relacoes = $relStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($relacoes as $r) {
    $relId = (int)$r['id_categoria_relacionada'];
    $label = $r['label_relacionada'];
    $obrig = (int)$r['obrigatorio'];
    // inclui se for obrigatório OU se o usuário marcou include_{relId}
    $include = $obrig || !empty($input["include_{$relId}"]);
    if ($include) {
        // prepara dados para duplicar
        $dupData = [
            'id_categoria'      => $relId,
            'nome_produto'      => $label . ' de ' . $nome,
            'slug_produto'      => strtolower(preg_replace(
                '/[^a-z0-9]+/',
                '-',
                iconv('UTF-8', 'ASCII//TRANSLIT', $label . ' de ' . $nome)
            )),
            'valor_produto'     => floatval(str_replace(',', '.', $input["valor_{$relId}"] ?? '0')),
            'descricao_produto' => $descricao,
            'produto_ativo'     => $ativo,
            'qtd_produto'       => $qtd_produto,
            'tipo_calculo_preco' => $tipo_calc,
            'qtd_sabores'       => $qtd_sabores,
        ];
        // INSERT duplicado
        $cols = implode(', ', array_keys($dupData));
        $vals = implode(', ', array_map(fn($c) => ":$c", array_keys($dupData)));
        $dupSql  = "INSERT INTO tb_produto ($cols) VALUES ($vals)";
        $dupStmt = $pdo->prepare($dupSql);
        $dupStmt->execute($dupData);
        $dupId = $pdo->lastInsertId();

        // replica subcategorias
        foreach ((array)($input['subcategorias'] ?? []) as $sc) {
            $insSub->execute([$dupId, intval($sc)]);
        }
        // replica tipos de adicionais
        if (!empty($input['tipo_adicional'])) {
            foreach ($input['tipo_adicional'] as $tid => $info) {
                $insTipo->execute([
                    $dupId,
                    intval($tid),
                    !empty($info['obrigatorio']) ? 1 : 0,
                    intval($info['max_inclusos'] ?? 0)
                ]);
            }
            // replica inclusos
            foreach ($input['tipo_adicional'] as $info) {
                foreach ((array)($info['adicionais'] ?? []) as $aid) {
                    $insAdd->execute([$dupId, intval($aid)]);
                }
            }
        }
    }
}

echo json_encode(['status' => 'ok', 'id' => $id]);
