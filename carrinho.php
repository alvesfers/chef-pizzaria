<?php
// carrinho.php
include_once 'assets/header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario  = $_SESSION['usuario'] ?? null;

// prepara statement para ler estoque
$stmtStock = $pdo->prepare("SELECT qtd_produto FROM tb_produto WHERE id_produto = ?");

// calcula total geral
$totalGeral = 0;
foreach ($carrinho as $item) {
    $totalGeral += $item['valor_unitario'] * $item['quantidade'];
}
?>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10" id="cart-container">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-center w-full sm:text-left sm:w-auto">Carrinho de Compras</h1>
        <?php if (!empty($carrinho)): ?>
            <button id="btnLimparCarrinho" class="btn btn-sm btn-warning ml-4">
                <i class="fas fa-trash-alt mr-1"></i> Limpar
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($carrinho)): ?>
        <p class="text-gray-500">Seu carrinho está vazio.</p>
        <a href="index.php" class="btn btn-accent mt-4">Voltar ao cardápio</a>
    <?php else: ?>
        <div class="space-y-6 mb-8">
            <?php foreach ($carrinho as $key => $item):
                // lê estoque atual do produto
                $stmtStock->execute([$item['id_produto']]);
                $row = $stmtStock->fetch(PDO::FETCH_ASSOC);
                $stock = $row ? intval($row['qtd_produto']) : 0;
                // define quantidade máxima permitida
                // se stock > 0: estoque real; se stock < 0: ilimitado mas máximo 10; se stock == 0: sem estoque
                if ($stock === 0) {
                    $maxQtd = 0;
                } elseif ($stock > 0) {
                    $maxQtd = $stock;
                } else {
                    $maxQtd = 10;
                }
            ?>
                <div class="card bg-base-100 shadow p-4 relative" data-key="<?= $key ?>" data-max="<?= $maxQtd ?>">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold"><?= htmlspecialchars($item['nome_produto']) ?></h2>
                        <div class="flex items-center space-x-2">
                            <?php if ($item['quantidade'] === 1): ?>
                                <button class="btn-remove btn btn-sm btn-outline" data-key="<?= $key ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-minus btn btn-sm btn-outline" data-key="<?= $key ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                            <?php endif; ?>
                            <input type="text"
                                value="<?= $item['quantidade'] ?>"
                                readonly
                                class="input input-bordered w-12 text-center qtde-field">
                            <button class="btn-plus btn btn-sm btn-outline"
                                data-key="<?= $key ?>"
                                <?= $item['quantidade'] >= $maxQtd ? 'disabled' : '' ?>>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Sabores -->
                    <?php if (!empty($item['sabores'])): ?>
                        <button class="btn btn-link p-0 mt-2 text-sm text-left text-primary"
                            data-toggle="sabores-<?= $key ?>">
                            Ver sabores <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="sabores-<?= $key ?>" class="hidden mt-2 ml-4 text-sm">
                            <ul class="list-disc list-inside">
                                <?php foreach ($item['sabores'] as $sabor): ?>
                                    <li>
                                        <?= htmlspecialchars($sabor['nome']) ?>
                                        (R$<?= number_format($sabor['valor'], 2, ',', '.') ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Adicionais -->
                    <?php if (!empty($item['adicionais'])): ?>
                        <button class="btn btn-link p-0 mt-2 text-sm text-left text-primary"
                            data-toggle="adicionais-<?= $key ?>">
                            Ver adicionais <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="adicionais-<?= $key ?>" class="hidden mt-2 ml-4 text-sm">
                            <ul class="list-disc list-inside">
                                <?php foreach ($item['adicionais'] as $add): ?>
                                    <li>
                                        <?= htmlspecialchars($add['nome']) ?>
                                        <?php if (!empty($add['extra'])): ?>
                                            (R$<?= number_format($add['valor'], 2, ',', '.') ?>)
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="text-right mt-4 text-lg font-bold">
                        Total: R$<?= number_format($item['valor_unitario'] * $item['quantidade'], 2, ',', '.') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-right text-xl font-bold mb-6">
            Total geral: R$<?= number_format($totalGeral, 2, ',', '.') ?>
        </div>

        <a href="finalizar_pedido.php"
            class="btn btn-primary w-full sm:w-auto"
            <?= $usuario ? '' : 'disabled' ?>>
            Finalizar Pedido
        </a>
    <?php endif; ?>
</div>

<!-- Modal de login automático -->
<input type="checkbox" id="modalLoginCarrinho" class="modal-toggle" <?= !$usuario ? 'checked' : '' ?>>
<div class="modal">
    <div class="modal-box relative">
        <label for="modalLoginCarrinho" class="btn btn-sm btn-circle absolute right-2 top-2">
            ✕
        </label>
        <h3 class="text-lg font-bold mb-4">Identifique-se para continuar</h3>
        <form id="formLoginAuto" class="space-y-4">
            <!-- (mantém o conteúdo do formulário de login) -->
        </form>
    </div>
</div>

<script>
    $(function() {
        // alterna visibilidade de detalhes
        $('[data-toggle]').on('click', function() {
            const tgt = $(this).data('toggle');
            $('#' + tgt).toggleClass('hidden');
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // função para atualizar quantidade com validação de estoque
        function atualizarCarrinho(key, delta) {
            const card = $(`[data-key="${key}"]`);
            const maxQtd = parseInt(card.data('max'), 10);
            const input = card.find('.qtde-field');
            const atual = parseInt(input.val(), 10);
            let nova;

            if (delta === 999) {
                nova = atual + 1;
            } else if (delta === -1) {
                nova = Math.max(1, atual - 1);
            } else {
                nova = delta;
            }

            if (nova > maxQtd) {
                Swal.fire('Atenção', 'Quantidade máxima disponível: ' + maxQtd, 'warning');
                return;
            }

            $.ajax({
                url: 'crud/crud_carrinho.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update',
                    key: key,
                    quantidade: delta === 999 ? 999 : (delta === -1 ? -1 : nova)
                })
            }).done(() => location.reload());
        }

        $('.btn-plus').click(function() {
            atualizarCarrinho($(this).data('key'), 999);
        });

        $('.btn-minus').click(function() {
            atualizarCarrinho($(this).data('key'), -1);
        });

        $('.btn-remove').click(function() {
            const key = $(this).data('key');
            Swal.fire({
                title: 'Remover item?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar'
            }).then(res => {
                if (res.isConfirmed) {
                    $.ajax({
                        url: 'crud/crud_carrinho.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'remove',
                            key
                        })
                    }).done(() => location.reload());
                }
            });
        });

        $('#btnLimparCarrinho').click(function() {
            Swal.fire({
                title: 'Limpar carrinho?',
                text: 'Todos os itens serão removidos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, limpar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'crud/crud_carrinho.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'clear'
                        })
                    }).done(() => location.reload());
                }
            });
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>