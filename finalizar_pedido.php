<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario = $_SESSION['usuario'];
$idUsuario = $usuario['id'];

// Redireciona se carrinho estiver vazio
if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

// Busca endereços do usuário
$stmt = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formas de pagamento
$formasPgto = $pdo->query("SELECT * FROM tb_forma_pgto WHERE pagamento_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

// Total produtos
$totalProdutos = 0;
foreach ($carrinho as $item) {
    $totalProdutos += $item['valor_unitario'] * $item['quantidade'];
}

$frete = 0; // default
?>

<div class="container mx-auto px-4 py-10 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Finalizar Pedido</h1>

    <!-- Escolher entrega ou retirada -->
    <div class="mb-6">
        <label class="font-semibold block mb-2">Tipo de Entrega</label>
        <select id="tipoEntrega" name="tipo_entrega" class="select select-bordered w-full">
            <option value="retirada">Retirada na loja</option>
            <option value="entrega">Entrega</option>
        </select>
    </div>

    <!-- Endereços -->
    <div id="enderecoEntrega" class="mb-6 hidden">
        <?php if (count($enderecos) > 0): ?>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="font-semibold block mb-2">Endereço de Entrega</label>
                    <select name="id_endereco" id="selectEndereco" class="select select-bordered w-full mb-2">
                        <?php foreach ($enderecos as $end): ?>
                            <option value="<?= $end['id_endereco'] ?>">
                                <?= htmlspecialchars($end['rua']) ?>, <?= $end['numero'] ?> - <?= $end['bairro'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" class="btnNovoEndereco btn btn-success btn-square mb-2">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <p class="text-sm text-red-500 mb-2">Você ainda não cadastrou um endereço.</p>

            <button type="button" class="btnNovoEndereco btn btn-sm btn-outline w-full mt-2">Cadastrar Novo Endereço</button>
        <?php endif; ?>

        <!-- Novo Endereço -->
        <div id="formNovoEndereco" class="hidden space-y-4">

            <!-- CEP e Botão de Buscar -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block font-medium mb-1">CEP</label>
                    <input type="text" id="cep" class="input input-bordered w-full" placeholder="Digite o CEP" required>
                </div>
                <div class="flex items-end">
                    <button type="button" id="btnBuscarCep" class="btn btn-primary btn-square">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Rua e Número -->
            <div class="flex gap-2">
                <div class="w-2/3">
                    <label class="block font-medium mb-1">Rua</label>
                    <input type="text" id="rua" class="input input-bordered w-full" required>
                </div>
                <div class="w-1/3">
                    <label class="block font-medium mb-1">Número</label>
                    <input type="text" id="numero" class="input input-bordered w-full" required>
                </div>
            </div>

            <!-- Complemento e Ponto de Referência -->
            <div class="flex gap-2">
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Complemento</label>
                    <input type="text" id="complemento" class="input input-bordered w-full">
                </div>
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Ponto de Referência</label>
                    <input type="text" id="ponto_referencia" class="input input-bordered w-full">
                </div>
            </div>

            <!-- Bairro e Apelido -->
            <div class="flex gap-2">
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Bairro</label>
                    <input type="text" id="bairro" class="input input-bordered w-full" required>
                </div>
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Apelido do Endereço</label>
                    <input type="text" id="apelido" class="input input-bordered w-full" placeholder="Casa, Trabalho..." required>
                </div>
            </div>

            <button type="button" id="btnSalvarEndereco" class="btn btn-success w-full mt-4">Salvar Endereço</button>

        </div>


    </div>

    <!-- Forma de pagamento -->
    <div class="mb-6">
        <label class="font-semibold block mb-2">Forma de Pagamento</label>
        <select name="forma_pagamento" class="select select-bordered w-full">
            <?php foreach ($formasPgto as $pg): ?>
                <option value="<?= $pg['nome_pgto'] ?>">
                    <?= htmlspecialchars($pg['nome_pgto']) ?> <?= $pg['is_online'] ? '(Online)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Resumo -->
    <div class="text-right font-bold text-lg mb-6">
        Total: R$<span id="valorTotal"><?= number_format($totalProdutos, 2, ',', '.') ?></span>
    </div>

    <form action="confirmar_pedido.php" method="post">
        <input type="hidden" name="tipo_entrega" id="inputTipoEntrega" value="retirada">
        <input type="hidden" name="id_endereco_selecionado" id="idEnderecoSelecionado" value="">
        <input type="hidden" name="valor_frete" value="<?= $frete ?>">
        <button type="submit" class="btn btn-primary w-full">Confirmar Pedido</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#tipoEntrega').on('change', function() {
            const tipo = $(this).val();
            $('#inputTipoEntrega').val(tipo);
            if (tipo === 'entrega') {
                $('#enderecoEntrega').removeClass('hidden');
            } else {
                $('#enderecoEntrega').addClass('hidden');
            }
        });

        $('#selectEndereco').on('change', function() {
            $('#idEnderecoSelecionado').val($(this).val());
        });

        $('.btnNovoEndereco').click(function() {
            $('#formNovoEndereco').toggleClass('hidden');
        });

        $('#btnBuscarCep').click(function() {
            const cep = $('#cep').val().replace(/\D/g, '');

            if (cep.length !== 8) {
                Swal.fire('Erro', 'CEP inválido.', 'error');
                return;
            }

            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                if (data.erro) {
                    Swal.fire('Erro', 'CEP não encontrado.', 'error');
                } else {
                    $('#rua').val(data.logradouro);
                    $('#bairro').val(data.bairro);
                }
            }).fail(function() {
                Swal.fire('Erro', 'Erro ao buscar o CEP.', 'error');
            });
        });

        $('#btnSalvarEndereco').click(function() {
            const dados = {
                acao: 'cadastrar',
                cep: $('#cep').val(),
                rua: $('#rua').val(),
                numero: $('#numero').val(),
                complemento: $('#complemento').val(),
                ponto_referencia: $('#ponto_referencia').val(),
                bairro: $('#bairro').val(),
                apelido: $('#apelido').val(),
                endereco_principal: 1
            };

            $.post('crud_endereco.php', dados, function(res) {
                if (res.status === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Endereço salvo!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        });

    });
</script>


<?php include_once 'footer.php'; ?>