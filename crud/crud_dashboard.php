<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../assets/conexao.php';
header('Content-Type: application/json');


if (!($_SESSION['usuario']['tipo_usuario'] ?? '') === 'admin') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    // 1) resumo numérico: total receita, total pedidos, ticket médio
    case 'resumo':
        $de  = $_POST['de'];
        $ate = $_POST['ate'];
        $sql = "
      SELECT 
        SUM(valor_total) AS total_receita,
        COUNT(*)        AS total_pedidos,
        AVG(valor_total) AS ticket_medio
      FROM tb_pedido
      WHERE criado_em BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$de, $ate]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'sucesso', 'data' => $d]);
        break;

    // 2) série temporal de receita, agrupada por dia/semana/mês
    case 'serie_receita':
        $periodo = $_POST['periodo']; // day, week, month
        $de  = $_POST['de'];
        $ate = $_POST['ate'];
        // define formato SQL e label JS
        if ($periodo === 'day') {
            $fmt = '%Y-%m-%d';
            $labelFmt = 'label';
        } elseif ($periodo === 'week') {
            // semana ISO
            $fmt = '%x-W%v';
            $labelFmt = 'semana';
        } else {
            // mês
            $fmt = '%Y-%m';
            $labelFmt = 'mes';
        }
        $sql = "
      SELECT DATE_FORMAT(criado_em, '$fmt') AS periodo, 
             SUM(valor_total) AS receita
      FROM tb_pedido
      WHERE criado_em BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
      GROUP BY periodo
      ORDER BY periodo
    ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$de, $ate]);
        $labels = [];
        $values = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $labels[] = $row['periodo'];
            $values[] = (float)$row['receita'];
        }
        echo json_encode(['status' => 'sucesso', 'labels' => $labels, 'values' => $values]);
        break;

    // 3) distribuição de pedidos por status
    case 'status_pedidos':
        $de  = $_POST['de'];
        $ate = $_POST['ate'];
        $sql = "
      SELECT status_pedido, COUNT(*) AS qtd
      FROM tb_pedido
      WHERE criado_em BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
      GROUP BY status_pedido
    ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$de, $ate]);
        $labels = [];
        $values = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $labels[] = $r['status_pedido'];
            $values[] = (int)$r['qtd'];
        }
        echo json_encode(['status' => 'sucesso', 'labels' => $labels, 'values' => $values]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida']);
}
