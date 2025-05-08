// carrinho.js
(function ($) {
    const products = window.__produtos__;
    const flavorsById = window.__flavorsAssoc__;
    const addonsById = window.__addonsAssoc__;
    let cart = [];

    function filterProducts() {
        const cat = $('#filter-category').val(),
            sub = $('#filter-subcategory').val(),
            txt = $('#filter-search').val().toLowerCase();

        const lista = products.filter(p => {
            const okCat = !cat || p.id_categoria == cat;
            const okSub = !sub || $('#filter-subcategory option:selected').data('cat') == p.id_categoria;
            const okTxt = !txt || p.nome.toLowerCase().includes(txt);
            return okCat && okSub && okTxt;
        });

        const html = lista.map(p => `
        <div class="card product-card border border-gray-200 hover:shadow-lg transition-all cursor-pointer" data-id="${p.id_produto}">
          <div class="card-body">
            <h4>${p.nome}</h4>
            <p>R$ ${parseFloat(p.valor_produto).toFixed(2)}</p>
          </div>
        </div>
      `).join('');

        $('#product-list').html(html);
    }

    function renderCart() {
        let total = 0;
        const html = cart.map((item, idx) => {
            total += item.qty * item.price;
            // sabor
            const saborHtml = item.flavorId
                ? `<div class="ml-4 text-sm">Sabor: ${(flavorsById[item.prod.id_produto] || [])
                    .find(s => s.id_produto == item.flavorId).nome}</div>`
                : '';
            // adicionais
            const addsHtml = item.addonsIds.length
                ? `<ul class="ml-4 list-disc list-inside text-sm">${item.addonsIds.map(aid => {
                    const ad = (addonsById[item.prod.id_produto] || []).find(x => x.id_adicional == aid);
                    return `<li>+ ${ad.nome} — R$${parseFloat(ad.preco).toFixed(2)}</li>`;
                }).join('')
                }</ul>`
                : '';
            return `
          <div class="relative border p-2 mb-2 rounded" data-index="${idx}">
            <button type="button" class="remove-cart-item btn btn-xs btn-error absolute top-1 right-1" title="Remover item">×</button>
            <div>${item.qty}× ${item.prod.nome} — R$${item.price.toFixed(2)}</div>
            ${saborHtml}
            ${addsHtml}
          </div>
        `;
        }).join('');

        $('#cart-items').html(html);
        $('#cart-total').text(`R$ ${total.toFixed(2)}`);
    }

    // API pública
    window.__cartModule__ = {
        addItem(item) { cart.push(item); renderCart(); },
        renderCart,
        filterProducts,
        clear() { cart = []; renderCart(); }
    };

    // eventos
    $(document)
        .on('click', '.remove-cart-item', function () {
            const idx = $(this).closest('[data-index]').data('index');
            cart.splice(idx, 1);
            renderCart();
        })
        .on('click', '.product-card', function () {
            const id = $(this).data('id'),
                prod = products.find(x => x.id_produto == id);
            $(document).trigger('produto:abrirDetalhe', prod);
        });

    $('#filter-category,#filter-subcategory').change(filterProducts);
    $('#filter-search').on('input', filterProducts);

    // inicial
    $(filterProducts);

})(jQuery);
