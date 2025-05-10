<?php
include_once 'assets/header.php';

// Se não estiver logado, redireciona para o login
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$idUsuario  = $_SESSION['usuario']['id'];
$novoPedido = isset($_GET['novo_pedido']) && $_GET['novo_pedido'] == 1;

// Prepara statements para reutilização
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

// Busca todos os pedidos do usuário
$stmtPedidos->execute([$idUsuario]);
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-6 max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Meus Pedidos</h1>

    <?php if (empty($pedidos)): ?>
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
                    // Badge de status
                    switch ($pedido['status_pedido']) {
                        case 'pendente':
                            $badgeClass = 'badge-warning';
                            break;
                        case 'preparando':
                            $badgeClass = 'badge-info';
                            break;
                        case 'saiu':
                            $badgeClass = 'badge-primary';
                            break;
                        case 'entregue':
                            $badgeClass = 'badge-success';
                            break;
                        case 'cancelado':
                            $badgeClass = 'badge-error';
                            break;
                        default:
                            $badgeClass = 'badge-outline';
                            break;
                    }

                    // Itens e logs deste pedido
                    $stmtItens->execute([$pedido['id_pedido']]);
                    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

                    $stmtStatusLog->execute([$pedido['id_pedido']]);
                    $logs = $stmtStatusLog->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div
                        x-show="filtro === 'todos' || filtro === '<?= $pedido['status_pedido'] ?>'"
                        x-cloak
                        x-data="{ aberto: false }"
                        class="bg-base-100 shadow rounded-lg p-4">
                        <!-- Cabeçalho -->
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?></h2>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($pedido['status_pedido'])) ?></span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?>
                            &mdash; <?= ucfirst(htmlspecialchars($pedido['tipo_entrega'])) ?>
                            &mdash; <?= htmlspecialchars($pedido['forma_pagamento']) ?>
                        </div>
                        <div class="mt-2 flex justify-between text-sm">
                            <span>Produtos: <strong>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></strong></span>
                            <span>Frete: <strong>R$ <?= number_format($pedido['valor_frete'],  2, ',', '.') ?></strong></span>
                        </div>
                        <div class="text-right mt-2 text-lg font-bold">
                            Total: R$ <?= number_format($pedido['valor_total'] + $pedido['valor_frete'], 2, ',', '.') ?>
                        </div>

                        <!-- Botão mostrar itens -->
                        <div class="mt-3">
                            <button
                                @click="aberto = !aberto"
                                class="btn btn-sm btn-outline w-full flex justify-center items-center gap-2">
                                <i :class="aberto ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                                Ver Itens
                            </button>

                            <div x-show="aberto" x-transition class="mt-3 space-y-3">
                                <?php foreach ($itens as $item): ?>
                                    <?php
                                    // Sabores
                                    $stmtSabores->execute([$item['id_item_pedido']]);
                                    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);

                                    // Adicionais
                                    $stmtAdds->execute([$item['id_item_pedido']]);
                                    $adicionais = $stmtAdds->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="bg-base-200 rounded p-3 text-sm">
                                        <div class="font-medium">
                                            <?= (int)$item['quantidade'] ?>× <?= htmlspecialchars($item['nome_exibicao']) ?>
                                            — R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?>
                                        </div>

                                        <?php if (!empty($sabores)): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Sabores:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($sabores as $s): ?>
                                                        <li><?= htmlspecialchars($s['nome_produto']) ?> (<?= (int)$s['proporcao'] ?>%)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($adicionais)): ?>
                                            <div class="ml-2 mt-1 text-xs text-gray-600">
                                                <div class="mb-1 font-semibold">Adicionais:</div>
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($adicionais as $a): ?>
                                                        <li>
                                                            <?= htmlspecialchars($a['nome_adicional']) ?>
                                                            — R$ <?= number_format($a['valor_adicional'], 2, ',', '.') ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="mt-4 flex flex-col sm:flex-row sm:justify-between gap-2">
                            <a
                                href="pedido_detalhe.php?id=<?= htmlspecialchars($pedido['id_pedido']) ?>"
                                class="btn btn-outline btn-sm w-full sm:w-auto flex items-center gap-1">
                                <i class="fas fa-receipt"></i> Ver Detalhes
                            </a>
                            <?php if ($pedido['status_pedido'] === 'pendente'): ?>
                                <button
                                    class="btn btn-error btn-sm w-full sm:w-auto flex items-center gap-1"
                                    onclick="cancelarPedido(<?= (int)$pedido['id_pedido'] ?>)">
                                    <i class="fas fa-times"></i> Cancelar Pedido
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Histórico de status -->
                        <?php if (!empty($logs)): ?>
                            <details class="mt-3 text-xs text-gray-500">
                                <summary class="cursor-pointer font-semibold mb-1">Histórico de Status</summary>
                                <ul class="ml-2 list-disc list-inside">
                                    <?php foreach ($logs as $log): ?>
                                        <li>
                                            <strong><?= ucfirst(htmlspecialchars($log['status_novo'])) ?></strong>
                                            em <?= date('d/m/Y H:i', strtotime($log['alterado_em'])) ?>
                                            <?php if (!empty($log['motivo'])): ?>
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
                            Swal.fire('Cancelado', res.mensagem, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Erro', res.mensagem, 'error');
                        }
                    }, 'json')
                    .fail(() => Swal.fire('Erro', 'Não foi possível cancelar.', 'error'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (<?= json_encode($novoPedido) ?>) {
            const link = localStorage.getItem('link_whatsapp');
            if (link) {
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
            }
        }
    });
</script>

<?php include_once 'assets/footer.php'; ?>