(function ($) {
    const CATS = window.__categorias__ || [];
    const SUBS = window.__subcategorias__ || [];
    const ALLP = window.__produtos__ || [];
    const TIPOCAT = window.__tipoCat__ || [];
    const ADDS = window.__adicionais__ || [];
    const CATREL = window.__catRelacionadas__ || [];

    let filtered = [...ALLP];
    let page = 1;
    const perPage = 8;
    let sortField = null;
    let sortAsc = true;

    function sortFiltered() {
        if (!sortField) return;
        filtered.sort((a, b) => {
            let va = a[sortField], vb = b[sortField];
            va = isNaN(va) ? (va || '').toString().toLowerCase() : +va;
            vb = isNaN(vb) ? (vb || '').toString().toLowerCase() : +vb;
            if (va < vb) return sortAsc ? -1 : 1;
            if (va > vb) return sortAsc ? 1 : -1;
            return 0;
        });
    }

    function renderTable() {
        sortFiltered();
        const start = (page - 1) * perPage;
        const slice = filtered.slice(start, start + perPage);
        const $tbody = $('#product-table-body').empty();
        slice.forEach(p => {
            $tbody.append(`
                <tr>
                    <td>${p.nome_produto}</td>
                    <td>${p.nome_categoria || ''}</td>
                    <td>R$ ${parseFloat(p.valor_produto).toFixed(2)}</td>
                    <td>${p.produto_ativo == 1 ? 'Sim' : 'Não'}</td>
                    <td class="flex gap-2">
                        <button class="btn btn-sm btn-outline btn-primary btn-edit" data-id="${p.id_produto}">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline btn-error btn-del" data-id="${p.id_produto}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
        renderPagination();
    }

    function renderPagination() {
        const total = Math.ceil(filtered.length / perPage);
        const $pg = $('#pagination').empty();
        for (let i = 1; i <= total; i++) {
            $pg.append(`<button class="btn btn-xs ${i === page ? 'btn-primary' : 'btn-ghost'} mr-1" data-page="${i}">${i}</button>`);
        }
    }

    function applyFilter() {
        const cat = $('#filter-category').val();
        const sub = $('#filter-subcategory').val();
        const txt = $('#filter-name').val().toLowerCase();
        filtered = ALLP.filter(p => {
            const ok1 = !cat || p.id_categoria == cat;
            const ok2 = !sub || SUBS.find(s => s.id_subcategoria == sub)?.id_categoria == p.id_categoria;
            const ok3 = !txt || p.nome_produto.toLowerCase().includes(txt);
            return ok1 && ok2 && ok3;
        });
        page = 1;
        renderTable();
    }

    function buildFilterSubcats() {
        const cat = $('#filter-category').val();
        const $fs = $('#filter-subcategory').empty().append('<option value="">Todas Subcategorias</option>');
        SUBS.filter(s => !cat || s.id_categoria == cat)
            .forEach(s => $fs.append(`<option value="${s.id_subcategoria}">${s.nome_subcategoria}</option>`));
    }

    function buildModalSubcats(selecionadas = []) {
        const catId = +$('#pf-categoria').val();
        const seen = new Set();
        const $wrap = $('#pf-subcategorias').empty();
        SUBS.forEach(s => {
            if (catId && s.id_categoria !== catId) return;
            if (seen.has(s.id_subcategoria)) return;
            seen.add(s.id_subcategoria);
            const ch = selecionadas.includes(s.id_subcategoria) ? 'checked' : '';
            $wrap.append(`
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="subcategorias[]" value="${s.id_subcategoria}" ${ch}/>
                    <span>${s.nome_subcategoria}</span>
                </label>
            `);
        });
    }

    function buildRelacionadasSection(catId) {
        const relacionadas = CATREL.filter(r => r.id_categoria == catId);
        const $rel = $('#pf-relacionadas-section').empty();
        relacionadas.forEach(r => {
            $rel.append(`
                <div class="mb-4 p-2 border rounded">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="pf-include-relacionada" data-rel-cat="${r.id_categoria_relacionada}"/>
                        <span>Incluir na categoria ${r.label_relacionada}</span>
                    </label>
                    <div class="pf-relacionada-valor-section hidden mt-2">
                        <label class="block font-medium mb-1">Valor ${r.label_relacionada}</label>
                        <input type="text" class="input input-bordered w-full currency pf-valor-relacionada" data-rel-cat="${r.id_categoria_relacionada}" value=""/>
                    </div>
                </div>
            `);
        });
    }

    function openForm(prod = {}) {
        $('#modal-title').text(prod.id_produto ? 'Editar Produto' : 'Novo Produto');
        $('#product-form')[0].reset();
        $('#pf-subcategorias, #pf-relacionadas-section').empty();

        $('#pf-id').val(prod.id_produto || '');
        $('#pf-nome').val(prod.nome_produto || '');
        $('#pf-valor').val(prod.valor_produto != null ? parseFloat(prod.valor_produto).toFixed(2) : '');
        $('#pf-descricao').val(prod.descricao_produto || '');
        $('#pf-categoria').val(prod.id_categoria || '');
        $('#pf-qtd-sabores').val(prod.qtd_sabores || 1);
        $('#pf-ativo').prop('checked', prod.produto_ativo != 0);

        buildModalSubcats(prod.subcategorias || []);
        buildRelacionadasSection(prod.id_categoria || '');

        if ((prod.qtd_sabores || 1) > 1) {
            $('#pf-tipo-calculo-group').removeClass('hidden')
                .find('select').val(prod.tipo_calculo_preco || 'maior');
        } else {
            $('#pf-tipo-calculo-group').addClass('hidden');
        }

        $('#modal-product').prop('checked', true);
    }

    $(document)
        .on('click', 'th.sortable', function () {
            const field = $(this).data('field');
            sortField === field ? sortAsc = !sortAsc : (sortField = field, sortAsc = true);
            $('th.sortable .sort-indicator').text('');
            $(this).find('.sort-indicator').text(sortAsc ? '▲' : '▼');
            renderTable();
        })
        .on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            $.getJSON(`crud/crud_produto.php?id=${id}`, res => {
                if (res.status === 'ok') openForm(res.produto);
                else Swal.fire('Erro', res.mensagem || 'Falha ao carregar', 'error');
            });
        })
        .on('click', '.btn-del', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Tem certeza?',
                text: 'Este produto será desativado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, desativar'
            }).then(({ isConfirmed }) => {
                if (!isConfirmed) return;
                $.ajax({
                    url: 'crud/crud_produto.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', id_produto: id })
                }).done(res => {
                    if (res.status === 'ok') {
                        Swal.fire('Deletado', 'Produto desativado.', 'success')
                            .then(() => location.reload());
                    } else Swal.fire('Erro', res.mensagem || 'Falha ao deletar', 'error');
                });
            });
        })
        .on('click', '#btn-new-product', () => openForm())
        .on('change', '#filter-category', () => { buildFilterSubcats(); applyFilter(); })
        .on('change', '#filter-subcategory', applyFilter)
        .on('input', '#filter-name', applyFilter)
        .on('click', '#pagination button', function () {
            page = +$(this).data('page');
            renderTable();
        })
        .on('change', '#pf-categoria', function () {
            buildModalSubcats();
            buildRelacionadasSection(+this.value);
        })
        .on('change', '.pf-include-relacionada', function () {
            const relCat = $(this).data('rel-cat');
            $(this).closest('div').find('.pf-relacionada-valor-section').toggleClass('hidden', !this.checked);
        })
        .on('submit', '#product-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);
            const catId = +$('#pf-categoria').val();
            data.id_categoria = catId;
            CATREL.filter(r => r.id_categoria == catId).forEach(r => {
                const relId = r.id_categoria_relacionada;
                const checked = $(`.pf-include-relacionada[data-rel-cat="${relId}"]`).is(':checked');
                if (checked) {
                    data[`include_${relId}`] = 1;
                    const valor = $(`.pf-valor-relacionada[data-rel-cat="${relId}"]`).val();
                    data[`valor_${relId}`] = parseFloat(valor.replace(',', '.')) || 0;
                }
            });
            data.qtd_sabores = +$('#pf-qtd-sabores').val() || 1;
            data.tipo_calculo_preco = $('#pf-tipo-calculo').val() || 'maior';
            data.produto_ativo = $('#pf-ativo').is(':checked') ? 1 : 0;
            data.subcategorias = $('#pf-subcategorias input:checked').map((_, cb) => +cb.value).get();

            $.ajax({
                url: 'crud/crud_produto.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success(res) {
                    if (res.status === 'ok') {
                        Swal.fire('Sucesso', 'Produto salvo!', 'success')
                            .then(() => { $('#modal-product').prop('checked', false); location.reload(); });
                    } else {
                        Swal.fire('Erro', res.mensagem || 'Falha ao salvar', 'error');
                    }
                }
            });
        });

    buildFilterSubcats();
    applyFilter();
    renderTable();
})(jQuery);
