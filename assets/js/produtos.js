// produtos.js
(function ($) {
    const CATS = window.__categorias__ || [];
    const SUBS = window.__subcategorias__ || [];
    const ALLP = window.__produtos__ || [];
    const TIPOCAT = window.__tipoCat__ || [];
    const ADDS = window.__adicionais__ || [];

    // detectar ID da categoria "pizza" dinamicamente (substitua "pizza" se necessário)
    const pizzaCat = CATS.find(c => c.nome_categoria.toLowerCase() === 'pizza');
    const pizzaCatId = pizzaCat ? pizzaCat.id_categoria : null;

    let filtered = [...ALLP];
    let page = 1;
    const perPage = 8;

    let sortField = null;
    let sortAsc = true;

    /**************************************************
     * Funções de renderização e filtro (sem alterações)
     **************************************************/
    function sortFiltered() {
        if (!sortField) return;
        filtered.sort((a, b) => {
            let va = a[sortField], vb = b[sortField];
            if (!isNaN(parseFloat(va)) && !isNaN(parseFloat(vb))) {
                va = parseFloat(va); vb = parseFloat(vb);
            } else {
                va = (va || '').toString().toLowerCase();
                vb = (vb || '').toString().toLowerCase();
            }
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
            $pg.append(`
              <button class="btn btn-xs ${i === page ? 'btn-primary' : 'btn-ghost'} mr-1" data-page="${i}">
                ${i}
              </button>
            `);
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
        page = 1; renderTable();
    }
    function buildFilterSubcats() {
        const cat = $('#filter-category').val();
        const $fs = $('#filter-subcategory').empty().append('<option value="">Todas Subcategorias</option>');
        SUBS.filter(s => !cat || s.id_categoria == cat)
            .forEach(s => $fs.append(`<option value="${s.id_subcategoria}">${s.nome_subcategoria}</option>`));
    }

    /**************************************************
     * Montagem do formulário de modal
     **************************************************/
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

    function buildTipos(catId) {
        const grupos = TIPOCAT.filter(tc => tc.id_categoria == catId);
        let htmlQtd = '', htmlItens = '';
        grupos.forEach(g => {
            // máximo por tipo
            htmlQtd += `
                <div class="flex items-center gap-2">
                  <label>${g.nome_tipo_adicional} (até)
                    <input type="number" class="input input-xs w-16 pf-maxinc"
                           data-tipo="${g.id_tipo_adicional}"
                           name="tipo_adicional[${g.id_tipo_adicional}][max_inclusos]"
                           value="${g.max_inclusos}" min="0"/>
                    inclusos
                  </label>
                </div>`;
            // checkboxes de adicionais
            const adds = ADDS.filter(a => a.id_tipo_adicional == g.id_tipo_adicional);
            let bloc = `<h4 class="font-semibold">${g.nome_tipo_adicional}</h4><div class="grid grid-cols-2 gap-2">`;
            adds.forEach(a => {
                bloc += `
                    <label class="flex items-center gap-2 text-sm">
                      <input type="checkbox" class="pf-incluso-itens"
                             data-tipo="${g.id_tipo_adicional}"
                             name="tipo_adicional[${g.id_tipo_adicional}][adicionais][]"
                             value="${a.id_adicional}"/>
                      ${a.nome_adicional} — R$${parseFloat(a.valor_adicional).toFixed(2)}
                    </label>`;
            });
            bloc += '</div>';
            htmlItens += `<div class="mb-2">${bloc}</div>`;
        });
        $('#pf-inclusos-quantidade').html(htmlQtd);
        $('#pf-inclusos-itens').html(htmlItens);
    }

    // exibe/oculta seção fogazza
    function handleFogazzaSection() {
        if ($('#pf-categoria').val() == 1) {
            $('#pf-fogazza-section').removeClass('hidden');
        } else {
            $('#pf-fogazza-section').addClass('hidden');
            $('#pf-include-fogazza').prop('checked', false);
            $('#pf-fogazza-value-section').addClass('hidden');
            $('#pf-valor-fogazza').val('');
        }
    }

    function openForm(prod = {}) {
        // reset geral
        $('#modal-title').text(prod.id_produto ? 'Editar Produto' : 'Novo Produto');
        $('#product-form')[0].reset();
        $('#pf-subcategorias,#pf-inclusos-quantidade,#pf-inclusos-itens').empty();
        $('#pf-tipo-calculo-group,#pf-inclusos-section,#pf-estoque-group,#pf-fogazza-section').addClass('hidden');
        $('#pf-fogazza-value-section').addClass('hidden');

        // preencher campos
        $('#pf-id').val(prod.id_produto || '');
        $('#pf-nome').val(prod.nome_produto || '');
        $('#pf-valor').val(prod.valor_produto != null ? parseFloat(prod.valor_produto).toFixed(2) : '');
        $('#pf-descricao').val(prod.descricao_produto || '');
        $('#pf-categoria').val(prod.id_categoria || '');
        $('#pf-qtd-sabores').val(prod.qtd_sabores || 1);

        // sabores
        if ((prod.qtd_sabores || 1) > 1) {
            $('#pf-tipo-calculo-group').removeClass('hidden')
                .find('select').val(prod.tipo_calculo_preco || 'maior');
        }
        // estoque
        const ctrl = prod.id_produto
            ? (prod.qtd_produto != null && prod.qtd_produto >= 0 ? '1' : '0')
            : '1';
        $('#pf-ctrl-estoque').val(ctrl);
        if (ctrl === '1') {
            $('#pf-estoque-group').removeClass('hidden');
            $('#pf-estoque').val(prod.qtd_produto >= 0 ? prod.qtd_produto : 0);
        }
        $('#pf-ativo').prop('checked', prod.produto_ativo != 0);

        // subcats + inclusos
        buildModalSubcats(prod.subcategorias || []);
        $('#pf-has-inclusos').val(prod.has_inclusos ? '1' : '0');
        if (prod.has_inclusos) $('#pf-inclusos-section').removeClass('hidden');

        // tipos + adicionais
        buildTipos(prod.id_categoria);
        (prod.tipo_adicionais || []).forEach(t => {
            $(`.pf-maxinc[data-tipo="${t.id_tipo_adicional}"]`).val(t.max_inclusos);
            (t.adicionais || []).forEach(a => {
                $(`.pf-incluso-itens[value="${a.id_adicional}"]`).prop('checked', a.incluso === 1);
            });
        });

        // fogazza
        handleFogazzaSection();

        $('#modal-product').prop('checked', true);
    }


    /**************************************************
     * Bindings de eventos
     **************************************************/
    // ordenação
    $(document).on('click', 'th.sortable', function () {
        const field = $(this).data('field');
        sortField === field ? sortAsc = !sortAsc : (sortField = field, sortAsc = true);
        $('th.sortable .sort-indicator').text('');
        $(this).find('.sort-indicator').text(sortAsc ? '▲' : '▼');
        renderTable();
    });

    // exibe lista de itens inclusos
    $(document).on('click', '#link-definir-itens', function (e) {
        e.preventDefault();
        $('#pf-inclusos-itens').toggleClass('hidden');
    });

    // exibe/oculta fogazza
    $(document).on('change', '#pf-categoria', function () {
        buildTipos(+this.value);
        buildModalSubcats();
        handleFogazzaSection();
    });

    // toggles
    $(document)
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
            page = +$(this).data('page'); renderTable();
        })
        .on('input', '#pf-qtd-sabores', function () {
            $(this).val() > 1
                ? $('#pf-tipo-calculo-group').removeClass('hidden')
                : $('#pf-tipo-calculo-group').addClass('hidden');
        })
        .on('change', '#pf-ctrl-estoque', function () {
            $('#pf-estoque-group').toggleClass('hidden', this.value !== '1');
        })
        .on('change', '#pf-has-inclusos', function () {
            const show = this.value === '1';
            $('#pf-inclusos-section').toggleClass('hidden', !show);
            if (show) $('#pf-inclusos-itens').addClass('hidden');
        })
        .on('change', '#pf-include-fogazza', function () {
            $('#pf-fogazza-value-section').toggleClass('hidden', !this.checked);
        })
        .on('change', '.pf-incluso-itens', function () {
            const tipo = $(this).data('tipo');
            const max = +$('.pf-maxinc[data-tipo="' + tipo + '"]').val();
            const sel = $(`.pf-incluso-itens[data-tipo="${tipo}"]:checked`).length;
            if (sel > max) {
                Swal.fire('Atenção', `Máximo de ${max} item(ns).`, 'warning');
                $(this).prop('checked', false);
            }
        })
        // ... dentro de $(document).on('submit', '#product-form', function (e) { ... }

        .on('submit', '#product-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);

            // Nome base digitado
            const nomeBase = $('#pf-nome').val().trim();
            const catId = $('#pf-categoria').val();

            // Se categoria for pizza, prefixa o nome automaticamente
            if ($('#pf-categoria').val() == 1) {
                data.nome_produto = `Pizza de ${nomeBase}`;
                if ($('#pf-include-fogazza').is(':checked')) {
                    data.nome_fogazza = `Fogazza de ${nomeBase}`;
                }
            } else {
                data.nome_produto = nomeBase;
            }

            data.qtd_sabores = +$('#pf-qtd-sabores').val() || 1;
            data.tipo_calculo_preco = $('#pf-tipo-calculo').val() || 'maior';
            data.controle_estoque = $('#pf-ctrl-estoque').val();
            data.qtd_produto = data.controle_estoque === '1'
                ? (+$('#pf-estoque').val() || 0)
                : -1;
            data.produto_ativo = $('#pf-ativo').is(':checked') ? 1 : 0;
            data.subcategorias = $('#pf-subcategorias input:checked')
                .map((_, cb) => +cb.value).get();

            // Inclusos
            data.has_inclusos = 1;
            data.tipo_adicional = {};
            $('.pf-maxinc').each(function () {
                const tipo = $(this).data('tipo');
                data.tipo_adicional[tipo] = data.tipo_adicional[tipo] || {};
                data.tipo_adicional[tipo].max_inclusos = +$(this).val();
            });
            $('.pf-incluso-itens:checked').each(function () {
                const tipo = $(this).data('tipo');
                if (!data.tipo_adicional[tipo]) {
                    data.tipo_adicional[tipo] = {};
                }
                if (!Array.isArray(data.tipo_adicional[tipo].adicionais)) {
                    data.tipo_adicional[tipo].adicionais = [];
                }
                data.tipo_adicional[tipo].adicionais.push(+$(this).val());
            });

            // Fogazza
            if ($('#pf-categoria').val() == 1 && $('#pf-include-fogazza').is(':checked')) {
                data.include_fogazza = 1;
                const v = parseFloat($('#pf-valor-fogazza').val().replace(',', '.'));
                data.valor_fogazza = isNaN(v) ? 0 : v;
                const nomeBase = $('#pf-nome').val().trim();
                data.nome_fogazza = `Fogazza de ${nomeBase}`;
            }

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
        })


    // inicialização
    buildFilterSubcats();
    applyFilter();
    renderTable();

})(jQuery);
