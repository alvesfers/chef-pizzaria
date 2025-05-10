<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'assets/header.php';

// força login
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$carrinho   = $_SESSION['carrinho'] ?? [];
$usuario    = $_SESSION['usuario'];
$idUsuario  = $usuario['id'];

// se carrinho vazio, volta
if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

// dados da loja
$dadosLoja     = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$enderecoLoja  = trim($dadosLoja['endereco_completo']);
$precoBase     = floatval($dadosLoja['preco_base']);
$precoKm       = floatval($dadosLoja['preco_km']);
$googleMapsKey = $dadosLoja['google'];
$limiteEntrega = isset($dadosLoja['limite_entrega'])
    ? floatval($dadosLoja['limite_entrega'])
    : null;

// endereços do usuário
$stmt = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// formas de pagamento e regras de frete
$formasPgto  = $pdo->query("SELECT * FROM tb_forma_pgto WHERE pagamento_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);
$regrasFrete = $pdo->query("SELECT * FROM tb_regras_frete WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

//  total dos produtos = sum(valor_unitario * quantidade)
$totalProdutos = 0;
foreach ($carrinho as $item) {
    $totalProdutos += $item['valor_unitario'] * $item['quantidade'];
}
?>

<div class="container mx-auto px-4 py-10 max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Finalizar Pedido</h1>

    <!-- Tipo de Entrega -->
    <div class="mb-6">
        <label class="font-semibold block mb-2">Tipo de entrega</label>
        <div class="flex gap-2">
            <button type="button" id="btnRetirada"
                class="btn btn-primary w-1/2">Retirada na Loja</button>
            <button type="button" id="btnEntrega"
                class="btn btn-outline w-1/2">Entrega</button>
        </div>
    </div>

    <!-- Endereço de Entrega -->
    <div id="enderecoEntrega" class="mb-6 hidden">
        <?php if ($enderecos): ?>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="font-semibold block mb-2">Endereço de Entrega</label>
                    <select name="id_endereco" id="selectEndereco"
                        class="select select-bordered w-full">
                        <?php foreach ($enderecos as $end): ?>
                            <option value="<?= $end['id_endereco'] ?>"
                                data-rua="<?= htmlspecialchars($end['rua']) ?>"
                                data-numero="<?= htmlspecialchars($end['numero']) ?>"
                                data-bairro="<?= htmlspecialchars($end['bairro']) ?>"
                                data-cep="<?= preg_replace('/\D/', '', $end['cep']) ?>">
                                <?= htmlspecialchars($end['rua']) ?>, <?= $end['numero'] ?> —
                                <?= htmlspecialchars($end['bairro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btnNovoEndereco btn btn-success btn-square mt-auto">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

        <?php else: ?>
            <p class="text-sm text-red-500 mb-2">Nenhum endereço cadastrado.</p>
            <button type="button" class="btnNovoEndereco btn btn-sm btn-outline w-full">
                Cadastrar endereço
            </button>
        <?php endif; ?>

        <!-- Formulário para novo endereço -->
        <div id="formNovoEndereco" class="hidden mt-6 space-y-4">
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block font-medium mb-1">CEP</label>
                    <input type="text" id="cep" class="input input-bordered w-full" placeholder="00000-000">
                </div>
                <button type="button" id="btnBuscarCep" class="btn btn-primary btn-square">
                    <i class="fas fa-search"></i>
                </button>
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
                    <label class="block font-medium mb-1">Bairro</label>
                    <input type="text" id="bairro" class="input input-bordered w-full">
                </div>
                <div class="w-1/2">
                    <label class="block font-medium mb-1">Apelido</label>
                    <input type="text" id="apelido" class="input input-bordered w-full" placeholder="Casa, Trabalho…">
                </div>
            </div>
            <button type="button" id="btnSalvarEndereco"
                class="btn btn-success w-full">Salvar Endereço</button>
        </div>
    </div>

    <!-- Forma de Pagamento -->
    <div class="mb-6">
        <label class="font-semibold block mb-2">Forma de Pagamento</label>
        <select name="forma_pagamento" class="select select-bordered w-full">
            <?php foreach ($formasPgto as $pg): ?>
                <option value="<?= htmlspecialchars($pg['nome_pgto']) ?>">
                    <?= htmlspecialchars($pg['nome_pgto']) ?>
                    <?= $pg['is_online'] ? '(Online)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Resumo -->
    <div class="text-right font-bold text-lg mb-6 space-y-1">
        <div>Subtotal: R$<span id="valorSubtotal"><?= number_format($totalProdutos, 2, ',', '.') ?></span></div>
        <div>Frete: R$<span id="valorFreteVisual">0,00</span></div>
        <div>Distância: <span id="valorDistanciaVisual">0,00</span> km</div>
        <div class="mt-2 border-t pt-2">
            Total: R$<span id="valorTotal"><?= number_format($totalProdutos, 2, ',', '.') ?></span>
        </div>
    </div>

    <!-- Formulário finalização -->
    <form id="formFinalizarPedido">
        <input type="hidden" name="tipo_entrega" id="inputTipoEntrega" value="retirada">
        <input type="hidden" name="id_endereco_selecionado" id="idEnderecoSelecionado" value="">
        <input type="hidden" name="valor_frete" id="valorFreteCalculado" value="0">
        <input type="hidden" name="distancia_calculada" id="distanciaCalculada" value="">
        <button type="submit" id="btnConfirmarPedido" class="btn btn-primary w-full">Confirmar Pedido</button>
    </form>
</div>

<script>
    $(function() {
        const precoBase = <?= $precoBase ?>;
        const precoKm = <?= $precoKm ?>;
        const enderecoLoja = <?= json_encode($enderecoLoja) ?>;
        const googleApiKey = <?= json_encode($googleMapsKey) ?>;
        const regrasFrete = <?= json_encode($regrasFrete) ?>;
        const limiteEntrega = <?= $limiteEntrega !== null ? $limiteEntrega : 'null' ?>;
        const subtotal = <?= $totalProdutos ?>;

        function arredondar(valor) {
            const int = Math.floor(valor),
                dec = valor - int;
            if (dec <= 0.25) return int;
            if (dec <= 0.75) return int + 0.5;
            return int + 1;
        }

        function calcularFrete(destino) {
            const url =
                `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric` +
                `&origins=${encodeURIComponent(enderecoLoja)}` +
                `&destinations=${encodeURIComponent(destino)}` +
                `&key=${googleApiKey}`;

            // usa proxy para evitar bloqueio CORS
            $.getJSON('/proxys/proxy_google.php?url=' + encodeURIComponent(url), res => {
                if (res.status !== 'OK') return Swal.fire('Erro', 'Google Matrix falhou.', 'error');
                const el = res.rows[0].elements[0];
                if (el.status !== 'OK') return Swal.fire('Erro', 'Endereço não encontrado.', 'error');

                const km = el.distance.value / 1000;
                if (limiteEntrega !== null && km > limiteEntrega) {
                    return Swal.fire('Atenção', 'Fora da área de entrega.', 'warning');
                }

                let frete = precoBase + (precoKm * km);
                const hoje = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'][new Date().getDay()];

                regrasFrete.forEach(reg => {
                    if (subtotal >= parseFloat(reg.valor_minimo) &&
                        km <= parseFloat(reg.distancia_maxima) &&
                        hoje === reg.dia_semana.toLowerCase()) {

                        if (reg.tipo_regra === 'frete_gratis') {
                            frete = 0;
                        } else if (reg.tipo_regra === 'desconto_valor') {
                            frete -= parseFloat(reg.valor_desconto);
                        } else if (reg.tipo_regra === 'desconto_porcentagem') {
                            frete -= frete * (parseFloat(reg.valor_desconto) / 100);
                        }
                    }
                });

                frete = Math.max(0, arredondar(frete));

                $('#valorFreteCalculado').val(frete.toFixed(2));
                $('#valorFreteVisual').text(frete.toFixed(2).replace('.', ','));
                $('#valorDistanciaVisual').text(km.toFixed(2).replace('.', ','));
                $('#valorTotal').text((subtotal + frete).toFixed(2).replace('.', ','));
                $('#btnConfirmarPedido').prop('disabled', false);
            });
        }

        // Handler dos botões
        $('#btnEntrega').click(() => {
            $('#inputTipoEntrega').val('entrega');
            $('#btnEntrega').addClass('btn-primary').removeClass('btn-outline');
            $('#btnRetirada').addClass('btn-outline').removeClass('btn-primary');
            $('#enderecoEntrega').removeClass('hidden');
            $('#btnConfirmarPedido').prop('disabled', true);
        });
        $('#btnRetirada').click(() => {
            $('#inputTipoEntrega').val('retirada');
            $('#btnRetirada').addClass('btn-primary').removeClass('btn-outline');
            $('#btnEntrega').addClass('btn-outline').removeClass('btn-primary');
            $('#enderecoEntrega').addClass('hidden');
            $('#valorFreteVisual').text('0,00');
            $('#valorTotal').text(subtotal.toFixed(2).replace('.', ','));
            $('#btnConfirmarPedido').prop('disabled', false);
        });

        // Ao escolher endereço
        $('#selectEndereco').change(function() {
            const opt = $(this).find(':selected');
            const destino = `${opt.data('rua')} ${opt.data('numero')}, ${opt.data('bairro')}, ${opt.data('cep')}, Brasil`;
            $('#idEnderecoSelecionado').val(opt.val());
            calcularFrete(destino);
        });

        // CEP ➔ ViaCEP
        $('#btnBuscarCep').click(() => {
            const cep = $('#cep').val().replace(/\D/g, '');
            if (cep.length !== 8) return Swal.fire('Erro', 'CEP inválido.', 'error');
            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, data => {
                if (data.erro) return Swal.fire('Erro', 'CEP não encontrado.', 'error');
                $('#rua').val(data.logradouro);
                $('#bairro').val(data.bairro);
            });
        });

        // Toggle novo endereço
        $('.btnNovoEndereco').click(() => $('#formNovoEndereco').toggleClass('hidden'));

        // Salvar novo endereço
        $('#btnSalvarEndereco').click(() => {
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
            $.post('crud/crud_endereco.php', dados, res => {
                if (res.status === 'ok') Swal.fire('Sucesso', res.mensagem, 'success')
                    .then(() => location.reload());
                else Swal.fire('Erro', res.mensagem, 'error');
            }, 'json');
        });

        // Submeter pedido
        $('#formFinalizarPedido').submit(function(e) {
            e.preventDefault();
            const dados = $(this).serialize() + '&acao=confirmar';
            $.post('crud/crud_pedido.php', dados, res => {
                if (res.status === 'ok') {
                    localStorage.setItem('link_whatsapp', res.link_whatsapp);
                    window.location.href = 'meus_pedidos.php?novo_pedido=1';
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json').fail(() => Swal.fire('Erro', 'Falha ao confirmar pedido.', 'error'));
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>