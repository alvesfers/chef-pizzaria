// produtoDetail.js
(function ($) {
    const saboresByProduto = window.__flavorsAssoc__;
    const addonsByProduto = window.__addonsAssoc__;
    const tiposByProduto = window.__tiposAdicionais__;  // <— agora definido

    $(document).on('produto:abrirDetalhe', function (_, prod) {
        const id = prod.id_produto;
        $('#detail-prod-name').text(prod.nome).data('id', id);

        // --- Sabores ---
        if (prod.qtd_sabores > 1 && saboresByProduto[id]) {
            $('#detail-prod-flavors').removeClass('hidden');
            $('#max-flavors').text(prod.qtd_sabores);
            let html = '';
            saboresByProduto[id].forEach(f => {
                html += `
            <label class="flex items-center gap-2">
              <input type="checkbox" 
                     name="flavor" 
                     class="checkbox-sabor" 
                     value="${f.id_produto}" 
                     data-preco="${f.preco}" />
              <span>${f.nome} — R$${parseFloat(f.preco).toFixed(2)}</span>
            </label>`;
            });
            $('#flavor-options').html(html);
            $('#addon-options')
                .removeClass('grid-cols-4 md:grid-cols-4')
                .addClass('grid-cols-2');
        } else {
            $('#detail-prod-flavors').addClass('hidden');
            $('#addon-options')
                .removeClass('grid-cols-2')
                .addClass('grid-cols-2 md:grid-cols-3');
        }

        // --- Adicionais (respeitando tipos e inclusos, pagos, etc) ---
        let addonsHtml = '';
        // encontra todos os grupos de adicionais para este produto
        tiposByProduto
            .filter(g => g.tipo.id_produto == id)
            .forEach(grupo => {
                addonsHtml += `<div class="mb-2">
            <div class="font-semibold">${grupo.tipo.nome_tipo_adicional}`;
                if (grupo.tipo.max_inclusos > 0) {
                    addonsHtml += ` <small>(até ${grupo.tipo.max_inclusos} inclusos)</small>`;
                }
                addonsHtml += `</div>
            <div class="grid grid-cols-2 md:grid-cols-1 gap-2">`;
                grupo.adicionais.forEach(a => {
                    const incluso = addonsByProduto[id]
                        .some(x => x.id_adicional == a.id_adicional) && !grupo.tipo.max_inclusos;
                    addonsHtml += `
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox"
                       name="addon"
                       value="${a.id_adicional}"
                       data-preco="${a.preco}"
                       ${incluso ? 'checked disabled' : ''} />
                <span>${a.nome} — R$${parseFloat(a.preco).toFixed(2)}</span>
              </label>`;
                });
                addonsHtml += `</div></div>`;
            });
        $('#addon-options').html(addonsHtml);

        $('#detail-prod-qty').val(1);
        $('#modal-product-detail').prop('checked', true);
    });

    // limita sabores selecionados
    $(document).on('change', '.checkbox-sabor', function () {
        const max = parseInt($('#max-flavors').text(), 10);
        if ($('.checkbox-sabor:checked').length > max) {
            this.checked = false;
            Swal.fire('Atenção', `Só é possível escolher até ${max} sabor(es).`, 'warning');
        }
    });

    // adiciona ao carrinho
    $('#add-to-cart-btn').click(function () {
        const id = $('#detail-prod-name').data('id');
        const prod = window.__produtos__.find(p => p.id_produto == id);
        const qty = +$('#detail-prod-qty').val();
        let price = parseFloat(prod.valor_produto) || 0;

        // sabor
        const $flav = $('input[name="flavor"]:checked');
        const flavorId = $flav.val() ? Number($flav.val()) : null;
        if (flavorId) {
            price = parseFloat($flav.data('preco')) || price;
        }

        // adicionais
        const addonsIds = [];
        $('input[name="addon"]:checked').each(function () {
            const val = parseFloat($(this).data('preco')) || 0;
            price += val;
            addonsIds.push(Number($(this).val()));
        });

        window.__cartModule__.addItem({ prod, qty, flavorId, addonsIds, price });
        $('#modal-product-detail').prop('checked', false);
    });
})(jQuery);
