(function ($) {
    let cart = [];

    function getProdutos() {
        return window.__produtos__ || [];
    }

    function filterProducts() {
        const produtos = getProdutos();
        const cat = $('#filter-category').val();
        const sub = $('#filter-subcategory').val();
        const txt = $('#filter-search').val().toLowerCase();

        const lista = produtos.filter(p => {
            const okCat = !cat || p.id_categoria == cat;
            const okSub = !sub || (Array.isArray(p.subcategorias) && p.subcategorias.includes(parseInt(sub)));
            const nome = (p.nome || p.nome_produto || '').toLowerCase();
            const okTxt = !txt || nome.includes(txt);
            return okCat && okSub && okTxt;
        });

        const html = lista.map(p => `
        <div class="card product-card border border-gray-200 hover:shadow-lg transition-all cursor-pointer p-4 rounded" data-id="${p.id_produto}">
            <div class="card-body">
                <h4>${p.nome || p.nome_produto}</h4>
                <p>R$ ${parseFloat(p.valor_produto).toFixed(2)}</p>
            </div>
        </div>
    `).join('');

        $('#product-list').html(html);
    }


    let debounceTimeout;
    $('#filter-search').on('input', function () {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(filterProducts, 200);
    });

    function renderCart() {
        let total = 0;
        const html = cart.map((item, idx) => {
            total += item.qty * item.price;

            const saborHtml = item.flavors?.length
                ? `<ul class="ml-4 list-disc list-inside text-sm">Sabores
                   ${item.flavors.map(s => `<li>${s}</li>`).join('')}
               </ul>` : '';

            const addsHtml = item.addons?.length
                ? `<ul class="ml-4 list-disc list-inside text-sm">Adicionais
                   ${item.addons.map(a => `<li>${a.nome}</li>`).join('')}
               </ul>` : '';

            return `
            <div class="relative border p-2 mb-2 rounded" data-index="${idx}">
                <button type="button" class="remove-cart-item btn btn-xs btn-error absolute top-1 right-1">×</button>
                <div>${item.qty}× ${item.prod.nome || item.prod.nome_produto} — R$${item.price.toFixed(2)}</div>
                ${saborHtml}
                ${addsHtml}
            </div>`;
        }).join('');

        $('#cart-items').html(html);
        $('#cart-total').text(`R$ ${total.toFixed(2)}`);
    }

    window.__cartModule__ = {
        addItem(item) { cart.push(item); renderCart(); },
        renderCart,
        filterProducts,
        clear() { cart = []; renderCart(); },
        getItems() { return cart; }
    };

    $(document)
        .on('click', '.remove-cart-item', function () {
            const idx = $(this).closest('[data-index]').data('index');
            cart.splice(idx, 1);
            renderCart();
        })
        .on('click', '.product-card', function () {
            const id = $(this).data('id');
            $.post('crud/crud_atendimento.php', JSON.stringify({ action: 'detalhar_produto', id_produto: id }), function (res) {
                if (res.status === 'ok') {
                    $(document).trigger('produto:abrirDetalhe', res);
                } else {
                    Swal.fire('Erro', res.mensagem || 'Não foi possível carregar produto.', 'error');
                }
            }, 'json');
        });

    $('#filter-category, #filter-subcategory').change(filterProducts);

    // Executa se dados estiverem carregados previamente
    if (window.__produtos__) {
        filterProducts();
    }

})(jQuery);
