// assets/js/adicionais.js
(function ($) {
    const TIPOS = window.__tipoAdicionais__ || [];
    let ADDS = window.__adicionais__ || [];

    // render lista de tipos na tabela
    function renderTipos() {
        const $body = $('#tipo-table-body').empty();
        TIPOS.forEach(t => {
            $body.append(`
                <tr>
                  <td>${t.nome_tipo_adicional}</td>
                  <td>${t.obrigatorio == 1 ? 'Sim' : 'Não'}</td>
                  <td>${t.multipla_escolha == 1 ? 'Sim' : 'Não'}</td>
                  <td>${t.max_escolha}</td>
                  <td>${t.tipo_ativo == 1 ? 'Sim' : 'Não'}</td>
                  <td class="flex gap-2">
                    <button class="btn btn-sm btn-outline btn-primary btn-edit-tipo"
                            data-id="${t.id_tipo_adicional}">
                      <i class="fa fa-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline btn-error btn-del-tipo"
                            data-id="${t.id_tipo_adicional}">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
            `);
        });
    }

    // abre modal de tipo, preenchendo dados
    function openTipoForm(tipo = {}) {
        $('#modal-tipo-title').text(tipo.id_tipo_adicional ? 'Editar Tipo' : 'Novo Tipo');
        $('#tipo-form')[0].reset();
        $('#tf-id').val(tipo.id_tipo_adicional || '');
        $('#tf-nome').val(tipo.nome_tipo_adicional || '');
        $('#tf-obrig').val(tipo.obrigatorio != null ? tipo.obrigatorio : '0');
        $('#tf-multi').val(tipo.multipla_escolha != null ? tipo.multipla_escolha : '0');
        $('#tf-max').val(tipo.max_escolha || 0);
        $('#tf-ativo').val(tipo.tipo_ativo != null ? tipo.tipo_ativo : '1');
        renderAdicionaisList(tipo.id_tipo_adicional);
        $('#modal-tipo').prop('checked', true);
    }

    // render lista de adicionais para um tipo dentro do modal
    function renderAdicionaisList(tipoId) {
        const $cont = $('#tf-adicionais-list').empty();
        const list = ADDS.filter(a => a.id_tipo_adicional == tipoId);

        if (list.length === 0) {
            $cont.html('<p class="italic">Nenhum adicional cadastrado.</p>');
            return;
        }

        let html = `
          <table class="table w-full">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Valor (R$)</th>
                <th style="width:120px">Ações</th>
              </tr>
            </thead>
            <tbody>
        `;

        list.forEach(a => {
            html += `
            <tr>
              <td>${a.nome_adicional}</td>
              <td>${parseFloat(a.valor_adicional).toFixed(2)}</td>
              <td class="flex gap-2">
                <button class="btn btn-xs btn-ghost btn-edit-ad" data-id="${a.id_adicional}">
                  <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-xs btn-ghost btn-del-ad" data-id="${a.id_adicional}">
                  <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
          `;
        });

        html += `
            </tbody>
          </table>
        `;

        $cont.html(html);
    }


    // abre modal de adicional, populando tipo
    function openAdForm(ad = {}, tipoId) {
        $('#modal-adicional-title').text(ad.id_adicional ? 'Editar Adicional' : 'Novo Adicional');
        $('#adicional-form')[0].reset();
        $('#af-id').val(ad.id_adicional || '');
        $('#af-tipo').val(ad.id_tipo_adicional || tipoId);
        $('#af-nome').val(ad.nome_adicional || '');
        $('#af-valor').val(ad.valor_adicional || 0);
        $('#af-ativo').val(ad.adicional_ativo != null ? ad.adicional_ativo : '1');
        $('#modal-adicional').prop('checked', true);
    }

    // handlers
    $(document)
        // novo tipo
        .on('click', '#btn-new-tipo', () => openTipoForm())

        // editar tipo
        .on('click', '.btn-edit-tipo', function () {
            const id = $(this).data('id');
            $.getJSON(`crud/crud_tipo_adicional.php?id=${id}`, res => {
                if (res.status === 'ok') openTipoForm(res.tipoAdicional);
                else Swal.fire('Erro', res.mensagem, 'error');
            });
        })

        // excluir tipo
        .on('click', '.btn-del-tipo', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Excluir tipo?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Não'
            }).then(({ isConfirmed }) => {
                if (!isConfirmed) return;
                $.ajax({
                    url: 'crud/crud_tipo_adicional.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', id_tipo_adicional: id })
                }).done(res => {
                    if (res.status === 'ok') {
                        const idx = TIPOS.findIndex(t => t.id_tipo_adicional === id);
                        TIPOS.splice(idx, 1);
                        renderTipos();
                    } else Swal.fire('Erro', res.mensagem, 'error');
                });
            });
        })

        // salvar tipo
        .on('submit', '#tipo-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);
            $.ajax({
                url: 'crud/crud_tipo_adicional.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            }).done(res => {
                if (res.status === 'ok') {
                    const t = res.tipoAdicional;
                    const idx = TIPOS.findIndex(x => x.id_tipo_adicional === t.id_tipo_adicional);
                    if (idx >= 0) TIPOS[idx] = t;
                    else TIPOS.push(t);
                    renderTipos();
                    $('#modal-tipo').prop('checked', false);
                } else Swal.fire('Erro', res.mensagem, 'error');
            });
        })

        // novo adicional
        .on('click', '#btn-new-adicional', function () {
            const tipoId = $('#tf-id').val();
            if (!tipoId) return Swal.fire('Erro', 'Salve primeiro o tipo', 'error');
            openAdForm({}, Number(tipoId));
        })

        // editar adicional
        .on('click', '.btn-edit-ad', function () {
            const id = $(this).data('id');
            $.getJSON(`crud/crud_adicional.php?id=${id}`, res => {
                if (res.status === 'ok') openAdForm(res.adicional);
                else Swal.fire('Erro', res.mensagem, 'error');
            });
        })

        // excluir adicional
        .on('click', '.btn-del-ad', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Excluir adicional?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Não'
            }).then(({ isConfirmed }) => {
                if (!isConfirmed) return;
                $.ajax({
                    url: 'crud/crud_adicional.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', id_adicional: id })
                }).done(res => {
                    if (res.status === 'ok') {
                        ADDS = ADDS.filter(a => a.id_adicional !== id);
                        renderAdicionaisList($('#tf-id').val());
                    } else Swal.fire('Erro', res.mensagem, 'error');
                });
            });
        })

        // salvar adicional
        .on('submit', '#adicional-form', function (e) {
            e.preventDefault();
            const data = {};
            $(this).serializeArray().forEach(x => data[x.name] = x.value);
            $.ajax({
                url: 'crud/crud_adicional.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            }).done(res => {
                if (res.status === 'ok') {
                    const a = res.adicional;
                    const idx = ADDS.findIndex(x => x.id_adicional === a.id_adicional);
                    if (idx >= 0) ADDS[idx] = a;
                    else ADDS.push(a);
                    renderAdicionaisList($('#tf-id').val());
                    $('#modal-adicional').prop('checked', false);
                } else Swal.fire('Erro', res.mensagem, 'error');
            });
        });

    // inicial
    renderTipos();
})(jQuery);
