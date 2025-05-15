(function ($) {
    const STATUS_LABEL = {
        pendente: 'Pendente',
        aceito: 'Aceito',
        em_preparo: 'Em Preparo',
        em_entrega: 'Em Entrega',
        finalizado: 'Finalizado',
        cancelado: 'Cancelado'
    };

    const entregadores = window.__entregadores__ || [];

    function renderCard(p) {
        const phone = (p.telefone_cliente || '').replace(/\D/g, '');
        let statusOpts = '', delivOpts = `<option value="">— Selecione —</option>`;
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
        $.post('crud/crud_atendimento.php', JSON.stringify({ action: 'get_pedidos' }), function (data) {
            if (!Array.isArray(data)) {
                console.error('Resposta inválida:', data);
                return;
            }
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

    $(document)
        .on('change', '.status-select', function () {
            const card = $(this).closest('.card'), id = card.data('id'), phone = card.data('phone'), novo = $(this).val();
            updateStatus(id, novo, phone);
        })
        .on('change', '.deliverer-select', function () {
            const card = $(this).closest('.card');
            $.post('crud/crud_pedido.php', JSON.stringify({
                action: 'atribuir_entregador',
                id_pedido: card.data('id'),
                id_entregador: $(this).val()
            }), fetchAndRender, 'json');
        });

    $(function () {
        fetchAndRender();
        setInterval(fetchAndRender, 15000);
    });
})(jQuery);
