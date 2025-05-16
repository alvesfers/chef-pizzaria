$(document).ready(function () {
    carregarDadosLoja();

    $('#btnSalvarLoja').on('click', function () {
        const dados = $('#formDadosLoja').serializeArray();
        const payload = {};
        dados.forEach(d => {
            if (d.name === 'usar_horarios') {
                payload[d.name] = 1;
            } else {
                payload[d.name] = d.value.trim();
            }
        });
        if (!payload.usar_horarios) payload.usar_horarios = 0;

        $.ajax({
            url: 'crud/crud_loja.php',
            method: 'POST',
            data: JSON.stringify({
                action: 'salvar_dados',
                dados: payload
            }),
            dataType: 'json',
            success: function (res) {
                Swal.fire({
                    title: res.status === 'ok' ? 'Sucesso' : 'Erro',
                    text: res.mensagem,
                    icon: res.status === 'ok' ? 'success' : 'error'
                });
            }
        });
    });


    $('#btnSalvarHorarios').on('click', function () {
        const horarios = [];
        $('#tabelaHorarios tr').each(function () {
            const linha = $(this);
            horarios.push({
                id_horario: linha.data('id'),
                dia_semana: linha.find('.dia').text(),
                hora_abertura: linha.find('.hora_abertura').val(),
                hora_fechamento: linha.find('.hora_fechamento').val(),
                ativo: linha.find('.ativo').is(':checked') ? 1 : 0
            });
        });

        $.post('crud/crud_loja.php', JSON.stringify({
            action: 'salvar_horarios',
            horarios
        }), function (res) {
            Swal.fire(res.status === 'ok' ? 'Salvo!' : 'Erro', res.mensagem, res.status);
        }, 'json');
    });

    $('#btnSalvarRegras').on('click', function () {
        const regras = [];
        $('#regrasFrete .card').each(function () {
            const card = $(this);
            regras.push({
                id_regra: card.find('input[name="id_regra[]"]').val(),
                nome_regra: card.find('input[name="nome_regra[]"]').val(),
                tipo_regra: card.find('select[name="tipo_regra[]"]').val(),
                valor_minimo: card.find('input[name="valor_minimo[]"]').val(),
                distancia_maxima: card.find('input[name="distancia_maxima[]"]').val(),
                valor_desconto: card.find('input[name="valor_desconto[]"]').val(),
                dia_semana: card.find('select[name="dia_semana[]"]').val(),
                ativo: card.find('input[name="ativo[]"]').is(':checked') ? 1 : 0
            });
        });

        $.post('crud/crud_loja.php', JSON.stringify({
            action: 'salvar_regras',
            regras
        }), function (res) {
            Swal.fire(res.status === 'ok' ? 'Salvo!' : 'Erro', res.mensagem, res.status);
        }, 'json');
    });

    $('#btnNovaRegra').on('click', function () {
        $('#regrasFrete').append(renderRegraFrete({}));
    });

    $('#btnEditarRegras').on('click', function () {
        $('#modal-regras-frete').prop('checked', true);
    });

    $(document).on('click', '.btn-remover-regra', function () {
        $(this).closest('.card').remove();
    });

    $(document).on('change', 'input[name=usar_horarios]', function () {
        const habilitar = $(this).is(':checked');
        $('#tabelaHorarios input, #tabelaHorarios select').prop('disabled', !habilitar);
    });

    $('#logo').on('change', function () {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        fetch('crud/crud_loja.php?action=salvar_logo', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(json => {
            Swal.fire(json.status === 'ok' ? 'Sucesso' : 'Erro', json.mensagem, json.status);
            if (json.status === 'ok') {
                $('#logo-preview').attr('src', URL.createObjectURL(file));
            }
        });
    });
});

function carregarDadosLoja() {
    $.post('crud/crud_loja.php', JSON.stringify({ action: 'get_dados_loja' }), function (res) {
        if (res.status !== 'ok') return;

        for (let campo in res.dados_loja) {
            const el = $(`[name="${campo}"]`);
            if (el.is(':checkbox')) {
                el.prop('checked', res.dados_loja[campo] == 1);
            } else {
                el.val(res.dados_loja[campo]);
            }
        }

        if (res.dados_loja.logo) {
            $('#logo-preview').attr('src', 'assets/images/logo?' + Date.now());
        }

        const usarHorarios = res.dados_loja.usar_horarios == 1;
        $('#tabelaHorarios').html('');
        res.horarios.forEach(h => {
            $('#tabelaHorarios').append(`
                <tr data-id="${h.id_horario}">
                    <td class="dia">${h.dia_semana}</td>
                    <td><input type="time" class="input input-bordered hora_abertura" value="${h.hora_abertura}" ${!usarHorarios ? 'disabled' : ''}></td>
                    <td><input type="time" class="input input-bordered hora_fechamento" value="${h.hora_fechamento}" ${!usarHorarios ? 'disabled' : ''}></td>
                    <td><input type="checkbox" class="toggle ativo" ${h.ativo == 1 ? 'checked' : ''} ${!usarHorarios ? 'disabled' : ''}></td>
                </tr>
            `);
        });

        $('#regrasFrete').html('');
        res.regras_frete.forEach(r => {
            $('#regrasFrete').append(renderRegraFrete(r));
        });
    }, 'json');
}

function renderRegraFrete(regra) {
    return `
    <div class="card bg-base-100 p-4 shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="id_regra[]" value="${regra.id_regra || ''}">
            <input type="text" class="input input-bordered" placeholder="Nome da Regra" name="nome_regra[]" value="${regra.nome_regra || ''}">
            <select name="tipo_regra[]" class="select select-bordered">
                <option value="percentual" ${regra.tipo_regra === 'percentual' ? 'selected' : ''}>% Desconto</option>
                <option value="fixo" ${regra.tipo_regra === 'fixo' ? 'selected' : ''}>Desconto Fixo</option>
            </select>
            <input type="number" step="0.01" class="input input-bordered" placeholder="Desconto" name="valor_desconto[]" value="${regra.valor_desconto || ''}">
            <input type="number" step="0.01" class="input input-bordered" placeholder="Valor mínimo" name="valor_minimo[]" value="${regra.valor_minimo || ''}">
            <input type="number" step="0.1" class="input input-bordered" placeholder="Distância máxima" name="distancia_maxima[]" value="${regra.distancia_maxima || ''}">
            <select name="dia_semana[]" class="select select-bordered">
                <option value="">Todos os dias</option>
                ${['segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'].map(d => `
                    <option value="${d}" ${regra.dia_semana === d ? 'selected' : ''}>${d.charAt(0).toUpperCase() + d.slice(1)}</option>
                `).join('')}
            </select>
            <label class="label cursor-pointer">
                <span class="label-text">Ativa</span>
                <input type="checkbox" name="ativo[]" class="toggle" ${regra.ativo == 1 ? 'checked' : ''}>
            </label>
            <div class="flex items-center">
                <button type="button" class="btn btn-error btn-sm btn-remover-regra"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    `;
}

$(document).on('click', '[data-set-theme]', function () {
    const tema = $(this).data('set-theme');

    $.post('crud/crud_loja.php', { action: 'salvar_tema', tema }, function (res) {
        if (res.status === 'ok') {
            Swal.fire('Tema atualizado!', '', 'success');
            // Reaplica o tema na interface atual (opcional)
            document.documentElement.setAttribute('data-theme', tema);
        } else {
            Swal.fire('Erro ao salvar tema.', res.mensagem || '', 'error');
        }
    }, 'json');
});

