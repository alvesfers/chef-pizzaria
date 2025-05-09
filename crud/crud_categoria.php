<?php
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// GET por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tb_categoria WHERE id_categoria = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Não encontrada']);
        exit;
    }

    // subcategorias vinculadas
    $stmt2 = $pdo->prepare("
      SELECT id_subcategoria
        FROM tb_subcategoria_categoria
       WHERE id_categoria = ?
    ");
    $stmt2->execute([$id]);
    $cat['subcategorias'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    // tipos de adicionais vinculados
    $stmt3 = $pdo->prepare("
      SELECT id_tipo_adicional
        FROM tb_tipo_adicional_categoria
       WHERE id_categoria = ?
    ");
    $stmt3->execute([$id]);
    $cat['tipo_adicionais'] = $stmt3->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['status' => 'ok', 'categoria' => $cat]);
    exit;
}

// POST JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Requisição inválida']);
    exit;
}

// DELETE lógico
if (($input['action'] ?? '') === 'delete') {
    $id = (int)$input['id_categoria'];
    $pdo->prepare("UPDATE tb_categoria SET categoria_ativa = 0 WHERE id_categoria = ?")
        ->execute([$id]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// validação
$nome = trim($input['nome_categoria'] ?? '');
if ($nome === '') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Nome obrigatório']);
    exit;
}

$tem_qtd        = intval($input['tem_qtd'] ?? 0);
$ativa          = intval($input['categoria_ativa'] ?? 0);
$ordem          = intval($input['ordem_exibicao'] ?? 0);

// dados básicos
$data = [
    'nome_categoria'  => $nome,
    'tem_qtd'         => $tem_qtd,
    'categoria_ativa' => $ativa,
    'ordem_exibicao'  => $ordem,
];

// CREATE / UPDATE
if (!empty($input['id_categoria'])) {
    $id   = intval($input['id_categoria']);
    $sets = array_map(fn($c) => "$c=:$c", array_keys($data));
    $sql  = "UPDATE tb_categoria SET " . implode(', ', $sets) . " WHERE id_categoria=:id";
    $stmt = $pdo->prepare($sql);
    $data['id'] = $id;
    $stmt->execute($data);
} else {
    $cols = implode(', ', array_keys($data));
    $ph   = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
    $sql  = "INSERT INTO tb_categoria ($cols) VALUES ($ph)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
}

// 1) atualizar subcategoria↔categoria
$pdo->prepare("DELETE FROM tb_subcategoria_categoria WHERE id_categoria = ?")
    ->execute([$id]);
if (!empty($input['subcategorias']) && is_array($input['subcategorias'])) {
    $stmtSC = $pdo->prepare("
      INSERT INTO tb_subcategoria_categoria
        (id_categoria,id_subcategoria)
      VALUES (?,?)
    ");
    foreach ($input['subcategorias'] as $scId) {
        $stmtSC->execute([$id, intval($scId)]);
    }
}

// 2) atualizar tipo_adicional↔categoria
$pdo->prepare("DELETE FROM tb_tipo_adicional_categoria WHERE id_categoria = ?")
    ->execute([$id]);
if (!empty($input['tipo_adicionais']) && is_array($input['tipo_adicionais'])) {
    $stmtTA = $pdo->prepare("
      INSERT INTO tb_tipo_adicional_categoria
        (id_categoria,id_tipo_adicional,ordem)
      VALUES (?,?,0)
    ");
    foreach ($input['tipo_adicionais'] as $taId) {
        $stmtTA->execute([$id, intval($taId)]);
    }
}

echo json_encode(['status' => 'ok', 'id' => $id]);
