(function ($) {
    // dados iniciais
    const CATS = window.__categorias__ || [];
    const SUBS = window.__subcategorias__ || [];
    // garante que cada produto já venha com subcategorias como array
    const ALLP = (window.__produtos__ || []).map(p => ({
        ...p,
        subcategorias: p.subcategorias || []
    }));
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
            $pg.append(`
        <button class="btn btn-xs ${i === page ? 'btn-primary' : 'btn-ghost'} mr-1"
                data-page="${i}">${i}</button>
      `);
        }
    }

    function applyFilter() {
        const cat = $('#filter-category').val();
        const sub = $('#filter-subcategory').val();
        const txt = $('#filter-name').val().toLowerCase();
        filtered = ALLP.filter(p => {
            const ok1 = !cat || p.id_categoria == cat;
            const ok2 = !sub || p.subcategorias.includes(+sub);
            const ok3 = !txt || p.nome_produto.toLowerCase().includes(txt);
            return ok1 && ok2 && ok3;
        });
        page = 1;
        renderTable();
    }

    function buildFilterSubcats() {
        const cat = $('#filter-category').val();
        const $fs = $('#filter-subcategory')
            .empty()
            .append('<option value="">Todas Subcategorias</option>');
        SUBS.filter(s => !cat || s.id_categoria == cat)
            .forEach(s => {
                $fs.append(`<option value="${s.id_subcategoria}">${s.nome_subcategoria}</option>`);
            });
    }

    function buildModalSubcats(selecionadas = []) {
        const catId = +$('#pf-categoria').val();
        const $wrap = $('#pf-subcategorias').empty();
        SUBS.filter(s => !catId || s.id_categoria == catId)
            .forEach(s => {
                const ch = selecionadas.includes(s.id_subcategoria) ? 'checked' : '';
                $wrap.append(`
           <label class="flex items-center gap-2">
             <input type="checkbox" name="subcategorias[]" value="${s.id_subcategoria}" ${ch}/>
             <span>${s.nome_subcategoria}</span>
           </label>
         `);
            });
    }

    function buildRelacionadasSection(catId, valores = {}) {
        const rels = CATREL.filter(r => r.id_categoria == catId);
        const $rel = $('#pf-relacionadas-section').empty();
        rels.forEach(r => {
            const val = valores[`valor_${r.id_categoria_relacionada}`] || '';
            const checked = valores[`include_${r.id_categoria_relacionada}`] ? 'checked' : '';
            $rel.append(`
        <div class="mb-4 p-2 border rounded">
          <label class="flex items-center gap-2">
            <input type="checkbox" class="pf-include-relacionada"
                   data-rel-cat="${r.id_categoria_relacionada}" ${checked}/>
            <span>Incluir na categoria ${r.label_relacionada}</span>
          </label>
          <div class="pf-relacionada-valor-section ${checked ? '' : 'hidden'} mt-2">
            <label class="block font-medium mb-1">Valor ${r.label_relacionada}</label>
            <input type="text"
                   class="input input-bordered w-full currency pf-valor-relacionada"
                   data-rel-cat="${r.id_categoria_relacionada}"
                   value="${val}"/>
          </div>
        </div>
      `);
        });
    }

    function buildTipos(catId) {
        const grupos = TIPOCAT.filter(tc => tc.id_categoria == catId);
        let htmlQtd = '', htmlItens = '';
        grupos.forEach(g => {
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

    function openForm(prod = {}) {
        $('#modal-title').text(prod.id_produto ? 'Editar Produto' : 'Novo Produto');
        $('#product-form')[0].reset();
        $('#pf-subcategorias,#pf-inclusos-quantidade,#pf-inclusos-itens,#pf-relacionadas-section').empty();
        $('#pf-id').val(prod.id_produto || '');
        $('#pf-nome').val(prod.nome_produto || '');
        $('#pf-valor').val(prod.valor_produto != null ? parseFloat(prod.valor_produto).toFixed(2) : '');
        $('#pf-descricao').val(prod.descricao_produto || '');
        $('#pf-categoria').val(prod.id_categoria || '');
        $('#pf-qtd-sabores').val(prod.qtd_sabores || 1);

        // tipo de cálculo
        if (+$('#pf-qtd-sabores').val() > 1) {
            $('#pf-tipo-calculo').prop('disabled', false).val(prod.tipo_calculo_preco);
        } else {
            $('#pf-tipo-calculo').prop('disabled', true).val('maior');
        }

        // controle de estoque: dispara change para mostrar/ocultar o grupo
        $('#pf-ctrl-estoque')
            .val(prod.controle_estoque ? '1' : '0')
            .trigger('change');
        if (prod.controle_estoque) {
            $('#pf-estoque').val(prod.qtd_produto);
        } else {
            $('#pf-estoque').val('');
        }

        $('#pf-ativo').prop('checked', prod.produto_ativo != 0);

        buildModalSubcats(prod.subcategorias || []);
        buildRelacionadasSection(prod.id_categoria || '', prod);
        buildTipos(prod.id_categoria);

        const hasIncl = prod.tipo_adicionais
            ? prod.tipo_adicionais.some(tipo =>
                tipo.adicionais.some(a => a.incluso == 1)
            )
            : false;
            
        $('#pf-has-inclusos').val(hasIncl ? '1' : '0').trigger('change');

        $('#modal-product').prop('checked', true);
    }

    $(document)
        .on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            $.getJSON(`crud/crud_produto.php?id=${id}`, res => {
                if (res.status === 'ok') openForm(res.produto);
                else Swal.fire('Erro', res.mensagem || 'Falha ao carregar', 'error');
            });
        })
        .on('click', '#btn-new-product', () => openForm())
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
        .on('change', '#filter-category', () => { buildFilterSubcats(); applyFilter(); })
        .on('change', '#filter-subcategory', applyFilter)
        .on('input', '#filter-name', applyFilter)
        .on('click', '#pagination button', function () { page = +$(this).data('page'); renderTable(); })
        .on('change', '#pf-categoria', function () {
            buildModalSubcats();
            buildRelacionadasSection(+this.value);
            buildTipos(+this.value);
        })
        .on('input', '#pf-qtd-sabores', function () {
            const qtd = +this.value;
            if (qtd > 1) $('#pf-tipo-calculo').prop('disabled', false);
            else {
                $('#pf-tipo-calculo').prop('disabled', true).val('maior');
            }
        })
        .on('change', '#pf-ctrl-estoque', function () {
            if (this.value == '1') {
                $('#pf-estoque-group').removeClass('hidden');
                $('#pf-estoque').prop('disabled', false);
            } else {
                $('#pf-estoque-group').addClass('hidden');
                $('#pf-estoque').prop('disabled', true).val('');
            }
        })
        .on('change', '.pf-include-relacionada', function () {
            $(this).closest('div')
                .find('.pf-relacionada-valor-section')
                .toggleClass('hidden', !this.checked);
        })

        .on('change', '#pf-has-inclusos', function () {
            if (this.value === '1') {
                $('#pf-inclusos-section').removeClass('hidden');
            } else {
                $('#pf-inclusos-section').addClass('hidden');
            }
        })

        .on('click', '#link-definir-itens', function (e) {
            e.preventDefault();
            $('#pf-inclusos-itens').toggleClass('hidden');
        })

        .on('submit', '#product-form', function (e) {
            e.preventDefault();
            // serializa o form inteiro, respeitando name[] e name[var][subvar]
            const arr = $(this).serializeArray();
            const data = {};
            arr.forEach(({ name, value }) => {
                // mapeia chaves como tipo_adicional[3][max_inclusos]
                const keys = name.replace(/\]/g, '').split('[');
                let cur = data;
                keys.forEach((key, i) => {
                    if (i === keys.length - 1) {
                        // último nível
                        if (cur[key] !== undefined) {
                            if (!Array.isArray(cur[key])) cur[key] = [cur[key]];
                            cur[key].push(value);
                        } else cur[key] = value;
                    } else {
                        cur[key] = cur[key] || {};
                        cur = cur[key];
                    }
                });
            });
            // adiciona controle de categoria relacionadas
            const catId = +$('#pf-categoria').val();
            CATREL.filter(r => r.id_categoria == catId).forEach(r => {
                const relId = r.id_categoria_relacionada;
                if ($(`.pf-include-relacionada[data-rel-cat="${relId}"]`).is(':checked')) {
                    data[`include_${relId}`] = 1;
                    const valor = $(`.pf-valor-relacionada[data-rel-cat="${relId}"]`).val();
                    data[`valor_${relId}`] = parseFloat(valor.replace(',', '.')) || 0;
                }
            });

            $.ajax({
                url: 'crud/crud_produto.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            }).done(res => {
                if (res.status === 'ok') {
                    Swal.fire('Sucesso', 'Produto salvo!', 'success')
                        .then(() => { $('#modal-product').prop('checked', false); location.reload(); });
                } else {
                    Swal.fire('Erro', res.mensagem || 'Falha ao salvar', 'error');
                }
            });
        });

    // inicialização
    buildFilterSubcats();
    applyFilter();
    renderTable();
})(jQuery);
