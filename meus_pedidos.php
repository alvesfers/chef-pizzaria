<?php
include_once 'assets/header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$idUsuario  = $_SESSION['usuario']['id'];
$novoPedido = isset($_GET['novo_pedido']) && $_GET['novo_pedido'] == 1;

$stmtPedidos = $pdo->prepare("
    SELECT *
      FROM tb_pedido
     WHERE id_usuario = ?
  ORDER BY criado_em DESC
");
$stmtItens     = $pdo->prepare("SELECT * FROM tb_item_pedido WHERE id_pedido = ?");
$stmtSabores   = $pdo->prepare("
    SELECT ps.proporcao, p.nome_produto
      FROM tb_item_pedido_sabor ps
      JOIN tb_produto p USING(id_produto)
     WHERE ps.id_item_pedido = ?
");
$stmtAdds      = $pdo->prepare("SELECT nome_adicional, valor_adicional FROM tb_item_adicional WHERE id_item_pedido = ?");
$stmtStatusLog = $pdo->prepare("SELECT * FROM tb_pedido_status_log WHERE id_pedido = ? ORDER BY alterado_em DESC");

$stmtPedidos->execute([$idUsuario]);
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

// Agrupa contagem por status
$contagem = array_count_values(array_column($pedidos, 'status_pedido'));
$todos    = count($pedidos);
$statuses = ['pendente', 'preparando', 'saiu', 'entregue', 'cancelado'];
$icones   = [
    'pendente'   => 'fas fa-clock',
    'preparando' => 'fas fa-pizza-slice',
    'saiu'       => 'fas fa-truck',
    'entregue'   => 'fas fa-check',
    'cancelado'  => 'fas fa-times'
];
$badgeClasses = [
    'pendente'   => 'badge-warning',
    'preparando' => 'badge-info',
    'saiu'       => 'badge-primary',
    'entregue'   => 'badge-success',
    'cancelado'  => 'badge-error'
];
$iconsPgto = [
    'pix' => 'fa fa-qrcode',
    'cartão' => 'fa fa-credit-card',
    'dinheiro' => 'fa fa-money-bill-wave',
];
?>

<div class="container mx-auto px-4 py-6 max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-center w-full sm:text-left sm:w-auto">Meus Pedidos</h1>
    </div>

    <?php if (empty($pedidos)): ?>
        <p class="text-center text-gray-500">Você ainda não fez nenhum pedido.</p>
    <?php else: ?>
        <div x-data="{ filtro: 'todos' }" class="space-y-6">

            <!-- Resumo por status -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm font-medium">
                <div class="btn w-full" :class="filtro==='todos' ? 'btn-primary' : 'btn-outline'" @click="filtro='todos'">
                    Todos (<?= $todos ?>)
                </div>
                <?php foreach ($statuses as $s): ?>
                    <div class="btn w-full"
                        :class="filtro==='<?= $s ?>' ? 'btn-primary' : 'btn-outline'"
                        @click="filtro='<?= $s ?>'">
                        <?= ucfirst($s) ?> (<?= $contagem[$s] ?? 0 ?>)
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Lista de pedidos -->
            <div class="space-y-4">
                <?php foreach ($pedidos as $pedido): ?>
                    <?php
                    $status = $pedido['status_pedido'];
                    $badgeClass = $badgeClasses[$status] ?? 'badge-outline';
                    $iconStatus = $icones[$status] ?? 'fas fa-question';
                    $stmtItens->execute([$pedido['id_pedido']]);
                    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
                    $stmtStatusLog->execute([$pedido['id_pedido']]);
                    $logs = $stmtStatusLog->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div
                        x-show="filtro === 'todos' || filtro === '<?= $status ?>'"
                        x-cloak
                        x-data="{ aberto: false }"
                        class="bg-base-100 shadow rounded-lg p-4">

                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Pedido #<?= $pedido['id_pedido'] ?></h2>
                            <span class="badge <?= $badgeClass ?>">
                                <i class="<?= $iconStatus ?> mr-1"></i> <?= ucfirst($status) ?>
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1 flex justify-between flex-wrap gap-2">
                            <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?>
                            <span class="italic"> <?= ucfirst($pedido['tipo_entrega']) ?> </span>
                            <span>
                                <?php
                                $forma = strtolower($pedido['forma_pagamento']);
                                $iconePg = $iconsPgto[$forma] ?? 'fa fa-money-bill';
                                ?>
                                <i class="<?= $iconePg ?> mr-1"></i>
                                <?= htmlspecialchars($pedido['forma_pagamento']) ?>
                            </span>
                        </div>

                        <div class="mt-2 flex justify-between text-sm">
                            <span>Produtos: <strong>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></strong></span>
                            <span>Frete: <strong>R$ <?= number_format($pedido['valor_frete'],  2, ',', '.') ?></strong></span>
                        </div>
                        <div class="text-right mt-2 text-lg font-bold">
                            Total: R$ <?= number_format($pedido['valor_total'] + $pedido['valor_frete'], 2, ',', '.') ?>
                        </div>

                        <div class="mt-3">
                            <button
                                @click="aberto = !aberto"
                                class="btn btn-sm btn-outline w-full flex justify-center items-center gap-2">
                                <i :class="aberto ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i> Ver Itens
                            </button>

                            <div x-show="aberto" x-transition class="mt-3 space-y-3">
                                <?php foreach ($itens as $item): ?>
                                    <?php
                                    $stmtSabores->execute([$item['id_item_pedido']]);
                                    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);
                                    $stmtAdds->execute([$item['id_item_pedido']]);
                                    $adicionais = $stmtAdds->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="bg-base-200 rounded p-3 text-sm">
                                        <div class="font-medium">
                                            <?= $item['quantidade'] ?>× <?= $item['nome_exibicao'] ?>
                                            — R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?>
                                        </div>
                                        <?php if ($sabores): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Sabores:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($sabores as $s): ?>
                                                        <li><?= $s['nome_produto'] ?> (<?= $s['proporcao'] ?>%)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($adicionais): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Adicionais:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($adicionais as $a): ?>
                                                        <li><?= $a['nome_adicional'] ?> — R$ <?= number_format($a['valor_adicional'], 2, ',', '.') ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col sm:flex-row sm:justify-between gap-2">
                            <a href="pedido_detalhe.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-outline btn-sm w-full sm:w-auto">
                                <i class="fas fa-receipt mr-1"></i> Ver Detalhes
                            </a>
                            <?php if ($status === 'pendente'): ?>
                                <button class="btn btn-error btn-sm w-full sm:w-auto"
                                    onclick="cancelarPedido(<?= $pedido['id_pedido'] ?>)">
                                    <i class="fas fa-times mr-1"></i> Cancelar Pedido
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($logs): ?>
                            <details class="mt-3 text-xs text-gray-500">
                                <summary class="cursor-pointer font-semibold mb-1">Histórico de Status</summary>
                                <ul class="ml-2 list-disc list-inside">
                                    <?php foreach ($logs as $log): ?>
                                        <li>
                                            <strong><?= ucfirst($log['status_novo']) ?></strong>
                                            em <?= date('d/m/Y H:i', strtotime($log['alterado_em'])) ?>
                                            <?php if ($log['motivo']): ?>
                                                <br><em><?= $log['motivo'] ?></em>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function cancelarPedido(id) {
        Swal.fire({
            title: 'Cancelar Pedido?',
            input: 'textarea',
            inputLabel: 'Motivo (opcional)',
            inputPlaceholder: 'Por que deseja cancelar?',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Voltar',
            icon: 'warning'
        }).then(result => {
            if (result.isConfirmed) {
                $.post('crud/crud_pedido.php', {
                    acao: 'cancelar',
                    id_pedido: id,
                    motivo: result.value
                }, res => {
                    if (res.status === 'ok') {
                        Swal.fire('Cancelado', res.mensagem, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json').fail(() => Swal.fire('Erro', 'Não foi possível cancelar.', 'error'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (<?= json_encode($novoPedido) ?>) {
            const link = localStorage.getItem('link_whatsapp');
            if (link) {
                if (window.LOJA.teste != 1) {
                    Swal.fire({
                        title: 'Pedido Confirmado!',
                        text: 'Deseja enviar para o WhatsApp?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Enviar',
                        cancelButtonText: 'Fechar'
                    }).then(({
                        isConfirmed
                    }) => {
                        if (isConfirmed) window.open(link, '_blank');
                        localStorage.removeItem('link_whatsapp');
                    });
                } else {

                Swal.fire('Pedido feito com suceso!', '', 'success');
                }
            }
        }
    });
</script>

<?php include_once 'assets/footer.php'; ?>