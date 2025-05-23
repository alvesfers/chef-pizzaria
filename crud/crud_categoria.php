<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');

// ——————————————————————————
// 1) GET por ID
// ——————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // busca categoria
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

    // categorias relacionadas: traz id, label e obrigatorio
    $stmt4 = $pdo->prepare("
        SELECT id_categoria_relacionada,
               label_relacionada,
               obrigatorio
          FROM tb_categoria_relacionada
         WHERE id_categoria = ?
    ");
    $stmt4->execute([$id]);
    $cat['categorias_relacionadas'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'ok', 'categoria' => $cat]);
    exit;
}

// ——————————————————————————
// 2) POST JSON
// ——————————————————————————
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

// validações
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

// CREATE / UPDATE em tb_categoria
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

// 3) atualizar categoria_relacionada (agora com label e obrigatorio)
$pdo->prepare("DELETE FROM tb_categoria_relacionada WHERE id_categoria = ?")
    ->execute([$id]);

// prepara statements para inserção
$stmtGetName = $pdo->prepare("SELECT nome_categoria FROM tb_categoria WHERE id_categoria = ?");
$stmtCR      = $pdo->prepare("
  INSERT INTO tb_categoria_relacionada
    (id_categoria, id_categoria_relacionada, label_relacionada, obrigatorio)
  VALUES (?, ?, ?, ?)
");

if (!empty($input['categorias_relacionadas']) && is_array($input['categorias_relacionadas'])) {
    foreach ($input['categorias_relacionadas'] as $relId) {
        $relId = intval($relId);
        if ($relId === $id) {
            continue; // não vincular a si mesma
        }

        // pega nome da categoria relacionada
        $stmtGetName->execute([$relId]);
        $nomeRel = $stmtGetName->fetchColumn() ?: '';

        // gera label sem 's' final (e.g. "Pizzas" → "Pizza")
        $label = preg_replace('/s$/i', '', trim($nomeRel));

        // lê se foi marcado como obrigatório no form (checkbox name="obrigatorio[<id>]")
        $obrig = !empty($input['obrigatorio'][$relId]) ? 1 : 0;

        // executa inserção
        $stmtCR->execute([$id, $relId, $label, $obrig]);
    }
}

echo json_encode(['status' => 'ok', 'id' => $id]);
