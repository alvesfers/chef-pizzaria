(function ($) {
    const STATUS_LABEL = {
        pendente: 'Pendente',
        aceito: 'Aceito',
        em_preparo: 'Em Preparo',
        em_entrega: 'Em Entrega',
        finalizado: 'Finalizado',
        cancelado: 'Cancelado'
    };

    const STATUS_COLOR = {
        pendente: 'border-red-400',
        aceito: 'border-yellow-400',
        em_preparo: 'border-blue-400',
        em_entrega: 'border-cyan-400',
        finalizado: 'border-green-400',
        cancelado: 'border-gray-400'
    };

    const entregadores = window.__entregadores__ || [];
    let pedidosAnteriores = new Set();
    const notificationAudio = document.getElementById('notification-sound');

    function renderCard(p) {
        const phone = (p.telefone_cliente || '').replace(/\D/g, '');
        let statusOpts = '', delivOpts = `<option value="">— Selecione —</option>`;
        for (let k in STATUS_LABEL) {
            statusOpts += `<option value="${k}" ${p.status_pedido === k ? 'selected' : ''}>${STATUS_LABEL[k]}</option>`;
        }
        entregadores.forEach(e => {
            delivOpts += `<option value="${e.id_funcionario}" ${p.id_entregador == e.id_funcionario ? 'selected' : ''}>${e.nome}</option>`;
        });

        const borderColor = STATUS_COLOR[p.status_pedido] || 'border-gray-200';

        return `
            <div class="card bg-white shadow p-4 border-l-4 ${borderColor}" data-id="${p.id_pedido}" data-phone="${phone}">
                <h3 class="font-bold">Pedido #${p.id_pedido}</h3>
                <p><strong>Cliente:</strong> ${p.cliente}</p>
                <p><strong>Total:</strong> R$ ${parseFloat(p.valor_total).toFixed(2)}</p>
                <p><strong>Criado em:</strong> ${new Date(p.criado_em).toLocaleString('pt-BR')}</p>
                <div class="mt-3">
                    <label>Status:</label>
                    <select class="select select-bordered status-select">${statusOpts}</select>
                    <div class="flex justify-center items-center gap-2 mt-2">
                        <button class="btn btn-success btn-whatsapp"><i class="fab fa-whatsapp text-xl"></i></button>
                        <button class="btn btn-primary btn-print"><i class="fa-solid fa-print text-xl"></i></button>
                        <button class="btn btn-warning btn-pedido"><i class="fa-solid fa-circle-info text-xl"></i></button>
                    </div>
                </div>
            </div>`;
    }

    function fetchAndRender() {
        $('#loading-overlay').removeClass('hidden');

        $.post('crud/crud_atendimento.php', JSON.stringify({ action: 'get_pedidos' }), function (data) {
            $('#loading-overlay').addClass('hidden');

            if (!Array.isArray(data)) {
                console.error('Resposta inválida:', data);
                return;
            }

            const pedidosAtuais = new Set(data.map(p => p.id_pedido));
            let houveNovo = false;

            for (let id of pedidosAtuais) {
                if (!pedidosAnteriores.has(id)) {
                    houveNovo = true;
                    break;
                }
            }

            Object.keys(STATUS_LABEL).forEach(s => {
                const container = $(`#orders-${s}`);
                container.empty();
            });

            data.forEach(p => {
                $(`#orders-${p.status_pedido}`).append(renderCard(p));
            });

            Object.keys(STATUS_LABEL).forEach(s => {
                const container = $(`#orders-${s}`);
                if (container.children().length === 0) {
                    container.append(`<p class="text-sm text-gray-500 italic">Nenhum pedido ${STATUS_LABEL[s].toLowerCase()}.</p>`);
                }
            });

            if (houveNovo && notificationAudio) {
                Swal.fire({
                    title: 'Novo pedido recebido!',
                    text: 'Verifique a lista de pedidos pendentes.',
                    icon: 'info',
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: () => {
                        try {
                            notificationAudio.currentTime = 0;
                            notificationAudio.play();
                        } catch (e) {
                            console.warn('Erro ao tentar tocar o som de notificação:', e);
                        }
                    }
                });
            }


            pedidosAnteriores = pedidosAtuais;

        }, 'json');
    }

    function updateStatus(id, novo, phone) {
        $.post('crud/crud_pedido.php', JSON.stringify({
            action: 'atualizar_status',
            id_pedido: id,
            status_pedido: novo
        }), () => {
            if (window.LOJA.teste != 1) {
                Swal.fire({
                    title: 'Enviar WhatsApp?',
                    text: `Deseja informar o cliente que o pedido está "${STATUS_LABEL[novo]}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, enviar',
                    cancelButtonText: 'Não enviar'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        fetchAndRender();
                        return;
                    }

                    if (novo === 'aceito') {
                        $.post('crud/crud_pedido.php', JSON.stringify({
                            action: 'get_pedido',
                            id_pedido: id
                        }), function (res) {
                            if (res.status !== 'ok') {
                                Swal.fire('Erro', 'Não foi possível carregar os dados do pedido.', 'error');
                                return;
                            }

                            const pedido = res.pedido;
                            const itens = res.itens;
                            const nomeLoja = window.__nomeLoja__ || 'Nossa Pizzaria';
                            const enderecoLoja = window.__enderecoLoja__ || '';
                            const formasPgto = window.__formasPagamento__ || [];

                            const tipoEntrega = pedido.tipo_entrega;
                            const nomeCliente = pedido.cliente || '';
                            const valorTotal = parseFloat(pedido.valor_total).toFixed(2);
                            const enderecoEntrega = pedido.endereco || '';
                            const formaPgtoId = pedido.forma_pagamento;
                            const formaPgtoNome = formasPgto.find(f => f.id_forma == formaPgtoId)?.nome_pgto || '';

                            let mensagem = `Olá ${nomeCliente}, aqui é da ${nomeLoja}.\n`;
                            mensagem += `Seu pedido #${id} foi *${STATUS_LABEL[novo]}*.\n\n`;

                            mensagem += `Itens do Pedido:\n`;
                            itens.forEach(item => {
                                mensagem += `• ${item.quantidade}x ${item.nome_exibicao}`;
                                if (item.sabores?.length) {
                                    mensagem += ` (${item.sabores.join(', ')})`;
                                }
                                mensagem += ` — R$ ${parseFloat(item.valor_unitario).toFixed(2)}\n`;

                                if (item.adicionais?.length) {
                                    mensagem += `  + Adicionais: `;
                                    mensagem += item.adicionais.map(a => a.nome_adicional).join(', ') + `\n`;
                                }
                            });

                            mensagem += `\nTotal: R$ ${valorTotal}\n`;

                            if (formaPgtoNome) {
                                mensagem += `Forma de pagamento: ${formaPgtoNome}\n`;
                            }

                            if (tipoEntrega === 'entrega') {
                                mensagem += `\nEndereço de entrega: ${enderecoEntrega}\n`;
                                mensagem += `Seu pedido será entregue em até 45 minutos.`;
                            } else {
                                mensagem += `Seu pedido estará disponível para retirada em até 25 minutos.`;
                                if (enderecoLoja) {
                                    mensagem += `\nEndereço da loja: ${enderecoLoja}`;
                                }
                            }

                            mensagem += `\n\nQualquer dúvida, estamos à disposição.`;
                            if (window.LOJA.teste != 1) {
                                window.open(`https://wa.me/55${phone}`, '_blank');
                            } else {
                                alert('Ambiente de teste ativo: não fara o envio WhatsApp.');
                            }
                            fetchAndRender();
                        }, 'json');
                    } else {
                        const nomeLoja = window.__nomeLoja__ || 'Nossa Pizzaria';
                        let mensagem = `Olá! Aqui é da ${nomeLoja}.\n`;
                        mensagem += `Seu pedido #${id} agora está *${STATUS_LABEL[novo]}*.\n`;
                        mensagem += `Agradecemos por comprar com a gente!`;
                        if (window.LOJA.teste != 1) {
                            window.open(`https://wa.me/55${phone}`, '_blank');
                        } else {
                            alert('Ambiente de teste ativo: não fara o envio WhatsApp.');
                        }
                        fetchAndRender();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Pedido feito!',
                    text: `Envio via whatsApp desabilitada!`,
                    icon: 'question'
                })
            }

        }, 'json');
    }

    $(document)
        .on('change', '.status-select', function () {
            const card = $(this).closest('.card'),
                id = card.data('id'),
                phone = card.data('phone'),
                novo = $(this).val();
            updateStatus(id, novo, phone);
        })

        .on('click', '.btn-whatsapp', function () {
            const phone = $(this).closest('.card').data('phone');
            if (!phone) {
                Swal.fire('Erro', 'Telefone não encontrado.', 'error');
                return;
            }
            if (window.LOJA.teste != 1) {
                window.open(`https://wa.me/55${phone}`, '_blank');
            } else {
                alert('Ambiente de teste ativo: não fara o envio WhatsApp.');
            }
        })

        .on('click', '.btn-print', function () {
            const id = $(this).closest('.card').data('id');
            window.open(`comprovante_pedido.php?id=${id}`, '_blank');
        })

        .on('click', '.btn-pedido', function () {
            const id = $(this).closest('.card').data('id');
            $.post('crud/crud_pedido.php', JSON.stringify({
                action: 'get_pedido',
                id_pedido: id
            }), function (res) {
                if (res.status === 'ok') {
                    $('#order-detail-id').text(res.pedido.id_pedido);
                    let html = '';
                    res.itens.forEach(item => {
                        html += `<div class="border-b pb-2 mb-2">
                            <p><strong>${item.nome_exibicao}</strong> — ${item.quantidade}x R$ ${parseFloat(item.valor_unitario).toFixed(2)}</p>`;
                        if (item.sabores?.length) {
                            html += `<p class="text-sm text-gray-500 ml-2">Sabores: ${item.sabores.join(', ')}</p>`;
                        }
                        if (item.adicionais?.length) {
                            html += `<p class="text-sm text-gray-500 ml-2">Adicionais: ${item.adicionais.map(a => a.nome_adicional).join(', ')}</p>`;
                        }
                        html += `</div>`;
                    });

                    $('#order-detail-content').html(html);
                    $('#modal-order-detail').prop('checked', true);
                } else {
                    Swal.fire('Erro', res.mensagem || 'Não foi possível carregar o pedido.', 'error');
                }
            }, 'json');
        });

    $(function () {
        fetchAndRender();
        setInterval(fetchAndRender, 15000);
    });
})(jQuery);
