(function ($) {
    let deskUserId = null;

    $('#new-order-btn').off('click').on('click', function () {
        $('#modal-new-order').prop('checked', true);
        $('#desk-confirm-btn').prop('disabled', true);
        $('#telefone, #desk-name').val('');
        $('#filter-category, #filter-subcategory, #filter-search').val('');

        // carregar produtos via AJAX
        $.post('crud/crud_atendimento.php', JSON.stringify({ action: 'carregar_produtos' }), res => {
            if (res.status !== 'ok' || !Array.isArray(res.produtos)) {
                return Swal.fire('Erro', 'Falha ao carregar produtos', 'error');
            }

            // Popula variáveis globais
            window.__categorias__ = res.categorias;
            window.__subcategorias__ = res.subcategorias;
            window.__produtos__ = res.produtos;

            // Preenche select de categorias
            const catHtml = res.categorias.map(c => `<option value="${c.id_categoria}">${c.nome_categoria}</option>`).join('');
            $('#filter-category').html(`<option value="">Todas Categorias</option>${catHtml}`);

            // Subcategorias completas (opcional - refeito ao mudar categoria)
            const subHtml = res.subcategorias.map(s => `
                <option value="${s.id_subcategoria}" data-cat="${s.id_categoria}">
                    ${s.nome_subcategoria}
                </option>`).join('');
            $('#filter-subcategory').html(`<option value="">Todas Subcategorias</option>${subHtml}`);

            // Bind categoria
            $('#filter-category').off('change').on('change', function () {
                const catId = $(this).val();
                const filtradas = window.__subcategorias__.filter(s => s.id_categoria == catId);
                const subOptions = filtradas.map(s => `
                    <option value="${s.id_subcategoria}" data-cat="${s.id_categoria}">
                        ${s.nome_subcategoria}
                    </option>`).join('');
                $('#filter-subcategory').html(`<option value="">Todas Subcategorias</option>${subOptions}`);

                if (window.__cartModule__) window.__cartModule__.filterProducts();
            });

            // Subcategoria
            $('#filter-subcategory').off('change').on('change', function () {
                if (window.__cartModule__) window.__cartModule__.filterProducts();
            });

            // Busca por texto (enter)
            $('#filter-search').off('keydown').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (window.__cartModule__) window.__cartModule__.filterProducts();
                }
            });

            if (window.__cartModule__) window.__cartModule__.filterProducts();
        }, 'json');
    });

    $('#telefone-search').off('click').on('click', function () {
        const phone = $('#telefone').val().replace(/\D/g, '');
        if (phone.length < 10 || phone.length > 11) {
            return Swal.fire('Erro', 'Telefone inválido', 'error');
        }

        $.ajax({
            url: 'crud/teste.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'get_by_phone', phone }),
            success(res) {
                if (res.exists) {
                    deskUserId = res.id;
                    $('#desk-name').val(res.name).prop('disabled', true);
                } else {
                    deskUserId = null;
                    $('#desk-name').val('').prop('disabled', false).focus();
                }
                $('#desk-confirm-btn').prop('disabled', false);
            },
            error() {
                Swal.fire('Erro', 'Erro na conexão com o servidor', 'error');
            }
        });
    });

    $('#desk-confirm-btn').off('click').on('click', function () {
        const phone = $('#telefone').val().replace(/\D/g, '');
        const name = $('#desk-name').val().trim();

        if (!phone || phone.length < 10) {
            return Swal.fire('Erro', 'Telefone inválido', 'error');
        }

        function abrirCheckout() {
            const phone = $('#telefone').val();
            const name = $('#desk-name').val().trim();

            $('#checkout-phone').val(phone);
            $('#checkout-name').val(name);
            $('#checkout-id-cliente').val(deskUserId);

            // Subtotal do carrinho
            const subtotal = window.__cartModule__.getItems()
                .reduce((acc, item) => acc + item.qty * item.price, 0);

            $('#checkout-total').text(`R$ ${subtotal.toFixed(2).replace('.', ',')}`);

            // Limpa lista de endereços e oculta formulário novo
            $('#selectEnderecoCheckout').html('<option>Carregando...</option>');
            $('#formNovoEnderecoCheckout').addClass('hidden');

            // Busca endereços do cliente
            $.post('crud/crud_endereco.php', {
                acao: 'listar',
                id_usuario: deskUserId
            }, function (res) {
                if (res.status === 'ok' && Array.isArray(res.enderecos)) {
                    if (res.enderecos.length) {
                        const options = res.enderecos.map(e => `
                    <option value="${e.id_endereco}"
                            data-rua="${e.rua}"
                            data-numero="${e.numero}"
                            data-bairro="${e.bairro}"
                            data-cep="${e.cep}">
                        ${e.rua}, ${e.numero} — ${e.bairro}
                    </option>
                `).join('');
                        $('#selectEnderecoCheckout').html('<option value="">Selecione um endereço</option>' + options);
                    } else {
                        $('#selectEnderecoCheckout').html('<option value="">Nenhum endereço encontrado</option>');
                    }
                } else {
                    $('#selectEnderecoCheckout').html('<option value="">Erro ao carregar endereços</option>');
                }
            }, 'json');

            $('#modal-checkout').prop('checked', true);
        }
        
        if (!deskUserId) {
            if (!name) return Swal.fire('Erro', 'Informe o nome do cliente', 'warning');
            $.post('crud/teste.php', JSON.stringify({ action: 'create_user', phone, name }), res => {
                if (res.status === 'ok') {
                    deskUserId = res.id;
                    abrirCheckout();
                } else {
                    Swal.fire('Erro', 'Erro ao criar usuário', 'error');
                }
            }, 'json');
        } else {
            abrirCheckout();
        }
    });
})(jQuery);
