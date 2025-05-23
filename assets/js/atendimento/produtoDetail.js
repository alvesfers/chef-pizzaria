(function ($) {
    let dadosProduto = null;
    let subcategoriasSabores = [];

    function calcularTotal() {
        const prod = window.__produtoSelecionado;
        if (!prod) return;

        const qtd = parseInt($('#detail-prod-qty').val(), 10) || 1;
        let precoBase = parseFloat(prod.valor_produto) || 0;
        let precoFinal = precoBase;

        if (prod.qtd_sabores > 1) {
            const selecionados = $('input[name="flavor"]:checked')
                .toArray()
                .map(el => parseFloat(el.dataset.preco || 0));

            if (selecionados.length === prod.qtd_sabores) {
                precoFinal = prod.tipo_calculo_preco === 'media'
                    ? selecionados.reduce((a, b) => a + b, 0) / selecionados.length
                    : Math.max(...selecionados);
            }
        }

        let extras = 0;
        const tiposMap = {};
        $('input[name="addon"]:checked').each(function () {
            const tipoId = $(this).data('tipo');
            const max = parseInt($(this).data('max') || 0);
            const incluso = $(this).data('incluso') == 1;
            const valor = parseFloat($(this).data('preco')) || 0;

            tiposMap[tipoId] = tiposMap[tipoId] || [];
            tiposMap[tipoId].push({ incluso, valor, max });
        });

        Object.values(tiposMap).forEach(lista => {
            const maxInc = lista[0]?.max || 0;
            const extrasSomados = lista
                .sort((a, b) => (b.incluso ? 1 : 0) - (a.incluso ? 1 : 0))
                .slice(maxInc)
                .reduce((sum, item) => sum + item.valor, 0);
            extras += extrasSomados;
        });

        const total = (precoFinal + extras) * qtd;
        $('#valor-total-modal').text(`R$ ${total.toFixed(2)}`);
    }

    function renderSabores(sabores, filtro = '') {
        const max = parseInt($('#max-flavors').text(), 10);

        const html = sabores
            .filter(f => filtro === '' || f.nome_subcategoria === filtro)
            .map(f => `
                <label class="flex items-center gap-2 sabor-item" data-subcat="${f.nome_subcategoria}">
                    <input type="checkbox" name="flavor" class="checkbox-sabor" value="${f.id_produto}" data-preco="${f.valor_produto}" />
                    <span>${f.nome_produto} — R$ ${parseFloat(f.valor_produto).toFixed(2)}</span>
                </label>
            `).join('');

        $('#flavor-options').html(html);
    }

    function renderFiltroSubcat(subcats) {
        if (!subcats.length) {
            $('#subcat-filter-wrapper').addClass('hidden');
            return;
        }

        const btns = [`<button type="button" class="btn btn-xs btn-outline active" data-sub="">Todos</button>`]
            .concat(subcats.map(s => `
                <button type="button" class="btn btn-xs btn-outline" data-sub="${s}">${s}</button>
            `)).join('');

        $('#flavor-subcat-buttons').html(btns);
        $('#subcat-filter-wrapper').removeClass('hidden');
    }

    $(document).on('produto:abrirDetalhe', function (_, res) {
        dadosProduto = res;
        const p = res.produto;
        window.__produtoSelecionado = p;

        $('#detail-prod-name').text(p.nome_produto).data('id', p.id_produto);
        $('#detail-prod-qty').val(1);

        if (p.qtd_sabores > 1) {
            $('#add-to-cart-btn').prop('disabled', true);
            $('#detail-prod-flavors').removeClass('hidden');
            $('#max-flavors').text(p.qtd_sabores);

            const sabores = res.sabores;
            subcategoriasSabores = [...new Set(sabores.map(s => s.nome_subcategoria))];
            renderSabores(sabores);
            renderFiltroSubcat(subcategoriasSabores);
        } else {
            $('#add-to-cart-btn').prop('disabled', false);
            $('#detail-prod-flavors').addClass('hidden');
            $('#flavor-options').empty();
            $('#subcat-filter-wrapper').addClass('hidden');
        }

        const addonsHtml = res.adicionais.map(grupo => {
            const tipo = grupo.tipo;
            const label = `${tipo.nome_tipo_adicional} ${tipo.max_inclusos > 0 ? `(até ${tipo.max_inclusos} inclusos)` : ''}`;
            const inputs = grupo.adicionais.map(add => {
                const incluso = res.inclusos.includes(add.id_adicional);
                return `
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox"
                            name="addon"
                            value="${add.id_adicional}"
                            data-tipo="${tipo.id_tipo_adicional}"
                            data-max="${tipo.max_inclusos || 0}"
                            data-incluso="${incluso ? 1 : 0}"
                            data-preco="${add.valor_adicional}"
                            ${incluso && tipo.max_inclusos === 0 ? 'checked disabled' : ''} />
                        <span>${add.nome_adicional} — R$${parseFloat(add.valor_adicional).toFixed(2)}</span>
                    </label>
                `;
            }).join('');
            return `<div class="mb-2"><div class="font-semibold">${label}</div><div class="grid grid-cols-2 gap-2 mt-1">${inputs}</div></div>`;
        }).join('');
        $('#addon-options').html(addonsHtml);

        $('#modal-product-detail').prop('checked', true);
        calcularTotal();
    });

    // Filtro por subcategoria
    $(document).on('click', '#flavor-subcat-buttons button', function () {
        $('#flavor-subcat-buttons button').removeClass('active');
        $(this).addClass('active');

        const filtro = $(this).data('sub');
        renderSabores(dadosProduto.sabores, filtro);
        $('.checkbox-sabor').trigger('change');
    });

    // Eventos principais
    $(document)
        .on('change', 'input[name="addon"], #detail-prod-qty', calcularTotal)
        .on('click', '#add-to-cart-btn', () => {
            if (!dadosProduto) return;

            const qtd = parseInt($('#detail-prod-qty').val(), 10) || 1;
            const sabores = $('input[name="flavor"]:checked')
                .map((_, el) => ({ id: parseInt(el.value, 10) }))
                .get();


            const addons = $('input[name="addon"]:checked')
                .map((_, el) => {
                    const id = parseInt(el.value, 10);
                    const nome = $(el).next().text();
                    const valor = parseFloat(el.dataset.preco) || 0;
                    return { id, nome, valor };
                }).get();

            const totalText = $('#valor-total-modal').text().replace(/[^\d,]/g, '').replace(',', '.') / 100;
            const price = parseFloat(totalText);

            window.__cartModule__.addItem({
                prod: dadosProduto.produto,
                qty: qtd,
                flavors: sabores,
                addons,
                price
            });

            $('#modal-product-detail').prop('checked', false);
        });

    // Sabores com limite
    $(document).on('change', '.checkbox-sabor', function () {
        const max = parseInt($('#max-flavors').text(), 10);
        const selecionados = $('.checkbox-sabor:checked').length;

        if (selecionados == max || selecionados >= max) {
            $('#add-to-cart-btn').prop('disabled', false);
            $('.checkbox-sabor:not(:checked)').prop('disabled', true);
        } else {
            $('.checkbox-sabor').prop('disabled', false);
            $('#add-to-cart-btn').prop('disabled', true);
        }
        calcularTotal();
    });
})(jQuery);
