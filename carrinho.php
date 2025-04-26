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
            <form id="formLoginCarrinho" method="post" action="crud_usuario.php" class="space-y-4">
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
    $(document).ready(function() {
        $('#telefone').on('input', function() {
            let telefone = $(this).val().replace(/\D/g, '');

            if (telefone.length == 11) {
                $.post('crud_usuario.php', {
                    acao: 'buscar_por_telefone',
                    telefone: telefone
                }, function(response) {
                    if (response.status === 'ok') {
                        // Usuário encontrado
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', true);
                        $('#nome').val(response.usuario.nome_usuario);
                        $('#senha').val('123456'); // Só para evitar problema de campo vazio (não será usado)
                        $('#formLoginCarrinho').attr('action', 'finalizar_pedido.php');
                        $('#btnFinalizar').prop('disabled', false);
                    } else if (response.status === 'nao_encontrado') {
                        // Novo usuário
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', false).val('');
                        $('#senha').val(telefone); // Senha será igual ao telefone
                        $('#formLoginCarrinho').attr('action', 'crud_usuario.php');
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

        $('#formLoginCarrinho').submit(function(e) {
            e.preventDefault(); // Sempre previne primeiro

            let form = $(this);
            let action = form.attr('action');
            let formData = form.serialize();

            if (action === 'crud_usuario.php') {
                // Se o destino for crud_usuario.php => Envia via AJAX
                $.post(action, formData, function(response) {
                    if (response.status === 'ok') {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            Swal.fire('Sucesso', response.mensagem, 'success');
                        }
                    } else {
                        Swal.fire('Erro', response.mensagem, 'error');
                    }
                }, 'json').fail(function() {
                    Swal.fire('Erro', 'Erro na comunicação com o servidor.', 'error');
                });
            } else {
                // Se o destino for finalizar_pedido.php => Submit normal
                this.submit();
            }
        });
    });
</script>



<?php include_once 'footer.php'; ?>