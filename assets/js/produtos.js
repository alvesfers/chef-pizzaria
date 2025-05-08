// produtos.js
(function ($) {
    // --- referências injetadas via PHP ---
    const CATS = window.__categorias__;      // [{id_categoria, nome_categoria, …}, …]
    const SUBS = window.__subcategorias__;   // [{id_subcategoria, nome_subcategoria, id_categoria}, …]
    const ALLP = window.__produtos__;        // [{id_produto,nome,…,qtd_sabores,subcategorias:[…]}, …]
    const TIPOCAT = window.__tipoCat__;         // [{id_categoria,id_tipo_adicional,nome_tipo_adicional,max_escolha}, …]
    const ADDS = window.__adicionais__;      // [{id_adicional,id_tipo_adicional,nome,preco}, …]

    // --- estado ---
    let filtered = [...ALLP];
    let page = 1;
    const perPage = 8;

    // --- 1) render da tabela e paginação ---
    function renderTable() {
        const start = (page - 1) * perPage;
        const slice = filtered.slice(start, start + perPage);
        const $b = $('#product-table-body').empty();
        slice.forEach(p => {
            $b.append(`
              <tr>
                <td>${p.id_produto}</td>
                <td>${p.nome}</td>
                <td>${p.nome_categoria || ''}</td>
                <td>R$ ${parseFloat(p.valor_produto).toFixed(2)}</td>
                <td>${p.qtd_sabores > 1 ? 'Sim' : 'Não'}</td>
                <td class="flex gap-2">
                  <button class="btn btn-sm btn-outline btn-primary btn-edit" data-id="${p.id_produto}">
                    <i class="fa fa-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline btn-error btn-del" data-id="${p.id_produto}">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>`);
        });
        renderPagination();
    }
    function renderPagination() {
        const total = Math.ceil(filtered.length / perPage);
        const $pg = $('#pagination').empty();
        for (let i = 1; i <= total; i++) {
            $pg.append(`
              <button class="btn btn-xs ${i === page ? 'btn-primary' : 'btn-ghost'} mr-1"
                      data-page="${i}">${i}</button>`);
        }
    }

    // --- 2) filtros ---
    function applyFilter() {
        const cat = $('#filter-category').val();
        const sub = $('#filter-subcategory').val();
        const txt = $('#filter-name').val().toLowerCase();
        filtered = ALLP.filter(p => {
            const ok1 = !cat || p.id_categoria == cat;
            const ok2 = !sub || SUBS.find(s => s.id_subcategoria == sub).id_categoria == p.id_categoria;
            const ok3 = !txt || p.nome.toLowerCase().includes(txt);
            return ok1 && ok2 && ok3;
        });
        page = 1;
        renderTable();
    }

    // --- 3) popula selects de filtro ---
    function buildFilterOptions() {
        const $fc = $('#filter-category').empty().append('<option value="">Todas Categorias</option>');
        CATS.forEach(c => $fc.append(`<option value="${c.id_categoria}">${c.nome_categoria}</option>`));
        buildFilterSubcats();
    }
    function buildFilterSubcats() {
        const cat = $('#filter-category').val();
        const $fs = $('#filter-subcategory').empty().append('<option value="">Todas Subcategorias</option>');
        SUBS.filter(s => !cat || s.id_categoria == cat)
            .forEach(s => $fs.append(`<option value="${s.id_subcategoria}">${s.nome_subcategoria}</option>`));
    }

    // --- 4) abre / popula modal ---
    function openForm(prod = null) {
        const isEdit = !!prod;
        $('#modal-title').text(isEdit ? 'Editar Produto' : 'Novo Produto');
        const f = $('#product-form')[0];
        f.reset();
        // limpa grupos dinâmicos
        $('#pf-qtd-sabores, #pf-tipo-calculo-group, #pf-inclusos-section')
            .addClass('hidden');
        $('#pf-tipos,#pf-inclusos-quantidade,#pf-inclusos-itens').empty();
        buildModalSubcats();
        if (isEdit) {
            console.log(prod)
            $('#pf-id').val(prod.id_produto);
            $('#pf-nome').val(prod.nome);
            $('#pf-categoria').val(prod.id_categoria);
            $('#pf-valor').val(parseFloat(prod.valor_produto).toFixed(2));
            $('#pf-estoque').val(prod.qtd_produto || 0);
            $('#pf-descricao').val(prod.descricao_produto || '');
            // sabores
            $('#pf-qtd-sabores').val(prod.qtd_sabores);
            if (prod.qtd_sabores > 1) {
                $('#pf-qtd-sabores').trigger('input');
                $('#pf-tipo-calculo').val(prod.tipo_calculo_preco || 'maior');
            }
            // subcategorias
            if (Array.isArray(prod.subcategorias)) {
                $('#pf-subcategorias input').prop('checked', false);
                prod.subcategorias.forEach(sc => {
                    $(`#pf-subcategorias input[value="${sc.id_subcategoria}"]`)
                        .prop('checked', true);
                });
            }
            // inclusos
            $('#pf-has-inclusos').val(prod.has_inclusos ? '1' : '0');
            if (prod.has_inclusos) {
                $('#pf-has-inclusos').trigger('change');
                $('#pf-incluso_mode').val(prod.incluso_mode || '0');
            }
        }
        buildTipos();
        $('#modal-product').prop('checked', true);
    }

    function buildModalSubcats() {
        const $ms = $('#pf-subcategorias').empty();
        SUBS.forEach(s => {
            $ms.append(`
              <label class="flex items-center gap-2">
                <input type="checkbox" name="subcategorias[]" value="${s.id_subcategoria}" />
                <span>${s.nome_subcategoria}</span>
              </label>`);
        });
    }

    // --- 5) tipos de adicionais / inclusos ---
    function buildTipos() {
        const cat = +$('#pf-categoria').val();
        const grupos = TIPOCAT.filter(tc => tc.id_categoria == cat);
        let htmlQtd = '', htmlItens = '';
        grupos.forEach(g => {
            // quantidade
            htmlQtd += `
              <div class="flex items-center gap-2">
                <label>${g.nome_tipo_adicional} (até)
                  <input type="number"
                         class="input input-xs w-16 pf-maxinc"
                         data-tipo="${g.id_tipo_adicional}"
                         name="inclusos_qtd[${g.id_tipo_adicional}]"
                         value="${g.max_escolha}"
                         min="0"
                  /> inclusos
                </label>
              </div>`;
            // itens
            const adds = ADDS.filter(a => a.id_tipo_adicional == g.id_tipo_adicional);
            let bloc = `<h4 class="font-semibold">${g.nome_tipo_adicional}</h4><div class="grid grid-cols-2 gap-2">`;
            adds.forEach(a => {
                bloc += `
                  <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox"
                           class="pf-incluso-itens"
                           name="inclusos_itens[]"
                           value="${a.id_adicional}"
                    /> ${a.nome} — R$${parseFloat(a.preco).toFixed(2)}
                  </label>`;
            });
            bloc += '</div>';
            htmlItens += `<div class="mb-2">${bloc}</div>`;
        });
        $('#pf-inclusos-quantidade').html(htmlQtd);
        $('#pf-inclusos-itens').html(htmlItens);
    }

    // --- 6) submit do form ---
    $('#product-form').on('submit', function (e) {
        e.preventDefault();
        const data = {};
        $(this).serializeArray().forEach(x => data[x.name] = x.value);
        data.produto_ativo = 1;
        data.qtd_sabores = +$('#pf-qtd-sabores').val() || 1;
        data.tipo_calculo_preco = $('#pf-tipo-calculo').val() || 'maior';
        data.subcategorias = $('#pf-subcategorias input:checked').map((_, cb) => +cb.value).get();
        // inclusos
        if ($('#pf-has-inclusos').val() === '1') {
            data.has_inclusos = 1;
            data.incluso_mode = $('#pf-incluso_mode').val();
            if (data.incluso_mode === '0') {
                data.inclusos_qtd = {};
                $('.pf-maxinc').each(function () {
                    data.inclusos_qtd[$(this).data('tipo')] = +$(this).val();
                });
            } else {
                data.inclusos_itens = $('.pf-incluso-itens:checked').map((_, cb) => +cb.value).get();
            }
        } else data.has_inclusos = 0;

        $.ajax({
            url: 'crud/crud_produto.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success(res) {
                if (res.status === 'ok') {
                    const idx = ALLP.findIndex(p => p.id_produto == res.id);
                    if (idx >= 0) Object.assign(ALLP[idx], data);
                    applyFilter();
                    $('#modal-product').prop('checked', false);
                } else Swal.fire('Erro', res.mensagem, 'error');
            }
        });
    });

    // --- 7) delegação de eventos ---
    $(document)
        .on('change', '#filter-category', () => {
            buildFilterSubcats(); applyFilter();
        })
        .on('change', '#filter-subcategory', applyFilter)
        .on('input', '#filter-name', applyFilter)
        .on('click', '#pagination button', function () {
            page = +$(this).data('page'); renderTable();
        })
        .on('click', '#btn-new-product', () => openForm(null))
        .on('click', '.btn-edit', function () {
            const id = +$(this).data('id'),
                prod = ALLP.find(p => p.id_produto === id);
            openForm(prod);
        })
        .on('click', '.btn-del', function () {
            if (!confirm('Excluir este produto?')) return;
            const id = +$(this).data('id');
            $.post('crud/crud_produto.php',
                JSON.stringify({ action: 'delete', id_produto: id }),
                res => {
                    if (res.status === 'ok') {
                        const i = ALLP.findIndex(p => p.id_produto === id);
                        if (i >= 0) ALLP.splice(i, 1);
                        applyFilter();
                    } else Swal.fire('Erro', res.mensagem, 'error');
                }, 'json');
        })
        .on('change', '#pf-categoria', buildTipos)
        .on('input', '#pf-qtd-sabores', function () {
            if (+this.value > 1) $('#pf-tipo-calculo-group').removeClass('hidden');
            else $('#pf-tipo-calculo-group').addClass('hidden');
        })
        .on('change', '#pf-has-inclusos', function () {
            if (this.value === '1') $('#pf-inclusos-section').removeClass('hidden');
            else $('#pf-inclusos-section').addClass('hidden');
        })
        .on('change', '#pf-incluso_mode', function () {
            if (this.value === '0') {
                $('#pf-inclusos-quantidade').removeClass('hidden');
                $('#pf-inclusos-itens').addClass('hidden');
            } else {
                $('#pf-inclusos-quantidade').addClass('hidden');
                $('#pf-inclusos-itens').removeClass('hidden');
            }
        });

    // --- inicialização ---
    buildFilterOptions();
    applyFilter();

})(jQuery);
