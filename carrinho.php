<?php
include_once 'assets/header.php';

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario  = $_SESSION['usuario'] ?? null;

// Calcula total
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
            <?php foreach ($carrinho as $key => $item): ?>
                <div class="card bg-base-100 shadow p-4 relative" data-key="<?= $key ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold"><?= htmlspecialchars($item['nome_produto']) ?></h2>
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($item['quantidade'] == 1): ?>
                                <button class="btn-remove btn btn-sm btn-outline" data-key="<?= $key ?>"><i class="fas fa-trash"></i></button>
                            <?php else: ?>
                                <button class="btn-minus btn btn-sm btn-outline" data-key="<?= $key ?>"><i class="fas fa-minus"></i></button>
                            <?php endif; ?>
                            <input type="text" value="<?= $item['quantidade'] ?>" readonly class="input input-bordered w-12 text-center">
                            <button class="btn-plus btn btn-sm btn-outline" data-key="<?= $key ?>"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <!-- Sabores -->
                    <?php if (!empty($item['sabores'])): ?>
                        <button class="btn btn-link p-0 mt-2 text-sm text-left text-primary" data-toggle="sabores-<?= $key ?>">
                            Ver sabores <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="sabores-<?= $key ?>" class="hidden mt-2 ml-4 text-sm">
                            <ul class="list-disc list-inside">
                                <?php foreach ($item['sabores'] as $sabor): ?>
                                    <li><?= htmlspecialchars($sabor['nome']) ?> (R$<?= number_format($sabor['valor'], 2, ',', '.') ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Adicionais -->
                    <?php if (!empty($item['adicionais'])): ?>
                        <button class="btn btn-link p-0 mt-2 text-sm text-left text-primary w-100" data-toggle="adicionais-<?= $key ?>">
                            Ver adicionais <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="adicionais-<?= $key ?>" class="hidden mt-2 ml-4 text-sm">
                            <ul class="list-disc list-inside">
                                <?php foreach ($item['adicionais'] as $add): ?>
                                    <li><?= htmlspecialchars($add['nome']) ?><?php if (!empty($add['extra'])): ?> (R$<?= number_format($add['valor'], 2, ',', '.') ?>)<?php endif; ?></li>
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

        <a href="finalizar_pedido.php" class="btn btn-primary w-full sm:w-auto" <?= $usuario ? '' : 'disabled' ?>>
            Finalizar Pedido
        </a>
    <?php endif; ?>
</div>


<!-- Modal de login automático -->
<input type="checkbox" id="modalLoginCarrinho" class="modal-toggle" <?= !$usuario ? 'checked' : '' ?>>
<div class="modal">
    <div class="modal-box relative">
        <label for="modalLoginCarrinho" class="btn btn-sm btn-circle absolute right-2 top-2">✕</label>
        <h3 class="text-lg font-bold mb-4">Identifique-se para continuar</h3>
        <form id="formLoginAuto" class="space-y-4">
            <div>
                <label class="block font-medium mb-1">Telefone</label>
                <input type="tel" name="telefone" id="telefone" class="input input-bordered w-full" required placeholder="(11) 91234-5678">
            </div>

            <div id="divNome" class="hidden">
                <label class="block font-medium mb-1">Nome</label>
                <input type="text" name="nome" id="nome" class="input input-bordered w-full">
                <p class="text-sm text-gray-500 mt-1">Sua senha será o telefone com DDD.</p>
            </div>

            <input type="hidden" name="acao" id="acao" value="buscar_por_telefone">
            <input type="hidden" name="senha" id="senha" value="">
            <input type="hidden" name="tipo_usuario" value="cliente">
            <input type="hidden" name="redirect" value="carrinho.php">

            <button type="submit" id="btnLoginAuto" class="btn btn-primary w-full">Continuar</button>
        </form>
    </div>
</div>

<script>
    $(function() {
        $('#telefone').mask('(00) 00000-0000');

        $('#formLoginAuto').on('submit', function(e) {
            e.preventDefault();
            const tel = $('#telefone').val().replace(/\D/g, '');
            const nome = $('#nome').val();
            const acao = $('#acao').val();
            const form = $(this);

            if (tel.length !== 11) {
                Swal.fire('Telefone inválido', 'Digite um telefone válido com DDD.', 'warning');
                return;
            }

            if (acao === 'buscar_por_telefone') {
                $.post('crud/crud_usuario.php', {
                        acao: 'buscar_por_telefone',
                        telefone: tel
                    }, 'json')
                    .done(res => {
                        if (res.status === 'ok') {
                            Swal.fire('Sucesso', 'Login feito com sucesso!', 'success');
                            location.reload();
                        } else {
                            $('#divNome').removeClass('hidden');
                            $('#acao').val('cadastrar_e_logar');
                            $('#senha').val(tel);
                            $('#btnLoginAuto').text('Criar Conta');
                        }
                    });
            } else if (acao === 'cadastrar_e_logar') {
                if (!nome.trim()) {
                    Swal.fire('Informe seu nome', 'Preencha o nome para criar a conta.', 'warning');
                    return;
                }

                const data = {
                    acao: 'cadastrar_e_logar',
                    nome: nome.trim(),
                    telefone: tel,
                    senha: tel,
                    tipo_usuario: 'cliente',
                    redirect: 'carrinho.php'
                };

                $.post('crud/crud_usuario.php', data, 'json')
                    .done(res => {
                        if (res.status === 'ok') {
                            Swal.fire('Sucesso', 'Conta criada com sucesso!', 'success');
                            location.reload();
                        } else {
                            Swal.fire('Erro', res.mensagem || 'Erro ao cadastrar.', 'error');
                        }
                    });
            }
        });

        // Alternar visibilidade de sabores/adicionais
        $('[data-toggle]').on('click', function() {
            const tgt = $(this).data('toggle');
            $('#' + tgt).toggleClass('hidden');
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Aumentar quantidade
        $('.btn-plus').click(function() {
            const key = $(this).data('key');
            $.ajax({
                url: 'crud/crud_carrinho.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update',
                    key,
                    quantidade: 999
                }) // será incrementado no back
            }).done(() => location.reload());
        });

        // Diminuir quantidade
        $('.btn-minus').click(function() {
            const key = $(this).data('key');
            $.ajax({
                url: 'crud/crud_carrinho.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update',
                    key,
                    quantidade: -1
                }) // será decrementado no back
            }).done(() => location.reload());
        });

        // Remover item
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
            }).then((result) => {
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