<?php
include_once 'assets/header.php';

$idPedido = $_GET['id'] ?? null;

if (!$idPedido || !isset($_SESSION['usuario'])) {
    echo "<p class='text-center mt-10 text-red-500'>Pedido não encontrado.</p>";
    include 'assets/footer.php';
    exit;
}

$idUsuario = $_SESSION['usuario']['id'];

$stmt = $pdo->prepare("SELECT * FROM tb_pedido WHERE id_pedido = ? AND id_usuario = ?");
$stmt->execute([$idPedido, $idUsuario]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "<p class='text-center mt-10 text-red-500'>Pedido não encontrado.</p>";
    include 'assets/footer.php';
    exit;
}

// Itens
$stmtItens = $pdo->prepare("SELECT * FROM tb_item_pedido WHERE id_pedido = ?");
$stmtItens->execute([$idPedido]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

// Logs
$stmtLogs = $pdo->prepare("SELECT * FROM tb_pedido_status_log WHERE id_pedido = ? ORDER BY alterado_em DESC");
$stmtLogs->execute([$idPedido]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Badge
$badgeClass = match ($pedido['status_pedido']) {
    'pendente' => 'badge-warning',
    'cancelado' => 'badge-error',
    'entregue' => 'badge-success',
    'preparando' => 'badge-info',
    'saiu' => 'badge-primary',
    default => 'badge-outline',
};

$dadosLoja = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6 max-w-xl">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold">Detalhes do Pedido #<?= $pedido['id_pedido'] ?></h1>
        <span class="badge <?= $badgeClass ?>"><?= ucfirst($pedido['status_pedido']) ?></span>
    </div>

    <?php if ($pedido['status_pedido'] !== 'cancelado'): ?>
        <button class="hidden" onclick="printJS({ printable: 'comprovante', type: 'html', css: 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' })"
            class="btn btn-primary btn-sm mb-4">
            <i class="fas fa-print mr-2"></i> Imprimir Comprovante
        </button>
    <?php endif; ?>

    <div id="comprovante" class="bg-white shadow rounded p-4 font-mono text-sm border border-gray-300">
        <div class="text-center">
            <?php if (!empty($dadosLoja['logo'])): ?>
                <img src="<?= $dadosLoja['logo'] ?>" alt="Logo" class="h-12 mx-auto mb-2">
            <?php endif; ?>
            <p class="font-bold text-lg"><?= htmlspecialchars($dadosLoja['nome_loja'] ?? 'Pizzaria') ?></p>
            <p><?= htmlspecialchars($dadosLoja['endereco_completo'] ?? '') ?></p>
        </div>

        <hr class="my-2">

        <p><strong>Pedido:</strong> #<?= $pedido['id_pedido'] ?></p>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></p>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['nome_cliente']) ?></p>
        <p><strong>Tipo de Entrega:</strong> <?= ucfirst($pedido['tipo_entrega']) ?></p>
        <?php if ($pedido['tipo_entrega'] == 'entrega'): ?>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($pedido['endereco']) ?></p>
        <?php endif; ?>
        <hr class="my-2">

        <p class="font-bold mb-1">Itens:</p>
        <?php foreach ($itens as $item): ?>
            <div class="mb-2">
                <p><?= $item['quantidade'] ?>x <?= htmlspecialchars($item['nome_exibicao']) ?> - R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></p>

                <?php
                $stmtSabores = $pdo->prepare("SELECT ps.*, p.nome_produto FROM tb_item_pedido_sabor ps JOIN tb_produto p ON ps.id_produto = p.id_produto WHERE id_item_pedido = ?");
                $stmtSabores->execute([$item['id_item_pedido']]);
                $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);

                $stmtAdd = $pdo->prepare("SELECT * FROM tb_item_adicional WHERE id_item_pedido = ?");
                $stmtAdd->execute([$item['id_item_pedido']]);
                $adicionais = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($sabores): ?>
                    <div class="ml-4 text-gray-600 text-xs">
                        Sabores:
                        <?php foreach ($sabores as $sabor): ?>
                            <p>- <?= $sabor['nome_produto'] ?> (<?= $sabor['proporcao'] ?>%)</p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="ml-4 mt-1 text-xs text-gray-600">
                    <div class="mb-1 font-semibold">Adicionais:</div>
                    <?php if ($adicionais): ?>
                        <ul class="list-disc list-inside">
                            <?php foreach ($adicionais as $add): ?>
                                <li>
                                    <?= htmlspecialchars($add['nome_adicional']) ?>
                                    <?php if (floatval($add['valor_adicional']) > 0): ?>
                                        — R$ <?= number_format($add['valor_adicional'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="italic text-gray-400">Nenhum adicional.</p>
                    <?php endif; ?>
                </div>


            </div>
        <?php endforeach; ?>

        <hr class="my-2">

        <p>Subtotal: R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></p>
        <p>Frete: R$ <?= number_format($pedido['valor_frete'], 2, ',', '.') ?></p>
        <p class="font-bold text-lg">Total: R$ <?= number_format($pedido['valor_total'] + $pedido['valor_frete'], 2, ',', '.') ?></p>

        <hr class="my-2">
        <p><strong>Pagamento:</strong> <?= htmlspecialchars($pedido['forma_pagamento']) ?></p>
    </div>

    <div class="mt-6">
        <h2 class="font-semibold mb-2">Histórico de Status</h2>
        <ul class="text-xs text-gray-600 list-disc list-inside">
            <?php foreach ($logs as $log): ?>
                <li>
                    <?= ucfirst($log['status_novo']) ?> em <?= date('d/m/Y H:i', strtotime($log['alterado_em'])) ?>
                    <?php if ($log['motivo']): ?>
                        <br><em><?= htmlspecialchars($log['motivo']) ?></em>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>
<link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">

<?php include_once 'assets/footer.php'; ?>