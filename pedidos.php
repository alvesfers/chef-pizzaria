<?php
include_once 'assets/header.php';

if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

$formasPgto = $pdo->query("SELECT id_forma, nome_pgto FROM tb_forma_pgto WHERE pagamento_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);
$entregadores = $pdo->query("
    SELECT f.id_funcionario, u.nome_usuario AS nome
    FROM tb_funcionario f
    JOIN tb_usuario u USING(id_usuario)
    WHERE f.ativo = 1
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Pedidos</h1>

    <!-- Filtros -->
    <div class="flex flex-wrap gap-2 mb-4">
        <select id="filtro-status" class="select select-bordered">
            <option value="">Todos Status</option>
            <option value="pendente">Pendente</option>
            <option value="aceito">Aceito</option>
            <option value="em_preparo">Em Preparo</option>
            <option value="em_entrega">Em Entrega</option>
            <option value="finalizado">Finalizado</option>
            <option value="cancelado">Cancelado</option>
        </select>

        <select id="filtro-pagamento" class="select select-bordered">
            <option value="">Todos Pagamentos</option>
            <?php foreach ($formasPgto as $f): ?>
                <option value="<?= $f['id_forma'] ?>"><?= htmlspecialchars($f['nome_pgto']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filtro-entrega" class="select select-bordered">
            <option value="">Todos Tipos</option>
            <option value="retirada">Retirada</option>
            <option value="entrega">Entrega</option>
        </select>

        <input type="date" id="filtro-data" class="input input-bordered" />
        <input type="text" id="filtro-nome" class="input input-bordered" placeholder="Buscar nome/telefone" />
        <button id="btn-buscar" class="btn btn-primary">Filtrar</button>
    </div>

    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Telefone</th>
                    <th>Entrega</th>
                    <th>Status</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-pedidos"></tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function carregarPedidos() {
            const filtros = {
                status: document.getElementById('filtro-status').value,
                pagamento: document.getElementById('filtro-pagamento').value,
                entrega: document.getElementById('filtro-entrega').value,
                nome: document.getElementById('filtro-nome').value,
                data: document.getElementById('filtro-data').value,
                action: 'listar_filtros'
            };

            fetch('crud/crud_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(filtros)
                })
                .then(res => res.json())
                .then(json => {
                    const tbody = document.getElementById('tabela-pedidos');
                    tbody.innerHTML = '';
                    json.pedidos.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                    <td>${p.id_pedido}</td>
                    <td>${p.cliente}</td>
                    <td>${p.telefone_cliente}</td>
                    <td>${p.tipo_entrega}</td>
                    <td>
                        <select data-id="${p.id_pedido}" class="select select-sm select-bordered status-select">
                            ${['pendente','aceito','em_preparo','em_entrega','finalizado','cancelado'].map(s =>
                                `<option value="${s}" ${s === p.status_pedido ? 'selected' : ''}>${s}</option>`
                            ).join('')}
                        </select>
                    </td>
                    <td>${p.forma_pgto}</td>
                    <td>R$ ${parseFloat(p.valor_total).toFixed(2).replace('.', ',')}</td>
                    <td>${new Date(p.criado_em).toLocaleString('pt-BR')}</td>
                    <td class="flex gap-2">
                        <button class="btn btn-xs btn-error btn-cancelar" data-id="${p.id_pedido}">Cancelar</button>
                        <button class="btn btn-xs btn-outline btn-print" data-id="${p.id_pedido}"><i class="fas fa-print"></i></button>
                    </td>
                `;
                        tbody.appendChild(tr);
                    });
                });
        }

        document.getElementById('btn-buscar').addEventListener('click', carregarPedidos);

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('status-select')) {
                const id = e.target.dataset.id;
                const status = e.target.value;
                fetch('crud/crud_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'atualizar_status',
                        id_pedido: id,
                        status_pedido: status
                    })
                }).then(() => carregarPedidos());
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-cancelar')) {
                const id = e.target.dataset.id;
                Swal.fire({
                    title: 'Cancelar Pedido?',
                    text: "Essa ação não poderá ser desfeita!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sim, cancelar',
                    cancelButtonText: 'Fechar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('crud/crud_pedido.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'cancelar',
                                id_pedido: id,
                                motivo: 'Cancelado via painel'
                            })
                        }).then(() => carregarPedidos());
                    }
                });
            }

            if (e.target.closest('.btn-print')) {
                const id = e.target.closest('.btn-print').dataset.id;
                window.open('comprovante_pedido.php?id=' + id, '_blank');
            }
        });

        carregarPedidos();
    });
</script>

<?php include_once 'assets/footer.php'; ?>