<?php
include_once 'assets/header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario  = $_SESSION['usuario']  ?? null;

// Calcula total geral
$totalGeral = 0;
foreach ($carrinho as $item) {
    $totalGeral += $item['valor_unitario'] * $item['quantidade'];
}
?>
<div class="container mx-auto px-4 py-10 max-w-3xl" id="cart-container">
    <h1 class="text-2xl font-bold mb-6">Carrinho de Compras</h1>

    <?php if (empty($carrinho)): ?>
        <p class="text-gray-500">Seu carrinho está vazio.</p>
        <a href="index.php" class="btn btn-accent mt-4">Voltar ao cardápio</a>
    <?php else: ?>
        <div id="cart-items" class="space-y-6 mb-8">
            <?php foreach ($carrinho as $key => $item): ?>
                <div class="card bg-base-100 shadow p-4 item-card" data-key="<?= htmlspecialchars($key) ?>">
                    <!-- 1) Cabeçalho: Nome + Controles de quantidade -->
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold"><?= htmlspecialchars($item['nome_produto']) ?></h2>
                        <div class="flex items-center space-x-2">
                            <button class="btn-minus btn btn-xs btn-outline" data-key="<?= htmlspecialchars($key) ?>">−</button>
                            <input type="text"
                                class="qty-input input input-bordered w-12 text-center"
                                value="<?= $item['quantidade'] ?>"
                                readonly>
                            <button class="btn-plus btn btn-xs btn-outline" data-key="<?= htmlspecialchars($key) ?>">+</button>
                        </div>
                    </div>

                    <!-- 2) Dropdown de Sabores -->
                    <?php if (!empty($item['sabores'])): ?>
                        <div class="mt-3">
                            <button type="button"
                                class="flex justify-between items-center w-full btn btn-ghost p-0 text-left"
                                data-toggle="sabores-<?= htmlspecialchars($key) ?>">
                                <span>Sabores</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div id="sabores-<?= htmlspecialchars($key) ?>" class="hidden mt-2 border rounded p-2 bg-base-200">
                                <ul class="list-disc list-inside text-sm">
                                    <?php foreach ($item['sabores'] as $sabor): ?>
                                        <li>
                                            <?= htmlspecialchars($sabor['nome']) ?>
                                            (R$<?= number_format($sabor['valor'], 2, ',', '.') ?>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3) Dropdown de Adicionais -->
                    <?php if (!empty($item['adicionais'])): ?>
                        <div class="mt-3">
                            <button type="button"
                                class="flex justify-between items-center w-full btn btn-ghost p-0 text-left"
                                data-toggle="adicionais-<?= htmlspecialchars($key) ?>">
                                <span>Adicionais</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div id="adicionais-<?= htmlspecialchars($key) ?>" class="hidden mt-2 border rounded p-2 bg-base-200 space-y-4">
                                <?php
                                // agrupa por tipo_nome
                                $porTipo = [];
                                foreach ($item['adicionais'] as $add) {
                                    $tipoNome = $add['tipo_nome'] ?? 'Outros';
                                    $porTipo[$tipoNome][] = $add;
                                }
                                ?>
                                <?php foreach ($porTipo as $tipoNome => $lista): ?>
                                    <div>
                                        <h4 class="font-semibold"><?= htmlspecialchars($tipoNome) ?></h4>
                                        <ul class="list-disc list-inside text-sm ml-4">
                                            <?php foreach ($lista as $add): ?>
                                                <li>
                                                    <?= htmlspecialchars($add['nome']) ?>
                                                    <?php if (!empty($add['extra'])): ?>
                                                        <span class="text-red-500">
                                                            (R$<?= number_format($add['valor'], 2, ',', '.') ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 4) Total por item -->
                    <div class="mt-4 text-right text-lg font-bold">
                        Total: R$<?= number_format($item['valor_unitario'] * $item['quantidade'], 2, ',', '.') ?>
                    </div>

                    <!-- 5) Remover (só se qty === 1) -->
                    <div class="mt-2 text-right remove-wrapper" style="display: none;">
                        <button class="btn-remove btn btn-sm btn-error text-white" data-key="<?= htmlspecialchars($key) ?>">
                            Remover
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="cart-total" class="text-right text-xl font-bold mb-6">
            Total geral: R$<?= number_format($totalGeral, 2, ',', '.') ?>
        </div>

        <!-- Login ou Finalização -->
        <?php if (!$usuario): ?>
            <form id="formLoginCarrinho" method="post" class="space-y-4">
                <div>
                    <label class="block font-medium mb-1">Telefone</label>
                    <input type="tel" name="telefone" id="telefone"
                        class="input input-bordered w-full"
                        placeholder="(11) 91234-5678" required>
                </div>
                <div id="divNome" class="hidden">
                    <label class="block font-medium mb-1">Seu nome</label>
                    <input type="text" name="nome" id="nome"
                        class="input input-bordered w-full">
                </div>
                <input type="hidden" name="acao" value="cadastrar_e_logar">
                <input type="hidden" name="senha" id="senha">
                <input type="hidden" name="tipo_usuario" value="cliente">
                <input type="hidden" name="redirect" value="finalizar_pedido.php">
                <button type="submit" id="btnFinalizar" class="btn btn-primary w-full" disabled>
                    Finalizar Pedido
                </button>
            </form>
        <?php else: ?>
            <a href="finalizar_pedido.php" class="btn btn-primary w-full" <?= $aberta ? '' : 'disabled' ?>>Finalizar Pedido</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    $(function() {
        // Recarrega o carrinho inteiro
        function reloadCart() {
            location.reload();
        }

        // Toggle de dropdowns (sabores / adicionais)
        $(document).on('click', '[data-toggle]', function() {
            const tgt = '#' + $(this).data('toggle');
            $(tgt).toggleClass('hidden');
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Atualiza visibilidade do botão Remover por item
        function refreshRemoveButtons() {
            $('.item-card').each(function() {
                const qty = parseInt($(this).find('.qty-input').val(), 10) || 1;
                $(this).find('.remove-wrapper').toggle(qty === 1);
            });
        }

        // Diminuir quantidade
        $(document).on('click', '.btn-minus', function() {
            const key = $(this).data('key');
            let $card = $(this).closest('.item-card'),
                qty = parseInt($card.find('.qty-input').val(), 10) || 1;
            if (qty <= 1) return;
            qty--;
            $.ajax({
                    url: 'crud/crud_carrinho.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'update',
                        key,
                        quantidade: qty
                    })
                }).done(reloadCart)
                .fail(() => Swal.fire('Erro', 'Não foi possível atualizar a quantidade.', 'error'));
        });

        // Aumentar quantidade
        $(document).on('click', '.btn-plus', function() {
            const key = $(this).data('key');
            let $card = $(this).closest('.item-card'),
                qty = parseInt($card.find('.qty-input').val(), 10) || 0;
            qty++;
            $.ajax({
                    url: 'crud/crud_carrinho.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'update',
                        key,
                        quantidade: qty
                    })
                }).done(reloadCart)
                .fail(() => Swal.fire('Erro', 'Não foi possível atualizar a quantidade.', 'error'));
        });

        // Remover item
        $(document).on('click', '.btn-remove', function() {
            const key = $(this).data('key');
            Swal.fire({
                title: 'Remover este item?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover'
            }).then(({
                isConfirmed
            }) => {
                if (!isConfirmed) return;
                $.ajax({
                        url: 'crud/crud_carrinho.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'remove',
                            key
                        })
                    }).done(reloadCart)
                    .fail(() => Swal.fire('Erro', 'Não foi possível remover o item.', 'error'));
            });
        });

        // Checkout: verificar/cadastrar usuário por telefone
        $('#telefone').on('input', function() {
            const tel = $(this).val().replace(/\D/g, '');
            if (tel.length === 11) {
                $.post('crud/crud_usuario.php', {
                    acao: 'buscar_por_telefone',
                    telefone: tel
                }, res => {
                    if (res.status === 'ok') {
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', true).val(res.usuario.nome_usuario);
                        $('#senha').val('123456');
                        $('#formLoginCarrinho').attr('action', 'finalizar_pedido.php');
                        $('#btnFinalizar').prop('disabled', false);
                    } else if (res.status === 'nao_encontrado') {
                        $('#divNome').removeClass('hidden');
                        $('#nome').prop('disabled', false).val('');
                        $('#senha').val(tel);
                        $('#formLoginCarrinho').attr('action', 'crud/crud_usuario.php');
                        $('#btnFinalizar').prop('disabled', false);
                        Swal.fire('Novo usuário!', 'Sua senha será seu telefone (com DDD).', 'info');
                    } else {
                        $('#btnFinalizar').prop('disabled', true);
                        $('#divNome').addClass('hidden');
                    }
                }, 'json');
            } else {
                $('#btnFinalizar').prop('disabled', true);
                $('#divNome').addClass('hidden');
            }
        });

        // Submete formulário de login/cadastro
        $('#formLoginCarrinho').submit(function(e) {
            e.preventDefault();
            const form = $(this),
                action = form.attr('action'),
                data = form.serialize();
            if (action === 'crud/crud_usuario.php') {
                $.post(action, data, res => {
                    if (res.status === 'ok') {
                        window.location = res.redirect || 'finalizar_pedido.php';
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json').fail(() => Swal.fire('Erro', 'Erro na comunicação.', 'error'));
            } else {
                this.submit();
            }
        });

        // Inicializa
        refreshRemoveButtons();
    });
</script>
<?php include_once 'assets/footer.php'; ?>