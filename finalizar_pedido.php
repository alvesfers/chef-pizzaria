<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'assets/header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$carrinho = $_SESSION['carrinho'] ?? [];
$usuario = $_SESSION['usuario'];
$idUsuario = $usuario['id'];

if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

$dadosLoja = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$enderecoLoja = trim($dadosLoja['endereco_completo']);
$precoBase = floatval($dadosLoja['preco_base']);
$precoKm = floatval($dadosLoja['preco_km']);
$googleMapsKey = $dadosLoja['google'];
$limiteEntrega = isset($dadosLoja['limite_entrega']) ? floatval($dadosLoja['limite_entrega']) : null;

$stmt = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formasPgto = $pdo->query("SELECT * FROM tb_forma_pgto WHERE pagamento_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);
$regrasFrete = $pdo->query("SELECT * FROM tb_regras_frete WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

$totalProdutos = 0;
foreach ($carrinho as $item) {
    $valorItem = $item['valor_unitario'];
    foreach ($item['adicionais'] as $add) {
        if ($add['extra']) {
            $valorItem += $add['valor'];
        }
    }
    $totalProdutos += $valorItem * $item['quantidade'];
}

?>

<div class="container mx-auto px-4 py-10 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Finalizar Pedido</h1>

    <!-- Tipo de Entrega com Botões -->
    <div class="mb-6">
        <label class="font-semibold block mb-2">Escolha o tipo de entrega</label>
        <div class="flex gap-2">
            <button type="button" id="btnRetirada" class="btn btn-outline w-1/2">Retirada na Loja</button>
            <button type="button" id="btnEntrega" class="btn btn-outline w-1/2">Entrega</button>
        </div>
    </div>

    <!-- Endereço de Entrega -->
    <div id="enderecoEntrega" class="mb-6 hidden">
        <?php if (count($enderecos) > 0): ?>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="font-semibold block mb-2">Endereço de Entrega</label>
                    <select name="id_endereco" id="selectEndereco" class="select select-bordered w-full mb-2">
                        <?php foreach ($enderecos as $end): ?>
                            <option
                                value="<?= $end['id_endereco'] ?>"
                                data-cep="<?= preg_replace('/\D/', '', $end['cep']) ?>"
                                data-rua="<?= htmlspecialchars($end['rua']) ?>"
                                data-numero="<?= htmlspecialchars($end['numero']) ?>"
                                data-bairro="<?= htmlspecialchars($end['bairro']) ?>">
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

        <!-- Formulário Novo Endereço -->
        <div id="formNovoEndereco" class="hidden mt-6 space-y-4">
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block font-medium mb-1">CEP</label>
                    <input type="text" id="cep" class="input input-bordered w-full" placeholder="Digite o CEP">
                </div>
                <div class="flex items-end">
                    <button type="button" id="btnBuscarCep" class="btn btn-primary btn-square">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <div class="w-2/3">
                    <label class="block font-medium mb-1">Rua</label>
                    <input type="text" id="rua" class="input input-bordered w-full">
                </div>
                <div class="w-1/3">
                    <label class="block font-medium mb-1">Número</label>
                    <input type="text" id="numero" class="input input-bordered w-full">
                </div>
            </div>

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

            <div class="flex gap-2">
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Bairro</label>
                    <input type="text" id="bairro" class="input input-bordered w-full">
                </div>
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Apelido</label>
                    <input type="text" id="apelido" class="input input-bordered w-full" placeholder="Casa, Trabalho...">
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
    <div class="text-right font-bold text-lg mb-6 space-y-1">
        <div>Subtotal: R$<span id="valorSubtotal"><?= number_format($totalProdutos, 2, ',', '.') ?></span></div>
        <div>Frete: R$<span id="valorFreteVisual">0,00</span></div>
        <div>Distância: <span id="valorDistanciaVisual">0,00</span> km</div>
        <div class="mt-2 border-t pt-2">Total: R$<span id="valorTotal"><?= number_format($totalProdutos, 2, ',', '.') ?></span></div>
    </div>

    <!-- Formulário Finalizar Pedido -->
    <form id="formFinalizarPedido">
        <input type="hidden" name="tipo_entrega" id="inputTipoEntrega" value="retirada">
        <input type="hidden" name="id_endereco_selecionado" id="idEnderecoSelecionado" value="">
        <input type="hidden" name="valor_frete" id="valorFreteCalculado" value="0">
        <input type="hidden" name="distancia_calculada" id="distanciaCalculada" value="">
        <button type="submit" class="btn btn-primary w-full" id="btnConfirmarPedido">Confirmar Pedido</button>
    </form>
</div>
<script>
    $(document).ready(function() {
        const precoBase = <?= $precoBase ?>;
        const precoKm = <?= $precoKm ?>;
        const enderecoLoja = "<?= addslashes($enderecoLoja) ?>";
        const googleMapsApiKey = "<?= $googleMapsKey ?>";
        const regrasFrete = <?= json_encode($regrasFrete) ?>;
        const limiteEntrega = <?= $limiteEntrega !== null ? $limiteEntrega : 'null' ?>;
        let valorProdutos = <?= $totalProdutos ?>;

        function arredondarFrete(valor) {
            const parteInteira = Math.floor(valor);
            const parteDecimal = valor - parteInteira;

            if (parteDecimal <= 0.25) return parteInteira;
            if (parteDecimal <= 0.75) return parteInteira + 0.5;
            return parteInteira + 1.0;
        }

        function calcularFrete(enderecoCliente) {
            const urlGoogle = `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=${encodeURIComponent(enderecoLoja)}&destinations=${encodeURIComponent(enderecoCliente)}&key=${googleMapsApiKey}`;

            $.getJSON('/proxys/proxy_google.php?url=' + encodeURIComponent(urlGoogle), function(response) {
                if (response.status === "OK") {
                    const element = response.rows[0].elements[0];
                    if (element.status === "OK") {
                        const distanciaKm = element.distance.value / 1000;

                        if (limiteEntrega !== null && distanciaKm > limiteEntrega) {
                            Swal.fire('Atenção', 'Infelizmente você está fora da área de entrega.', 'warning');
                            $('#btnConfirmarPedido').prop('disabled', true);
                            return;
                        }

                        let frete = precoBase + (precoKm * distanciaKm);
                        const hoje = new Date();
                        const diasSemana = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
                        const diaHoje = diasSemana[hoje.getDay()];

                        regrasFrete.forEach(function(regra) {
                            if (
                                valorProdutos >= parseFloat(regra.valor_minimo) &&
                                distanciaKm <= parseFloat(regra.distancia_maxima) &&
                                diaHoje.toLowerCase() === regra.dia_semana.toLowerCase()
                            ) {
                                if (regra.tipo_regra === 'frete_gratis') {
                                    frete = 0;
                                } else if (regra.tipo_regra === 'desconto_valor') {
                                    frete -= parseFloat(regra.valor_desconto);
                                } else if (regra.tipo_regra === 'desconto_porcentagem') {
                                    frete -= frete * (parseFloat(regra.valor_desconto) / 100);
                                }
                            }
                        });

                        frete = Math.max(0, arredondarFrete(frete));

                        $('#valorFreteCalculado').val(frete.toFixed(2));
                        $('#valorFreteVisual').text(frete.toFixed(2).replace('.', ','));
                        $('#valorDistanciaVisual').text(distanciaKm.toFixed(2).replace('.', ','));

                        const novoTotal = valorProdutos + frete;
                        $('#valorTotal').text(novoTotal.toFixed(2).replace('.', ','));
                        $('#btnConfirmarPedido').prop('disabled', false);
                    }
                } else {
                    Swal.fire('Erro', 'Erro ao consultar distância no Google.', 'error');
                }
            });
        }

        $('#btnEntrega').click(function() {
            $('#inputTipoEntrega').val('entrega');
            $('#btnEntrega').addClass('btn-primary').removeClass('btn-outline');
            $('#btnRetirada').addClass('btn-outline').removeClass('btn-primary');
            $('#enderecoEntrega').removeClass('hidden');
            $('#btnConfirmarPedido').prop('disabled', true);
        });

        $('#btnRetirada').click(function() {
            $('#inputTipoEntrega').val('retirada');
            $('#btnEntrega').removeClass('btn-primary').addClass('btn-outline');
            $('#btnRetirada').removeClass('btn-outline').addClass('btn-primary');
            $('#enderecoEntrega').addClass('hidden');
            $('#valorFreteVisual').text('0,00');
            $('#valorTotal').text(valorProdutos.toFixed(2).replace('.', ','));
            $('#btnConfirmarPedido').prop('disabled', false);
        });

        $('#selectEndereco').change(function() {
            const rua = $(this).find(':selected').data('rua');
            const numero = $(this).find(':selected').data('numero');
            const bairro = $(this).find(':selected').data('bairro');
            const cep = $(this).find(':selected').data('cep');
            $('#idEnderecoSelecionado').val($(this).val());

            const enderecoCliente = `${rua} ${numero}, ${bairro}, ${cep}, Brasil`;
            calcularFrete(enderecoCliente);
        });

        $('#btnBuscarCep').click(function() {
            const cep = $('#cep').val().replace(/\D/g, '');
            if (cep.length !== 8) {
                Swal.fire('Erro', 'Digite um CEP válido com 8 dígitos.', 'error');
                return;
            }

            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                if (!data.erro) {
                    $('#rua').val(data.logradouro);
                    $('#bairro').val(data.bairro);
                } else {
                    Swal.fire('Erro', 'CEP não encontrado.', 'error');
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
                endereco_principal: true
            };

            $.post('crud/crud_endereco.php', dados, function(res) {
                if (res.status === 'ok') {
                    Swal.fire('Sucesso', res.mensagem, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Erro', 'Erro ao cadastrar endereço.', 'error');
            });
        });

        $('.btnNovoEndereco').click(function() {
            $('#formNovoEndereco').toggleClass('hidden');
        });

        $('#formFinalizarPedido').submit(function(e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.post('crud/crud_pedido.php', formData + '&acao=confirmar', function(res) {
                if (res.status === 'ok') {
                    // Salva o link do WhatsApp localmente para usar em meus_pedidos.php
                    localStorage.setItem('link_whatsapp', res.link_whatsapp);
                    window.location.href = 'meus_pedidos.php?novo_pedido=1';
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json').fail(() => {
                Swal.fire('Erro', 'Erro ao confirmar o pedido.', 'error');
            });
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>