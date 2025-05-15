(function ($) {
    $('#desconto').mask('#.##0,00', { reverse: true });
    const precoBase = window.__precoBase__ || 0;
    const precoKm = window.__precoKm__ || 0;
    const enderecoLoja = window.__enderecoLoja__ || '';
    const googleApiKey = window.__googleMapsKey__ || '';
    const limiteEntrega = window.__limiteEntrega__ || null;
    const regrasFrete = window.__regrasFrete__ || [];

    function arredondar(valor) {
        const int = Math.floor(valor);
        const dec = valor - int;
        if (dec <= 0.25) return int;
        if (dec <= 0.75) return int + 0.5;
        return int + 1;
    }

    function getSubtotal() {
        return window.__cartModule__.getItems()
            .reduce((acc, item) => acc + item.qty * item.price, 0);
    }

    function calcularFrete(destino, subtotal, desconto = 0) {
        const url = `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric` +
            `&origins=${encodeURIComponent(enderecoLoja)}` +
            `&destinations=${encodeURIComponent(destino)}` +
            `&key=${googleApiKey}`;

        $.getJSON('/proxys/proxy_google.php?url=' + encodeURIComponent(url), res => {
            const el = res.rows?.[0]?.elements?.[0];
            if (!el || el.status !== 'OK') {
                return Swal.fire('Erro', 'Endereço inválido ou fora da área.', 'error');
            }

            const km = el.distance.value / 1000;
            if (limiteEntrega && km > limiteEntrega) {
                return Swal.fire('Atenção', 'Fora da área de entrega.', 'warning');
            }

            let frete = precoBase + precoKm * km;

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

            $('#checkout-valor-frete').val(frete.toFixed(2));
            $('#checkout-distancia').val(km.toFixed(2));

            const totalFinal = subtotal + frete - desconto;
            $('#checkout-total').text(`R$ ${Math.max(totalFinal, 0).toFixed(2).replace('.', ',')}`);
        });
    }

    $('#btnRetiradaCheckout').click(() => {
        $('#checkout-tipo-entrega').val('retirada');
        $('#blocoEnderecoCheckout').addClass('hidden');
        $('#checkout-id-endereco').val('');
        $('#btnRetiradaCheckout').addClass('btn-primary').removeClass('btn-outline');
        $('#btnEntregaCheckout').addClass('btn-outline').removeClass('btn-primary');

        const subtotal = getSubtotal();
        const desconto = parseFloat($('#desconto').val().replace(/[^\d,]/g, '').replace(',', '.')) || 0;
        const total = Math.max(subtotal - desconto, 0);
        $('#checkout-total').text(`R$ ${total.toFixed(2).replace('.', ',')}`);
    });

    $('#btnEntregaCheckout').click(() => {
        $('#checkout-tipo-entrega').val('entrega');
        $('#blocoEnderecoCheckout').removeClass('hidden');
        $('#btnEntregaCheckout').addClass('btn-primary').removeClass('btn-outline');
        $('#btnRetiradaCheckout').addClass('btn-outline').removeClass('btn-primary');
    });

    $('#selectEnderecoCheckout').on('change', function () {
        const opt = $(this).find(':selected');
        if (!opt.val()) return;
        $('#checkout-id-endereco').val(opt.val());

        const destino = `${opt.data('rua')} ${opt.data('numero')}, ${opt.data('bairro')}, ${opt.data('cep')}, Brasil`;
        const subtotal = getSubtotal();
        const desconto = parseFloat($('#desconto').val().replace(/[^\d,]/g, '').replace(',', '.')) || 0;

        calcularFrete(destino, subtotal, desconto);
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

    $('#btnNovoEnderecoCheckout').click(() => {
        $('#formNovoEnderecoCheckout').toggleClass('hidden');
    });

    $('#btnSalvarEndereco').click(() => {
        const idUser = $('#checkout-id-cliente').val();
        const dados = {
            acao: 'cadastrar',
            cep: $('#cep').val(),
            rua: $('#rua').val(),
            numero: $('#numero').val(),
            bairro: $('#bairro').val(),
            apelido: $('#apelido').val(),
            endereco_principal: true,
            id_usuario: idUser
        };

        $.post('crud/crud_endereco.php', dados, res => {
            if (res.status === 'ok') {
                Swal.fire('Sucesso', res.mensagem, 'success').then(() => {
                    $('#formNovoEnderecoCheckout').addClass('hidden');
                    $('#formNovoEnderecoCheckout input').val('');

                    $.post('crud/crud_endereco.php', { acao: 'listar', id_usuario: idUser }, r => {
                        if (r.status === 'ok') {
                            const options = r.enderecos.map(end => `
                                <option value="${end.id_endereco}"
                                        data-rua="${end.rua}"
                                        data-numero="${end.numero}"
                                        data-bairro="${end.bairro}"
                                        data-cep="${end.cep}">
                                    ${end.rua}, ${end.numero} — ${end.bairro}
                                </option>
                            `).join('');
                            $('#selectEnderecoCheckout').html(`<option value="">Selecione um endereço</option>${options}`);
                        }
                    }, 'json');
                });
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }, 'json');
    });

    $('#desconto').on('input', () => {
        const opt = $('#selectEnderecoCheckout option:selected');
        let desconto = parseFloat($('#desconto').val().replace(/[^\d,]/g, '').replace(',', '.')) || 0;

        const subtotal = getSubtotal();
        if (desconto > subtotal) {
            desconto = 0;
            $('#desconto').val('0,00');
            Swal.fire('Atenção', 'Desconto maior que o valor total. Ele foi zerado.', 'warning');
        }

        if ($('#checkout-tipo-entrega').val() === 'retirada') {
            const total = subtotal - desconto;
            $('#checkout-total').text(`R$ ${Math.max(total, 0).toFixed(2).replace('.', ',')}`);
        } else if (opt.val()) {
            const destino = `${opt.data('rua')} ${opt.data('numero')}, ${opt.data('bairro')}, ${opt.data('cep')}, Brasil`;
            calcularFrete(destino, subtotal, desconto);
        }
    });

    $('#checkout-form').on('submit', function (e) {
        e.preventDefault();

        const desconto = parseFloat($('#desconto').val().replace(/[^\d,]/g, '').replace(',', '.')) || 0;

        const payload = {
            action: 'criar_pedido_balcao',
            nome_cliente: $('#checkout-name').val(),
            telefone_cliente: $('#checkout-phone').val(),
            id_usuario: $('#checkout-id-cliente').val(),
            tipo_entrega: $('#checkout-tipo-entrega').val(),
            id_endereco: $('#checkout-id-endereco').val() || null,
            endereco: $('#selectEnderecoCheckout').val(),
            valor_frete: $('#checkout-valor-frete').val() || '0',
            distancia: $('#checkout-distancia').val() || '0',
            desconto: desconto.toFixed(2),
            forma_pagamento: $('#checkout-payment').val(),
            items: window.__cartModule__.getItems()
        };

        $.post('crud/crud_atendimento.php', JSON.stringify(payload), res => {
            if (res.status === 'ok') {
                Swal.fire({
                    title: 'Pedido criado!',
                    text: 'Deseja imprimir a via do pedido?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Imprimir',
                    cancelButtonText: 'Fechar'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.open('comprovante_pedido.php?id=' + res.id_pedido, '_blank');
                    }
                });

                $('#modal-checkout,#modal-new-order').prop('checked', false);
                window.__cartModule__.clear();
                window.fetchAndRenderPedidos?.();
            } else {
                Swal.fire('Erro', res.mensagem || 'Erro ao criar pedido.', 'error');
            }
        }, 'json');
    });

})(jQuery);
