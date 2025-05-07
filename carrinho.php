<?php
include_once 'assets/header.php';

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

                            <!-- Sabores -->
                            <?php if (!empty($item['sabores'])): ?>
                                <p class="text-sm text-gray-600 mt-1">Sabores:</p>
                                <ul class="text-sm list-disc list-inside ml-4 mb-2">
                                    <?php foreach ($item['sabores'] as $sabor): ?>
                                        <li><?= htmlspecialchars($sabor['nome']) ?> (R$<?= number_format($sabor['valor'], 2, ',', '.') ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Adicionais -->
                            <?php if (!empty($item['adicionais'])): ?>
                                <p class="text-sm text-gray-600 mt-1">Adicionais:</p>
                                <ul class="text-sm list-disc list-inside ml-4 mb-2">
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

                            <!-- Controles de quantidade -->
                            <div class="flex items-center gap-2 mt-2">
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
                        </div>

                        <!-- Remover item -->
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

        <!-- Login ou Finalização -->
        <?php if (!$usuario): ?>
            <form id="formLoginCarrinho" method="post" action="crud/crud_usuario.php" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Telefone</label>
                    <input type="tel" name="telefone" id="telefone" class="input input-bordered w-full" placeholder="(11) 91234-5678" required>
                </div>
                <div id="divNome" class="hidden">
                    <label class="block font-medium mb-1">Seu nome</label>
                    <input type="text" name="nome" id="nome" class="input input-bordered w-full">
                </div>
                <input type="hidden" name="acao" value="cadastrar_e_logar">
                <input type="hidden" name="senha" id="senha">
                <input type="hidden" name="tipo_usuario" value="cliente">
                <input type="hidden" name="redirect" value="finalizar_pedido.php">
                <button type="submit" id="btnFinalizar" class="btn btn-primary w-full" disabled>Finalizar Pedido</button>
            </form>
        <?php else: ?>
            <a href="finalizar_pedido.php" class="btn btn-primary w-full">Finalizar Pedido</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function () {
        $('#telefone').on('input', function () {
            let telefone = $(this).val().replace(/\D/g, '');

            if (telefone.length === 11) {
                $.post('crud/crud_usuario.php', {
                    acao: 'buscar_por_telefone',
                    telefone: telefone
                }, function (response) {
                    if (response.status === 'ok') {
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', true).val(response.usuario.nome_usuario);
                        $('#senha').val('123456');
                        $('#formLoginCarrinho').attr('action', 'finalizar_pedido.php');
                        $('#btnFinalizar').prop('disabled', false);
                    } else if (response.status === 'nao_encontrado') {
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', false).val('');
                        $('#senha').val(telefone);
                        $('#formLoginCarrinho').attr('action', 'crud/crud_usuario.php');
                        $('#btnFinalizar').prop('disabled', false);

                        Swal.fire({
                            icon: 'info',
                            title: 'Novo usuário!',
                            text: 'Sua senha será o número do seu telefone (com DDD).',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        $('#btnFinalizar').prop('disabled', true);
                        $('#divNome').addClass('hidden');
                        $('#nome').val('');
                    }
                }, 'json');
            } else {
                $('#btnFinalizar').prop('disabled', true);
                $('#divNome').addClass('hidden');
                $('#nome').val('');
            }
        });

        $('#formLoginCarrinho').submit(function (e) {
            e.preventDefault();

            let form = $(this);
            let action = form.attr('action');
            let formData = form.serialize();

            if (action === 'crud/crud_usuario.php') {
                $.post(action, formData, function (response) {
                    if (response.status === 'ok') {
                        window.location.href = response.redirect || 'finalizar_pedido.php';
                    } else {
                        Swal.fire('Erro', response.mensagem, 'error');
                    }
                }, 'json').fail(function () {
                    Swal.fire('Erro', 'Erro na comunicação com o servidor.', 'error');
                });
            } else {
                this.submit();
            }
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>
