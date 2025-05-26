<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'assets/header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$carrinho   = $_SESSION['carrinho'] ?? [];
$usuario    = $_SESSION['usuario'];
$idUsuario  = $usuario['id'];

if (empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

$dadosLoja     = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$precoBase     = floatval($dadosLoja['preco_base']);
$precoKm       = floatval($dadosLoja['preco_km']);
$enderecoLoja  = trim($dadosLoja['endereco_completo']);
$googleMapsKey = $dadosLoja['google'];
$limiteEntrega = isset($dadosLoja['limite_entrega']) ? floatval($dadosLoja['limite_entrega']) : null;
$tempoEntrega     = isset($dadosLoja['tempo_entrega'])   ? intval($dadosLoja['tempo_entrega'])     : null;
$tempoRetirada    = isset($dadosLoja['tempo_retirada'])  ? intval($dadosLoja['tempo_retirada']) : null;
$regrasFrete   = $pdo->query("SELECT * FROM tb_regras_frete WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

$stmt       = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$enderecos  = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formasPgto = $pdo->query("SELECT * FROM tb_forma_pgto WHERE pagamento_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($carrinho as $item) {
    $subtotal += $item['valor_unitario'] * $item['quantidade'];
}
?>
<div class="container mx-auto px-4 py-10 max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Finalizar Pedido</h1>
        <a href="carrinho.php" class="btn btn-sm btn-outline">Voltar ao Carrinho</a>
    </div>

    <form id="formFinalizarPedido">
        <div class="mb-6">
            <label class="font-semibold block mb-2">Tipo de entrega</label>
            <div class="flex gap-2">
                <button type="button" id="btnRetirada" class="btn btn-primary w-1/2">Retirada na Loja</button>
                <button type="button" id="btnEntrega" class="btn btn-outline w-1/2">Entrega</button>
            </div>
            <div class="m-2 text-center">
                <p id="textoPrazo" class="text-sm text-gray-700">
                    <!-- Será preenchido pelo JS -->
                </p>
            </div>
            <input type="hidden" name="tipo_entrega" id="inputTipoEntrega" value="retirada">
        </div>

        <div id="enderecoEntrega" class="mb-6 hidden">
            <?php if ($enderecos): ?>
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="font-semibold block mb-2">Endereço de Entrega</label>
                        <select id="selectEndereco" class="select select-bordered w-full">
                            <option value="0">Selecione seu endereço</option>
                            <?php foreach ($enderecos as $end): ?>
                                <option value="<?= $end['id_endereco'] ?>"
                                    data-rua="<?= htmlspecialchars($end['rua']) ?>"
                                    data-numero="<?= htmlspecialchars($end['numero']) ?>"
                                    data-bairro="<?= htmlspecialchars($end['bairro']) ?>"
                                    data-cep="<?= preg_replace('/\D/', '', $end['cep']) ?>">
                                    <?= htmlspecialchars($end['rua']) ?>, <?= $end['numero'] ?> — <?= htmlspecialchars($end['bairro']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btnNovoEndereco btn btn-success btn-square mt-auto" title="Cadastrar novo endereço">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            <?php else: ?>
                <p class="text-sm text-red-500 mb-2">Nenhum endereço cadastrado.</p>
                <button type="button" class="btnNovoEndereco btn btn-sm btn-outline w-full">Cadastrar endereço</button>
            <?php endif; ?>
            <input type="hidden" name="id_endereco_selecionado" id="idEnderecoSelecionado" value="">

            <div id="formNovoEndereco" class="hidden mt-6 space-y-4">
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block font-medium mb-1">CEP</label>
                        <input type="text" id="cep" class="input input-bordered w-full" placeholder="00000-000">
                    </div>
                    <button type="button" id="btnBuscarCep" class="btn btn-primary btn-square mt-auto">
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
                <button type="button" id="btnSalvarEndereco" class="btn btn-success w-full">Salvar Endereço</button>
            </div>
        </div>

        <div class="mb-6">
            <label class="font-semibold block mb-2">Forma de Pagamento</label>
            <select id="selectForma" class="select select-bordered w-full" required>
                <?php foreach ($formasPgto as $pg): ?>
                    <option value="<?= htmlspecialchars($pg['nome_pgto']) ?>">
                        <?= htmlspecialchars($pg['nome_pgto']) ?> <?= $pg['is_online'] ? '(Online)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="forma_pagamento" id="inputFormaPagamento" value="">
        </div>

        <div class="mb-6">
            <label class="font-semibold block mb-2">Cupom de Desconto</label>
            <div class="flex gap-2">
                <input type="text" id="inputCupom" class="input input-bordered flex-1" placeholder="Digite seu cupom">
                <button type="button" id="btnAplicarCupom" class="btn btn-secondary">Aplicar</button>
            </div>
            <p id="cupomMensagem" class="text-sm mt-1"></p>
            <input type="hidden" name="codigo_cupom" id="inputCodigoCupom" value="">
        </div>

        <div class="text-right font-bold text-lg mb-6 space-y-1">
            <div>Subtotal: R$<span id="valorSubtotal"><?= number_format($subtotal, 2, ',', '.') ?></span></div>
            <div class="class-frete hidden">Frete: R$<span id="valorFreteVisual">0,00</span></div>
            <div class="class-frete hidden">Distância: <span id="valorDistanciaVisual">0,00</span> km</div>
            <div>Desconto: R$<span id="valorDesconto">0,00</span></div>
            <div class="mt-2 border-t pt-2">
                Total: R$<span id="valorTotal"><?= number_format($subtotal, 2, ',', '.') ?></span>
            </div>
            <input type="hidden" name="valor_frete" id="valorFreteCalculado" value="0">
            <input type="hidden" name="distancia_calculada" id="distanciaCalculada" value="">
        </div>

        <button type="submit" id="btnConfirmarPedido" class="btn btn-primary w-full">Confirmar Pedido</button>
    </form>
</div>

<script>
    $(function() {
        const precoBase = <?= json_encode($precoBase) ?>;
        const precoKm = <?= json_encode($precoKm) ?>;
        const enderecoLoja = <?= json_encode($enderecoLoja) ?>;
        const googleApiKey = <?= json_encode($googleMapsKey) ?>;
        const regrasFrete = <?= json_encode($regrasFrete) ?>;
        const limiteEntrega = <?= $limiteEntrega ?? 'null' ?>;
        const tempoEntrega = <?= $tempoEntrega   ?? 'null' ?>;
        const tempoRetirada = <?= $tempoRetirada  ?? 'null' ?>;

        let subtotal = <?= $subtotal ?>;
        let descontoAtual = 0;

        function atualizarPrazo(tipo) {
            if (tipo === 'entrega' && tempoEntrega !== null) {
                $('#textoPrazo').text(`Prazo de entrega: ${tempoEntrega} minutos`);
            } else if (tipo === 'retirada' && tempoRetirada !== null) {
                $('#textoPrazo').text(`Prazo de retirada: ${tempoRetirada} minutos`);
            } else {
                $('#textoPrazo').text('');
            }
        }

        atualizarPrazo('retirada');

        function calcularFrete(destino) {
            const url = `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric` +
                `&origins=${encodeURIComponent(enderecoLoja)}` +
                `&destinations=${encodeURIComponent(destino)}` +
                `&key=${googleApiKey}`;

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
                        if (reg.tipo_regra === 'frete_gratis') frete = 0;
                        else if (reg.tipo_regra === 'desconto_valor') frete -= parseFloat(reg.valor_desconto);
                        else if (reg.tipo_regra === 'desconto_porcentagem') frete -= frete * (parseFloat(reg.valor_desconto) / 100);
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

        function arredondar(v) {
            const i = Math.floor(v),
                d = v - i;
            if (d <= 0.25) return i;
            if (d <= 0.75) return i + 0.5;
            return i + 1;
        }

        function atualizarTotal() {
            const frete = parseFloat($('#valorFreteCalculado').val()) || 0;
            const total = subtotal + frete - descontoAtual;
            $('#valorTotal').text(total.toFixed(2).replace('.', ','));
        }

        $('#btnEntrega').click(() => {
            $('#inputTipoEntrega').val('entrega');
            $('#btnEntrega').addClass('btn-primary').removeClass('btn-outline');
            $('#btnRetirada').addClass('btn-outline').removeClass('btn-primary');
            $('#enderecoEntrega').removeClass('hidden');
            $('#btnConfirmarPedido').prop('disabled', true);
            $('.class-frete').removeClass('hidden');

            atualizarPrazo('entrega');
        });

        $('#btnRetirada').click(() => {
            $('#inputTipoEntrega').val('retirada');
            $('#btnRetirada').addClass('btn-primary').removeClass('btn-outline');
            $('#btnEntrega').addClass('btn-outline').removeClass('btn-primary');
            $('#enderecoEntrega').addClass('hidden');
            $('.class-frete').addClass('hidden');
            $('#valorFreteVisual,#valorDistanciaVisual').text('0,00');
            $('#valorTotal').text(subtotal.toFixed(2).replace('.', ','));
            $('#selectEndereco').val(0);
            $('#btnConfirmarPedido').prop('disabled', false);

            atualizarPrazo('retirada');
        });

        $('#selectEndereco').change(function() {
            const opt = $(this).find(':selected');
            if (opt.val() == 0) return $('#btnConfirmarPedido').prop('disabled', true);
            const destino = `${opt.data('rua')} ${opt.data('numero')}, ${opt.data('bairro')}, ${opt.data('cep')}, Brasil`;
            $('#idEnderecoSelecionado').val(opt.val());
            const url = `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric` +
                `&origins=${encodeURIComponent(enderecoLoja)}` +
                `&destinations=${encodeURIComponent(destino)}` +
                `&key=${googleApiKey}`;
            $.getJSON('/proxys/proxy_google.php?url=' + encodeURIComponent(url), res => {
                if (res.status !== 'OK') return Swal.fire('Erro', 'Google Matrix falhou.', 'error');
                const el = res.rows[0].elements[0];
                if (el.status !== 'OK') return Swal.fire('Erro', 'Endereço não encontrado.', 'error');
                const km = el.distance.value / 1000;
                if (limiteEntrega !== null && km > limiteEntrega) return Swal.fire('Atenção', 'Fora da área de entrega.', 'warning');
                let frete = precoBase + precoKm * km;
                const hoje = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'][new Date().getDay()];
                regrasFrete.forEach(reg => {
                    if (subtotal >= parseFloat(reg.valor_minimo) &&
                        km <= parseFloat(reg.distancia_maxima) &&
                        hoje === reg.dia_semana.toLowerCase()) {
                        if (reg.tipo_regra === 'frete_gratis') frete = 0;
                        else if (reg.tipo_regra === 'desconto_valor') frete -= parseFloat(reg.valor_desconto);
                        else frete -= frete * (parseFloat(reg.valor_desconto) / 100);
                    }
                });
                frete = Math.max(0, arredondar(frete));
                $('#valorFreteCalculado').val(frete.toFixed(2));
                $('#valorFreteVisual').text(frete.toFixed(2).replace('.', ','));
                $('#valorDistanciaVisual').text(km.toFixed(2).replace('.', ','));
                $('#btnConfirmarPedido').prop('disabled', false);
                atualizarTotal();
            });
        });

        $('.btnNovoEndereco').click(function() {
            $('#formNovoEndereco').toggleClass('hidden');
            const aberto = !$('#formNovoEndereco').hasClass('hidden');

            // Se for botão com ícone
            if ($(this).hasClass('btn-square')) {
                $(this).find('i').removeClass().addClass(aberto ? 'fa-regular fa-circle-up' : 'fas fa-plus');
            } else {
                // Se for botão com texto
                $(this).text(aberto ? 'Fechar' : 'Cadastrar endereço');
            }
        });

        $('#selectForma').change(function() {
            $('#inputFormaPagamento').val($(this).val());
        }).trigger('change');

        $('#btnAplicarCupom').click(() => {
            const codigo = $('#inputCupom').val().trim();
            if (!codigo) {
                $('#cupomMensagem').text('Digite um cupom.').addClass('text-red-500');
                return;
            }
            $.post('crud/crud_cupom.php', {
                action: 'validar',
                codigo,
                subtotal
            }, res => {
                if (res.status === 'ok') {
                    descontoAtual = parseFloat(res.desconto);
                    $('#valorDesconto').text(descontoAtual.toFixed(2).replace('.', ','));
                    $('#inputCodigoCupom').val(codigo);
                    $('#cupomMensagem').text('Cupom aplicado!').removeClass('text-red-500').addClass('text-green-500');
                } else {
                    descontoAtual = 0;
                    $('#valorDesconto').text('0,00');
                    $('#inputCodigoCupom').val('');
                    $('#cupomMensagem').text(res.mensagem).addClass('text-red-500');
                }
                atualizarTotal();
            }, 'json').fail(() => {
                $('#cupomMensagem').text('Erro ao validar cupom.').addClass('text-red-500');
            });
        });

        $('#btnBuscarCep').click(() => {
            const cep = $('#cep').val().replace(/\D/g, '');
            if (cep.length !== 8) return Swal.fire('Erro', 'CEP inválido.', 'error');
            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, data => {
                if (data.erro) return Swal.fire('Erro', 'CEP não encontrado.', 'error');
                $('#rua').val(data.logradouro);
                $('#bairro').val(data.bairro);
            });
        });

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
                if (res.status === 'ok') {
                    Swal.fire('Sucesso', res.mensagem, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>