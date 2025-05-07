<?php
require_once 'assets/conexao.php';
include_once 'assets/header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$idUsuario = $_SESSION['usuario']['id'];
$novoPedido = isset($_GET['novo_pedido']) && $_GET['novo_pedido'] == 1;

$stmt = $pdo->prepare("SELECT * FROM tb_pedido WHERE id_usuario = ? ORDER BY criado_em DESC");
$stmt->execute([$idUsuario]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6 max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Meus Pedidos</h1>

    <?php if (count($pedidos) === 0): ?>
        <p class="text-center text-gray-500">Você ainda não fez nenhum pedido.</p>
    <?php else: ?>
        <div x-data="{ filtro: 'todos' }">
            <label class="block font-medium mb-2">Filtrar por status:</label>
            <select x-model="filtro" class="select select-bordered w-full mb-6">
                <option value="todos">Todos</option>
                <option value="pendente">Pendente</option>
                <option value="preparando">Preparando</option>
                <option value="saiu">Saiu para entrega</option>
                <option value="entregue">Entregue</option>
                <option value="cancelado">Cancelado</option>
            </select>

            <div class="space-y-4">
                <?php foreach ($pedidos as $pedido): ?>
                    <?php
                    $badgeClass = match ($pedido['status_pedido']) {
                        'pendente' => 'badge-warning',
                        'cancelado' => 'badge-error',
                        'entregue' => 'badge-success',
                        'preparando' => 'badge-info',
                        'saiu' => 'badge-primary',
                        default => 'badge-outline',
                    };

                    $stmtItens = $pdo->prepare("SELECT * FROM tb_item_pedido WHERE id_pedido = ?");
                    $stmtItens->execute([$pedido['id_pedido']]);
                    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

                    $stmtLogs = $pdo->prepare("SELECT * FROM tb_pedido_status_log WHERE id_pedido = ? ORDER BY alterado_em DESC");
                    $stmtLogs->execute([$pedido['id_pedido']]);
                    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div x-show="filtro === 'todos' || filtro === '<?= $pedido['status_pedido'] ?>'" x-cloak x-data="{ aberto: false }" class="bg-base-100 shadow rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Pedido #<?= $pedido['id_pedido'] ?></h2>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($pedido['status_pedido']) ?></span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?>
                            <br>
                            <?= htmlspecialchars($pedido['forma_pagamento']) ?> — <?= ucfirst($pedido['tipo_entrega']) ?>
                        </div>
                        <div class="mt-2 flex justify-between text-sm">
                            <span>Produtos: <strong>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></strong></span>
                            <span>Frete: <strong>R$ <?= number_format($pedido['valor_frete'], 2, ',', '.') ?></strong></span>
                        </div>
                        <div class="text-right mt-2 text-lg font-bold">
                            Total: R$ <?= number_format($pedido['valor_total'] + $pedido['valor_frete'], 2, ',', '.') ?>
                        </div>

                        <div class="mt-3">
                            <button @click="aberto = !aberto" class="btn btn-sm btn-outline w-full">
                                <i :class="aberto ? 'fa fa-chevron-up' : 'fa fa-chevron-down'" class="mr-2"></i>
                                Ver Itens
                            </button>

                            <div x-show="aberto" class="mt-3 space-y-3" x-transition>
                                <?php foreach ($itens as $item): ?>
                                    <?php
                                    $stmtSabores = $pdo->prepare("SELECT ps.*, p.nome_produto FROM tb_item_pedido_sabor ps JOIN tb_produto p ON ps.id_produto = p.id_produto WHERE id_item_pedido = ?");
                                    $stmtSabores->execute([$item['id_item_pedido']]);
                                    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);

                                    $stmtAdds = $pdo->prepare("SELECT * FROM tb_item_adicional WHERE id_item_pedido = ?");
                                    $stmtAdds->execute([$item['id_item_pedido']]);
                                    $adicionais = $stmtAdds->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="bg-base-200 rounded p-3 text-sm">
                                        <div class="font-medium"><?= $item['quantidade'] ?>x <?= htmlspecialchars($item['nome_exibicao']) ?> — R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></div>

                                        <?php if (count($sabores) > 0): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Sabores:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($sabores as $sabor): ?>
                                                        <li><?= htmlspecialchars($sabor['nome_produto']) ?> (<?= $sabor['proporcao'] ?>%)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (count($adicionais) > 0): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Adicionais:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($adicionais as $add): ?>
                                                        <li><?= htmlspecialchars($add['nome_adicional']) ?> — R$ <?= number_format($add['valor_adicional'], 2, ',', '.') ?></li>
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

                            <?php if ($pedido['status_pedido'] === 'pendente'): ?>
                                <button class="btn btn-error btn-sm w-full sm:w-auto" onclick="cancelarPedido(<?= $pedido['id_pedido'] ?>)">
                                    <i class="fas fa-times mr-1"></i> Cancelar Pedido
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if (count($logs) > 0): ?>
                            <details class="mt-3 text-xs text-gray-500">
                                <summary class="cursor-pointer font-semibold mb-1">Histórico de Status</summary>
                                <ul class="ml-2 list-disc list-inside">
                                    <?php foreach ($logs as $log): ?>
                                        <li>
                                            <strong><?= ucfirst($log['status_novo']) ?></strong> em <?= date('d/m/Y H:i', strtotime($log['alterado_em'])) ?>
                                            <?php if ($log['motivo']): ?>
                                                <br><em><?= htmlspecialchars($log['motivo']) ?></em>
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
    const cancelarPedido = (id) => {
        Swal.fire({
            title: 'Cancelar Pedido?',
            input: 'textarea',
            inputLabel: 'Motivo do cancelamento (opcional)',
            inputPlaceholder: 'Digite o motivo...',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Voltar',
            icon: 'warning'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('crud/crud_pedido.php', {
                    acao: 'cancelar',
                    id_pedido: id,
                    motivo: result.value
                }, function(res) {
                    if (res.status === 'ok') {
                        Swal.fire('Cancelado', res.mensagem, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json').fail(() => {
                    Swal.fire('Erro', 'Erro ao cancelar o pedido.', 'error');
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const novoPedido = <?= json_encode($novoPedido) ?>;
        if (novoPedido) {
            const linkWhatsapp = localStorage.getItem('link_whatsapp');
            if (linkWhatsapp) {
                Swal.fire({
                    title: 'Pedido Confirmado!',
                    text: 'Deseja enviar o pedido para o WhatsApp?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Enviar no WhatsApp',
                    cancelButtonText: 'Fechar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(linkWhatsapp, '_blank');
                    }
                    localStorage.removeItem('link_whatsapp');
                });
            }
        }
    });
</script>

<?php include_once 'assets/footer.php'; ?>