<?php
include_once 'header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario = $_SESSION['usuario'] ?? null;

// Total
$total = 0;
foreach ($carrinho as $item) {
    $total += $item['valor_unitario'] * $item['quantidade'];
}
?>

<div class="container mx-auto px-4 py-10 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Carrinho de Compras</h1>

    <?php if (count($carrinho) === 0): ?>
        <p class="text-gray-500">Seu carrinho está vazio.</p>
        <a href="index.php" class="btn btn-accent mt-4">Voltar ao cardápio</a>
    <?php else: ?>
        <div class="space-y-6 mb-8">
            <?php foreach ($carrinho as $index => $item): ?>
                <div class="card bg-base-100 shadow p-4">
                    <div class="flex justify-between items-start">
                        <div class="w-full">
                            <h2 class="text-lg font-semibold"><?= htmlspecialchars($item['nome_produto']) ?></h2>
                            
                            <!-- Controles de quantidade -->
                            <div class="flex items-center gap-2 mt-1 mb-2">
                                <form action="atualizar_quantidade.php" method="post" class="inline">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <input type="hidden" name="acao" value="diminuir">
                                    <button type="submit" class="btn btn-xs btn-outline">-</button>
                                </form>

                                <span class="font-medium text-sm"><?= $item['quantidade'] ?></span>

                                <form action="atualizar_quantidade.php" method="post" class="inline">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <input type="hidden" name="acao" value="aumentar">
                                    <button type="submit" class="btn btn-xs btn-outline">+</button>
                                </form>
                            </div>

                            <?php if (!empty($item['adicionais'])): ?>
                                <ul class="text-sm mt-2 list-disc list-inside">
                                    <?php foreach ($item['adicionais'] as $add): ?>
                                        <li>
                                            <?= htmlspecialchars($add['nome']) ?>
                                            <?php if ($add['extra']): ?>
                                                <span class="text-red-500">(R$<?= number_format($add['valor'], 2, ',', '.') ?>)</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="remover_item.php">
                            <input type="hidden" name="index" value="<?= $index ?>">
                            <button type="submit" class="btn btn-sm btn-error text-white">Remover</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-right text-xl font-bold mb-6">
            Total: R$<?= number_format($total, 2, ',', '.') ?>
        </div>

        <?php if (!$usuario): ?>
            <form action="criar_usuario.php" method="post" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Seu nome</label>
                    <input type="text" name="nome" required class="input input-bordered w-full">
                </div>
                <div>
                    <label class="block font-medium mb-1">Telefone</label>
                    <input type="tel" name="telefone" required class="input input-bordered w-full" placeholder="(11) 91234-5678">
                </div>
                <input type="hidden" name="redirect" value="finalizar_pedido.php">
                <button type="submit" class="btn btn-primary w-full">Finalizar Pedido</button>
            </form>
        <?php else: ?>
            <a href="finalizar_pedido.php" class="btn btn-primary w-full">Finalizar Pedido</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
