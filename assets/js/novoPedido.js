// novoPedido.js
(function ($) {
    let deskUserId = null;
    $('#new-order-btn').click(() => {
        $('#modal-new-order').prop('checked', true);
        $('#desk-confirm-btn').prop('disabled', true);
        $('#telefone, #desk-name').val('');
    });

    // substituir seu handler de "#telefone-search" por este:
    $('#telefone-search').on('click', function () {
        const phone = $('#telefone').val().replace(/\D/g, '');
        if (phone.length < 10) {
            return Swal.fire('Erro', 'Telefone inválido', 'error');
        }
        $.ajax({
            url: 'crud/teste.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'get_by_phone', phone }),
            success(res) {
                // se existir, preenche e desabilita o input; senão, limpa e habilita
                if (res.exists) {
                    deskUserId = res.id;
                    $('#desk-name').val(res.name).prop('disabled', true);
                } else {
                    deskUserId = null;
                    $('#desk-name').val('').prop('disabled', false).focus();
                }
                // libera o botão de finalizar
                $('#desk-confirm-btn').prop('disabled', false);
            },
            error() {
                Swal.fire('Erro', 'Falha ao buscar cliente', 'error');
            }
        });
    });


    $('#desk-confirm-btn').click(() => {
        const phone = $('#telefone').val().replace(/\D/g, ''), name = $('#desk-name').val().trim();
        function abrirCheckout() {
            $('#modal-new-order').prop('checked', false);
            $('#checkout-phone').val($('#telefone').val());
            $('#checkout-name').val(name || '');
            $('#modal-checkout').prop('checked', true);
        }
        if (!deskUserId) {
            if (!name) return alert('Informe o nome');
            $.post('crud/teste.php', JSON.stringify({ action: 'create_user', phone, name }), res => {
                if (res.status === 'ok') { deskUserId = res.id; abrirCheckout(); }
                else alert('Erro ao criar usuário');
            }, 'json');
        } else abrirCheckout();
    });
})(jQuery);
