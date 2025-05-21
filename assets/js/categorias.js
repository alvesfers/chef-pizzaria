(function ($) {
    const ALL = window.__categorias__ || [];
    let SUBCATS = window.__subcategorias__ || [];
    const TIPOADDS = window.__tipoAdicionais__ || [];
    let filtered = [...ALL];
    let page = 1;
    const perPage = 10;

    // render tabela de categorias (sem alterações)
    function renderTable() {
        const start = (page - 1) * perPage;
        const slice = filtered.slice(start, start + perPage);
        const $tbody = $('#category-table-body').empty();
        slice.forEach(cat => {
            $tbody.append(`
                <tr>
                  <td>${cat.nome_categoria}</td>
                  <td>${cat.tem_qtd == 1 ? 'Sim' : 'Não'}</td>
                  <td>${cat.categoria_ativa == 1 ? 'Sim' : 'Não'}</td>
                  <td>${cat.ordem_exibicao}</td>
                  <td class="flex gap-2">
                    <button class="btn btn-sm btn-outline btn-primary btn-edit"
                            data-id="${cat.id_categoria}">
                      <i class="fa fa-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline btn-error btn-del"
                            data-id="${cat.id_categoria}">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
            `);
        });
        renderPagination();
    }

    function buildModalCategoriasRelacionadas(selecionadas = []) {
        const $wrap = $('#cf-categorias-relacionadas').empty();
        ALL.forEach(c => {
            const checked = selecionadas.includes(c.id_categoria) ? 'checked' : '';
            $wrap.append(`
          <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="categorias_relacionadas[]"
                   value="${c.id_categoria}"
                   ${checked} />
            <span>${c.nome_categoria}</span>
          </label>
        `);
        });
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

    // constrói subcategorias no modal de categoria
    function buildModalSubcats(selecionadas = []) {
        const $wrap = $('#cf-subcategorias').empty();
        SUBCATS.forEach(s => {
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

    // constrói tipos de adicionais no modal de categoria
    function buildModalTipoAdicionais(selecionados = []) {
        const $wrap = $('#cf-tipo-adicionais').empty();
        TIPOADDS.forEach(t => {
            const checked = selecionados.includes(t.id_tipo_adicional) ? 'checked' : '';
            $wrap.append(`
              <label class="flex items-center gap-2">
                <input type="checkbox"
                       name="tipo_adicionais[]"
                       value="${t.id_tipo_adicional}"
                       ${checked} />
                <span>${t.nome_tipo_adicional}</span>
              </label>
            `);
        });
    }

    // render lista de subcategorias como botões com editar/excluir
    function renderSubcatList() {
        const $cont = $('#subcat-list').empty();
        if (!SUBCATS.length) {
            $cont.html('<p class="italic">Nenhuma subcategoria cadastrada.</p>');
            return;
        }

        let html = `
          <div class="overflow-x-auto">
            <table class="table w-full">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th style="width:120px">Ações</th>
                </tr>
              </thead>
              <tbody>
        `;

        SUBCATS.forEach(s => {
            html += `
            <tr>
              <td>${s.nome_subcategoria}</td>
              <td class="flex gap-2">
                <button class="subcat-edit btn btn-xs btn-ghost" data-id="${s.id_subcategoria}">
                  <i class="fa fa-pencil"></i>
                </button>
                <button class="subcat-del btn btn-xs btn-ghost" data-id="${s.id_subcategoria}">
                  <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
          `;
        });

        html += `
              </tbody>
            </table>
          </div>
        `;

        $cont.html(html);
    }


    // abre modal de categoria
    function openForm(cat = {}) {
        $('#modal-category-title').text(cat.id_categoria ? 'Editar Categoria' : 'Nova Categoria');
        $('#category-form')[0].reset();
        $('#cf-id').val(cat.id_categoria || '');
        $('#cf-nome').val(cat.nome_categoria || '');
        $('#cf-tem-qtd').val(cat.tem_qtd != null ? String(cat.tem_qtd) : '1');
        $('#cf-ativa').val(cat.categoria_ativa != null ? String(cat.categoria_ativa) : '1');
        $('#cf-ordem').val(cat.ordem_exibicao != null ? cat.ordem_exibicao : 0);
        buildModalSubcats(cat.subcategorias || []);
        buildModalTipoAdicionais(cat.tipo_adicionais || []);
        buildModalCategoriasRelacionadas(cat.categorias_relacionadas || []);

        $('#modal-category').prop('checked', true);
    }

    // inicialização de lista de subcats
    function openSubcatModal() {
        renderSubcatList();
        $('#modal-subcats').prop('checked', true);
    }

    // document ready
    $(document)
        // novo categoria
        .on('click', '#btn-new-category', () => openForm())

        // editar categoria
        .on('click', '.btn-edit', function () {
            $.getJSON(`crud/crud_categoria.php?id=${$(this).data('id')}`, res => {
                if (res.status === 'ok') openForm(res.categoria);
                else Swal.fire('Erro', res.mensagem, 'error');
            });
        })

        // deletar categoria
        .on('click', '.btn-del', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Desativar categoria?', icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, desativar', cancelButtonText: 'Cancelar'
            }).then(({ isConfirmed }) => {
                if (!isConfirmed) return;
                $.ajax({
                    url: 'crud/crud_categoria.php',
                    method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', id_categoria: id })
                }).done(res => {
                    if (res.status === 'ok') {
                        Swal.fire('Desativada', '', 'success');
                        location.reload();
                    } else Swal.fire('Erro', res.mensagem, 'error');
                });
            });
        })

        // paginação
        .on('click', '#pagination button', function () {
            page = +$(this).data('page'); renderTable();
        })

        // salvar categoria
        .on('submit', '#category-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);
            data.subcategorias = $('#cf-subcategorias input:checked')
                .map((_, cb) => +cb.value).get();
            data.tipo_adicionais = $('#cf-tipo-adicionais input:checked')
                .map((_, cb) => +cb.value).get();
            data.categorias_relacionadas = $('#cf-categorias-relacionadas input:checked')
                .map((_, cb) => +cb.value).get();

            $.ajax({
                url: 'crud/crud_categoria.php',
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify(data)
            }).done(res => {
                if (res.status === 'ok') {
                    Swal.fire('Sucesso', 'Categoria salva!', 'success');
                    $('#modal-category').prop('checked', false);
                    location.reload();
                } else Swal.fire('Erro', res.mensagem, 'error');
            });
        })

        // abrir modal de subcategorias
        .on('click', '#btn-manage-subcats', openSubcatModal)

        // adicionar subcategoria
        .on('submit', '#subcat-form', function (e) {
            e.preventDefault();
            const nome = $('#sc-nome').val().trim();
            if (!nome) return;
            $.ajax({
                url: 'crud/crud_subcategoria.php',
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ nome_subcategoria: nome })
            }).done(res => {
                if (res.status === 'ok') {
                    SUBCATS.push(res.subcategoria);
                    $('#sc-nome').val('');
                    renderSubcatList();
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            });
        })

        // deletar subcategoria
        .on('click', '.subcat-del', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Excluir subcategoria?', icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar'
            }).then(({ isConfirmed }) => {
                if (!isConfirmed) return;
                $.ajax({
                    url: 'crud/crud_subcategoria.php',
                    method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', id_subcategoria: id })
                }).done(res => {
                    if (res.status === 'ok') {
                        SUBCATS = SUBCATS.filter(s => s.id_subcategoria !== id);
                        renderSubcatList();
                    } else Swal.fire('Erro', res.mensagem, 'error');
                });
            });
        })

        // editar subcategoria
        .on('click', '.subcat-edit', function () {
            const id = $(this).data('id');
            const sc = SUBCATS.find(s => s.id_subcategoria === id);
            Swal.fire({
                title: 'Editar Subcategoria',
                input: 'text',
                inputValue: sc.nome_subcategoria,
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar'
            }).then(res => {
                if (!res.isConfirmed) return;
                const novo = res.value.trim();
                if (!novo) return Swal.fire('Erro', 'Nome não pode ficar vazio', 'error');
                $.ajax({
                    url: 'crud/crud_subcategoria.php',
                    method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ id_subcategoria: id, nome_subcategoria: novo })
                }).done(resp => {
                    if (resp.status === 'ok') {
                        sc.nome_subcategoria = novo;
                        renderSubcatList();
                    } else Swal.fire('Erro', resp.mensagem, 'error');
                });
            });
        });

    // init
    renderTable();
})(jQuery);
