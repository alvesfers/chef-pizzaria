// panelAtendimento.js
(function ($) {
    const STATUS_LABEL = { pendente: 'Pendente', aceito: 'Aceito', em_preparo: 'Em Preparo', em_entrega: 'Em Entrega', finalizado: 'Finalizado', cancelado: 'Cancelado' };
    const entregadores = window.__entregadores__; // injetado no PHP como JSON
    function renderCard(p) {
        const phone = (p.telefone_cliente || '').replace(/\D/g, '');
        let statusOpts = '',
            delivOpts = `<option value="">— Selecione —</option>`;
        for (let k in STATUS_LABEL) {
            statusOpts += `<option value="${k}" ${p.status_pedido === k ? 'selected' : ''}>${STATUS_LABEL[k]}</option>`;
        }
        entregadores.forEach(e => {
            delivOpts += `<option value="${e.id_funcionario}" ${p.id_entregador == e.id_funcionario ? 'selected' : ''}>${e.nome}</option>`;
        });
        return `
          <div class="card bg-white shadow p-4" data-id="${p.id_pedido}" data-phone="${phone}">
            <h3 class="font-bold">Pedido #${p.id_pedido}</h3>
            <p><strong>Cliente:</strong> ${p.cliente}</p>
            <p><strong>Total:</strong> R$ ${parseFloat(p.valor_total).toFixed(2)}</p>
            <p><strong>Criado em:</strong> ${new Date(p.criado_em).toLocaleString('pt-BR')}</p>
            <div class="mt-3">
              <label>Status:</label>
              <select class="select select-bordered status-select">${statusOpts}</select>
              <label>Entregador:</label>
              <select class="select select-bordered deliverer-select">${delivOpts}</select>
              <div class="flex justify-center items-center gap-2 mt-2">
                <button class="btn btn-success btn-whatsapp"><i class="fab fa-whatsapp text-xl"></i></button>
                <button class="btn btn-primary btn-print"><i class="fa-solid fa-print text-xl"></i></button>
                <button class="btn btn-warning btn-pedido"><i class="fa-solid fa-circle-info text-xl"></i></button>
              </div>
            </div>
          </div>`;
    }

    function fetchAndRender() {
        $.post('crud/crud_pedido.php', JSON.stringify({ action: 'get_pendentes' }), data => {
            Object.keys(STATUS_LABEL).forEach(s => $(`#orders-${s}`).empty());
            data.forEach(p => $(`#orders-${p.status_pedido}`).append(renderCard(p)));
        }, 'json');
    }
    function updateStatus(id, novo, phone) {
        $.post('crud/crud_pedido.php', JSON.stringify({ action: 'atualizar_status', id_pedido: id, status_pedido: novo }), () => {
            if (confirm('Enviar WhatsApp?')) window.open(`https://wa.me/55${phone}?text=Seu pedido está "${STATUS_LABEL[novo]}"`);
            fetchAndRender();
        }, 'json');
    }
    // delegação
    $(document)
        .on('change', '.status-select', function () {
            const card = $(this).closest('.card'), id = card.data('id'), phone = card.data('phone'), novo = $(this).val();
            updateStatus(id, novo, phone);
        })
        .on('change', '.deliverer-select', function () {
            const card = $(this).closest('.card');
            $.post('crud/crud_pedido.php', JSON.stringify({ action: 'atribuir_entregador', id_pedido: card.data('id'), id_entregador: $(this).val() }), fetchAndRender, 'json');
        })
        .on('click', '.btn-whatsapp', function () {
            const card = $(this).closest('.card'), id = card.data('id'), phone = card.data('phone'), status = card.find('.status-select').val();
            window.open(`https://wa.me/55${phone}?text=Pedido #${id} está "${STATUS_LABEL[status]}"`);
        })
        // dentro do seu IIFE, junto com os outros .on(...)
        .on('click', '.btn-print', function () {
            const $card = $(this).closest('.card');
            const id = $card.data('id');
            // busca os dados
            $.post('crud/crud_pedido.php',
                JSON.stringify({ action: 'get_pedido', id_pedido: id }),
                function (res) {
                    if (res.status !== 'ok') {
                        return alert(res.mensagem);
                    }
                    const p = res.pedido, itens = res.itens;
                    // abre janela de impressão
                    const w = window.open('', '_blank', 'width=300,height=500');
                    w.document.write(`
                        <html>
                            <head>
                                <title>Pedido #${p.id_pedido}</title>
                                <style>
                                    body { font-family: monospace; white-space: pre; }
                                    .center { text-align: center; }
                                </style>
                            </head>
                            <body>
                                <div class="center">
                                    <strong>Sua Loja</strong>\n
                                    Pedido #${p.id_pedido}\n
                                    ${new Date(p.criado_em).toLocaleString('pt-BR')}\n
                                ------------------------------\n
                                </div>
                    `);
                    itens.forEach(it => {
                        const unit = parseFloat(it.valor_unitario).toFixed(2);
                        w.document.write(`${it.quantidade}× ${it.nome_exibicao}  R$${unit}\n`);
                        if (it.sabores.length) {
                            w.document.write(`  Sabores: ${it.sabores.join(', ')}\n`);
                        }
                        it.adicionais.forEach(a => {
                            const addVal = parseFloat(a.valor_adicional).toFixed(2);
                            w.document.write(`  + ${a.nome_adicional}  R$${addVal}\n`);
                        });
                    });
                    w.document.write(`------------------------------\nTotal: R$${parseFloat(p.valor_total).toFixed(2)}\n\nObrigado!\n`);
                    w.document.write('</body></html>');
                    w.document.close();
                    w.print();
                    w.close();
                },
                'json'
            );
        })
        .on('click', '.btn-pedido', function () {
            const $card = $(this).closest('.card');
            const id = $card.data('id');
            // busca e exibe no modal
            $.post('crud/crud_pedido.php',
                JSON.stringify({ action: 'get_pedido', id_pedido: id }),
                function (res) {
                    if (res.status !== 'ok') {
                        return alert(res.mensagem);
                    }
                    const p = res.pedido, itens = res.itens;
                    $('#order-detail-id').text(p.id_pedido);
                    let html = `
                        <p><strong>Cliente:</strong> ${p.cliente}</p>
                        <p><strong>Criado em:</strong> ${new Date(p.criado_em).toLocaleString('pt-BR')}</p>
                        <hr/>
                    `;
                    itens.forEach(it => {
                        html += `<div>${it.quantidade}× ${it.nome_exibicao} — R$${parseFloat(it.valor_unitario).toFixed(2)}</div>`;
                        if (it.sabores.length) {
                            html += `<div class="ml-4 text-sm">Sabores: ${it.sabores.join(', ')}</div>`;
                        }
                        if (it.adicionais.length) {
                            html += `<ul class="ml-4 list-disc list-inside text-sm">`;
                            it.adicionais.forEach(a => {
                                html += `<li>${a.nome_adicional} — R$${parseFloat(a.valor_adicional).toFixed(2)}</li>`;
                            });
                            html += `</ul>`;
                        }
                    });
                    html += `<hr/><p><strong>Total:</strong> R$${parseFloat(p.valor_total).toFixed(2)}</p>`;
                    $('#order-detail-content').html(html);
                    $('#modal-order-detail').prop('checked', true);
                },
                'json'
            );
        });
    $(function () {
        fetchAndRender();
        setInterval(fetchAndRender, 15000);
    });
})(jQuery);
