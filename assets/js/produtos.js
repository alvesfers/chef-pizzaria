// produtos.js
(function ($) {
    const CATS = window.__categorias__ || [];
    const SUBS = window.__subcategorias__ || [];
    const ALLP = window.__produtos__ || [];
    const TIPOCAT = window.__tipoCat__ || [];
    const ADDS = window.__adicionais__ || [];

    let filtered = [...ALLP];
    let page = 1;
    const perPage = 8;

    // qual campo e direção de sort
    let sortField = null;
    let sortAsc = true;

    // função genérica de comparar
    function sortFiltered() {
        if (!sortField) return;
        filtered.sort((a, b) => {
            let va = a[sortField], vb = b[sortField];
            // detecta número?
            if (!isNaN(parseFloat(va)) && !isNaN(parseFloat(vb))) {
                va = parseFloat(va);
                vb = parseFloat(vb);
            } else {
                va = (va || '').toString().toLowerCase();
                vb = (vb || '').toString().toLowerCase();
            }
            if (va < vb) return sortAsc ? -1 : 1;
            if (va > vb) return sortAsc ? 1 : -1;
            return 0;
        });
    }

    // renderiza tabela + aplica sort antes de fatiar
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
                        <button class="btn btn-sm btn-outline btn-primary btn-edit"
                                data-id="${p.id_produto}">
                          <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline btn-error btn-del"
                                data-id="${p.id_produto}">
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
              <button class="btn btn-xs ${i === page ? 'btn-primary' : 'btn-ghost'} mr-1"
                      data-page="${i}">
                ${i}
              </button>
            `);
        }
    }

    // filtros (já reintegra sort após filtrar)
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
        const $fs = $('#filter-subcategory').empty().append(
            '<option value="">Todas Subcategorias</option>'
        );
        SUBS.filter(s => !cat || s.id_categoria == cat)
            .forEach(s => $fs.append(`
              <option value="${s.id_subcategoria}">
                ${s.nome_subcategoria}
              </option>
            `));
    }

    // subcategorias no modal
    function buildModalSubcats(selecionadas = []) {
        const catId = +$('#pf-categoria').val();        // categoria selecionada
        const seen = new Set();                        // para rastrear únicos
        const $wrap = $('#pf-subcategorias').empty();

        SUBS.forEach(s => {
            // 1) só subcats da categoria atual
            if (catId && s.id_categoria !== catId) return;

            // 2) pula duplicados
            if (seen.has(s.id_subcategoria)) return;
            seen.add(s.id_subcategoria);

            // 3) marca se já estiver na lista de selecionadas
            const checked = selecionadas.includes(s.id_subcategoria) ? 'checked' : '';

            $wrap.append(`
                <label class="flex items-center gap-2">
                    <input type="checkbox"
                           name="subcategorias[]"
                           value="${s.id_subcategoria}"
                           ${checked} />
                    <span>${s.nome_subcategoria}</span>
                </label>
            `);
        });
    }


    // tipos de adicionais no modal
    function buildTipos(catId) {
        const grupos = TIPOCAT.filter(tc => tc.id_categoria == catId);
        let htmlQtd = '';
        let htmlItens = '';

        grupos.forEach(g => {
            htmlQtd += `
                <div class="flex items-center gap-2">
                    <label>${g.nome_tipo_adicional} (até)
                        <input type="number"
                               class="input input-xs w-16 pf-maxinc"
                               data-tipo="${g.id_tipo_adicional}"
                               name="tipo_adicional[${g.id_tipo_adicional}][max_inclusos]"
                               value="${g.max_inclusos}"
                               min="0" />
                        inclusos
                    </label>
                </div>`;

            const adds = ADDS.filter(a => a.id_tipo_adicional == g.id_tipo_adicional);
            let bloc = `<h4 class="font-semibold">${g.nome_tipo_adicional}</h4><div class="grid grid-cols-2 gap-2">`;
            adds.forEach(a => {
                bloc += `
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox"
                               class="pf-incluso-itens"
                               data-tipo="${g.id_tipo_adicional}"
                               name="tipo_adicional[${g.id_tipo_adicional}][adicionais][]"
                               value="${a.id_adicional}" />
                        ${a.nome_adicional} — R$${parseFloat(a.valor_adicional).toFixed(2)}
                    </label>`;
            });
            bloc += '</div>';
            htmlItens += `<div class="mb-2">${bloc}</div>`;
        });

        $('#pf-inclusos-quantidade').html(htmlQtd);
        $('#pf-inclusos-itens').html(htmlItens);
    }

    // abre modal para novo/edição
    function openForm(prod = {}) {
        $('#modal-title').text(prod.id_produto ? 'Editar Produto' : 'Novo Produto');
        $('#product-form')[0].reset();
        $('#pf-subcategorias, #pf-inclusos-quantidade, #pf-inclusos-itens').empty();
        $('#pf-tipo-calculo-group').addClass('hidden');
        $('#pf-inclusos-section, #pf-estoque-group').addClass('hidden');

        // campos básicos
        $('#pf-id').val(prod.id_produto || '');
        $('#pf-nome').val(prod.nome_produto || '');
        $('#pf-valor').val(prod.valor_produto != null ? parseFloat(prod.valor_produto).toFixed(2) : '');
        $('#pf-descricao').val(prod.descricao_produto || '');
        $('#pf-categoria').val(prod.id_categoria || '');
        $('#pf-qtd-sabores').val(prod.qtd_sabores || 1);

        // qtd_sabores > 1 ?
        if ((prod.qtd_sabores || 1) > 1) {
            $('#pf-tipo-calculo-group').removeClass('hidden');
            $('#pf-tipo-calculo').val(prod.tipo_calculo_preco || 'maior');
        }

        // ** nova lógica de Controle de Estoque **
        let ctrl;
        if (prod.id_produto) {
            // ao editar: se qtd_produto >= 0 → sim, senão (ex: -1) → não
            ctrl = prod.qtd_produto != null && prod.qtd_produto >= 0 ? '1' : '0';
        } else {
            // novo produto: padrão "Sim"
            ctrl = '1';
        }
        $('#pf-ctrl-estoque').val(ctrl);
        if (ctrl === '1') {
            $('#pf-estoque-group').removeClass('hidden');
            // ao criar, prod.qtd_produto pode não existir → usar 0
            $('#pf-estoque').val(prod.qtd_produto >= 0 ? prod.qtd_produto : 0);
        }

        // produto ativo
        $('#pf-ativo').prop('checked', prod.produto_ativo != 0);

        // subcats e adicionais
        buildModalSubcats(prod.subcategorias || []);
        $('#pf-has-inclusos').val(prod.has_inclusos ? '1' : '0');
        $('#pf-incluso_mode').val(prod.incluso_mode || '0');
        if (prod.has_inclusos) $('#pf-inclusos-section').removeClass('hidden');

        buildTipos(prod.id_categoria);
        (prod.tipo_adicionais || []).forEach(t => {
            $(`.pf-maxinc[data-tipo="${t.id_tipo_adicional}"]`).val(t.max_inclusos);
            (t.adicionais || []).forEach(a => {
                $(`.pf-incluso-itens[value="${a.id_adicional}"]`).prop('checked', a.incluso === 1);
            });
        });

        $('#modal-product').prop('checked', true);
    }


    $(document).on('click', 'th.sortable', function () {
        const field = $(this).data('field');
        if (sortField === field) {
            sortAsc = !sortAsc;
        } else {
            sortField = field;
            sortAsc = true;
        }
        // limpa ícones
        $('th.sortable .sort-indicator').text('');
        // seta a seta atual
        $(this).find('.sort-indicator').text(sortAsc ? '▲' : '▼');
        // re-renderiza
        renderTable();
    });
    // eventos
    $(document)
        // abrir edição
        .on('click', '.btn-edit', function () {
            $.getJSON(`crud/crud_produto.php?id=${$(this).data('id')}`, res => {
                if (res.status === 'ok') openForm(res.produto);
                else Swal.fire('Erro', res.mensagem || 'Falha ao carregar', 'error');
            });
        })
        // delete
        .on('click', '.btn-del', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Tem certeza?',
                text: 'Este produto será desativado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, desativar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'crud/crud_produto.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'delete',
                            id_produto: id
                        }),
                    }).done(res => {
                        if (res.status === 'ok') {
                            Swal.fire('Deletado', 'Produto desativado com sucesso.', 'success');
                            // ou remove da tabela: $(`button[data-id="${id}"]`).closest('tr').remove();
                            location.reload();
                        } else {
                            Swal.fire('Erro', res.mensagem || 'Falha ao deletar.', 'error');
                        }
                    });
                }
            });
        })
        // novo produto
        .on('click', '#btn-new-product', () => openForm())
        // filtros
        .on('change', '#filter-category', () => { buildFilterSubcats(); applyFilter(); })
        .on('change', '#filter-subcategory', applyFilter)
        .on('input', '#filter-name', applyFilter)
        // paginação
        .on('click', '#pagination button', function () {
            page = +$(this).data('page');
            renderTable();
        })
        // ao mudar categoria no modal
        .on('change', '#pf-categoria', function () {
            buildTipos(+this.value);
        })
        // ao mudar qtd_sabores
        .on('input', '#pf-qtd-sabores', function () {
            $(this).val() > 1
                ? $('#pf-tipo-calculo-group').removeClass('hidden')
                : $('#pf-tipo-calculo-group').addClass('hidden');
        })
        // controle de estoque
        .on('change', '#pf-ctrl-estoque', function () {
            $('#pf-estoque-group').toggleClass('hidden', this.value !== '1');
        })
        // has_inclusos
        .on('change', '#pf-has-inclusos', function () {
            $('#pf-inclusos-section').toggleClass('hidden', this.value !== '1');
        })
        // modo de inclusos
        .on('change', '#pf-incluso_mode', function () {
            $('#pf-inclusos-quantidade').toggleClass('hidden', this.value !== '0');
            $('#pf-inclusos-itens').toggleClass('hidden', this.value !== '1');
        })
        // envio do form
        .on('submit', '#product-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);

            // ajustes finais antes do POST
            data.qtd_sabores = +$('#pf-qtd-sabores').val() || 1;
            data.tipo_calculo_preco = $('#pf-tipo-calculo').val() || 'maior';
            data.controle_estoque = $('#pf-ctrl-estoque').val();
            data.qtd_produto = data.controle_estoque === '1'
                ? (+$('#pf-estoque').val() || 0)
                : -1;
            data.produto_ativo = $('#pf-ativo').is(':checked') ? 1 : 0;
            data.subcategorias = $('#pf-subcategorias input:checked').map((_, cb) => +cb.value).get();

            // inclusos
            if ($('#pf-has-inclusos').val() === '1') {
                data.has_inclusos = 1;
                data.incluso_mode = $('#pf-incluso_mode').val();
                data.tipo_adicional = {};
                if (data.incluso_mode === '0') {
                    $('.pf-maxinc').each(function () {
                        const tipo = $(this).data('tipo');
                        data.tipo_adicional[tipo] = { max_inclusos: +$(this).val() };
                    });
                } else {
                    $('.pf-incluso-itens:checked').each(function () {
                        const tipo = $(this).data('tipo');
                        data.tipo_adicional[tipo] = data.tipo_adicional[tipo] || { adicionais: [] };
                        data.tipo_adicional[tipo].adicionais.push(+$(this).val());
                    });
                }
            } else {
                data.has_inclusos = 0;
            }

            // envia
            $.ajax({
                url: 'crud/crud_produto.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success(res) {
                    if (res.status === 'ok') {
                        Swal.fire('Sucesso', 'Produto salvo com sucesso!', 'success');
                        $('#modal-product').prop('checked', false);
                        location.reload();
                    } else {
                        Swal.fire('Erro', res.mensagem || 'Falha ao salvar', 'error');
                    }
                }
            });
        });

    // inicialização
    buildFilterSubcats();
    applyFilter();
    renderTable();

})(jQuery);
