// checkout.js
(function ($) {
    $('#open-checkout-btn').click(() => {
        $('#modal-checkout').prop('checked', true);
    });

    $('#checkout-form').on('submit', function (e) {
        e.preventDefault();

        const payload = {
            action: 'criar_pedido_balcao',
            nome_cliente: $('#checkout-name').val(),
            id_cliente: $('#checkoout-id-cliente').val(),
            telefone_cliente: $('#checkout-phone').val(),
            tipo_entrega: $('#checkout-delivery-type').val(),
            forma_pagamento: $('#checkout-payment').val(),
            items: window.__cartModule__.clear() || [] // limpa o carrinho ao enviar
        };

        $.ajax({
            url: 'crud/crud_pedido.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success(r) {
                if (r.status === 'ok') {
                    Swal.fire('Sucesso', 'Pedido criado com sucesso!', 'success');
                    $('#modal-checkout,#modal-new-order').prop('checked', false);
                    window.__cartModule__.clear();
                    window.fetchAndRenderPedidos();
                } else {
                    Swal.fire('Erro', r.mensagem, 'error');
                }
            },
            error() {
                Swal.fire('Erro', 'Falha de rede ao criar pedido.', 'error');
            }
        });
    });

})(jQuery);
