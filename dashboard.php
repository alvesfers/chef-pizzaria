<?php
// dashboard.php
require_once 'assets/header.php';
if (!($isAdmin ?? false)) {
    header('Location: index.php');
    exit;
}
?>
<div class="container mx-auto p-6 space-y-6">

    <!-- filtros -->
    <div class="card bg-base-200 p-4 shadow rounded">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block font-medium">Período</label>
                <div class="btn-group">
                    <button id="btnDay" class="btn btn-outline">Diário</button>
                    <button id="btnWeek" class="btn btn-outline">Semanal</button>
                    <button id="btnMonth" class="btn btn-active">Mensal</button>
                </div>
                <input type="hidden" id="filtro_periodo" value="month">
            </div>
            <div>
                <label class="block font-medium">Data inicial</label>
                <input type="date" id="filtro_de" class="input input-bordered" />
            </div>
            <div>
                <label class="block font-medium">Data final</label>
                <input type="date" id="filtro_ate" class="input input-bordered" />
            </div>
            <button id="btnFiltrar" class="btn btn-primary">Filtrar</button>
        </div>
    </div>

    <!-- cartões de resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card bg-primary text-primary-content shadow">
            <div class="card-body">
                <h2 class="card-title">Receita Total</h2>
                <p id="resumo_receita" class="text-3xl">R$ 0,00</p>
            </div>
        </div>
        <div class="card bg-secondary text-secondary-content shadow">
            <div class="card-body">
                <h2 class="card-title">Pedidos</h2>
                <p id="resumo_pedidos" class="text-3xl">0</p>
            </div>
        </div>
        <div class="card bg-accent text-accent-content shadow">
            <div class="card-body">
                <h2 class="card-title">Ticket Médio</h2>
                <p id="resumo_ticket" class="text-3xl">R$ 0,00</p>
            </div>
        </div>
    </div>

    <!-- gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-base-100 p-4 shadow rounded">
            <h3 class="font-bold mb-2">Receita ao longo do período</h3>
            <div class="relative" style="height:300px">
                <canvas id="chartReceita"></canvas>
            </div>
        </div>
        <div class="bg-base-100 p-4 shadow rounded">
            <h3 class="font-bold mb-2">Pedidos por status</h3>
            <div class="flex justify-center" style="height:300px">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
    </div>

</div>

<?php include 'assets/footer.php'; ?>

<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    $(function() {
        // datas padrão (último mês)
        const hoje = new Date().toISOString().slice(0, 10);
        const umMesAtras = new Date();
        umMesAtras.setMonth(umMesAtras.getMonth() - 1);
        $('#filtro_ate').val(hoje);
        $('#filtro_de').val(umMesAtras.toISOString().slice(0, 10));

        // alterna período
        function setPeriodo(p) {
            $('#filtro_periodo').val(p);
            $('#btnDay,#btnWeek,#btnMonth').removeClass('btn-active').addClass('btn-outline');
            if (p === 'day') $('#btnDay').addClass('btn-active').removeClass('btn-outline');
            if (p === 'week') $('#btnWeek').addClass('btn-active').removeClass('btn-outline');
            if (p === 'month') $('#btnMonth').addClass('btn-active').removeClass('btn-outline');
        }
        $('#btnDay').click(() => setPeriodo('day'));
        $('#btnWeek').click(() => setPeriodo('week'));
        $('#btnMonth').click(() => setPeriodo('month'));

        // registra plugin de datalabels
        Chart.register(ChartDataLabels);

        // cria gráfico de linha
        const ctxR = document.getElementById('chartReceita').getContext('2d');
        const chartReceita = new Chart(ctxR, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'R$ Receita',
                    data: [],
                    borderColor: 'rgba(59,130,246,1)',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            callback: function(val) {
                                const label = this.getLabelForValue(val);
                                const d = new Date(label);
                                return isNaN(d) ? label : d.toLocaleDateString('pt-BR');
                            }
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: items => {
                                const d = new Date(items[0].label);
                                return isNaN(d) ? items[0].label : d.toLocaleDateString('pt-BR');
                            },
                            label: ctx => `R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`
                        }
                    }
                }
            }
        });

        // cria doughnut com datalabels
        const ctxS = document.getElementById('chartStatus').getContext('2d');
        const chartStatus = new Chart(ctxS, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const v = ctx.parsed;
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = (v * 100 / total).toFixed(1).replace('.', ',');
                                return `${v} pedidos (${pct}%)`;
                            }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        formatter: (value, ctx) => {
                            const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const pct = (value * 100 / total).toFixed(1).replace('.', ',');
                            return `${value}\n${pct}%`;
                        }
                    }
                }
            }
        });

        const statusColors = {
            pendente: '#f59e0b',
            aceito: '#3b82f6',
            finalizado: '#ef4444',
            em_entrega: '#10b981'
        };

        function atualizarDashboard() {
            const periodo = $('#filtro_periodo').val();
            const de = $('#filtro_de').val();
            const ate = $('#filtro_ate').val();

            // resumo
            $.post('crud/crud_dashboard.php', {
                action: 'resumo',
                de,
                ate
            }, res => {
                if (res.status === 'sucesso') {
                    $('#resumo_receita').text('R$ ' + parseFloat(res.data.total_receita).toFixed(2).replace('.', ','));
                    $('#resumo_pedidos').text(res.data.total_pedidos);
                    $('#resumo_ticket').text('R$ ' + (res.data.ticket_medio || 0).toFixed(2).replace('.', ','));
                }
            }, 'json');

            // série de receita
            $.post('crud/crud_dashboard.php', {
                action: 'serie_receita',
                periodo,
                de,
                ate
            }, res => {
                if (res.status === 'sucesso') {
                    chartReceita.data.labels = res.labels;
                    chartReceita.data.datasets[0].data = res.values;
                    chartReceita.update();
                }
            }, 'json');

            // pedidos por status
            $.post('crud/crud_dashboard.php', {
                action: 'status_pedidos',
                de,
                ate
            }, res => {
                if (res.status === 'sucesso') {
                    const labels = res.labels;
                    const values = res.values;
                    chartStatus.data.labels = labels;
                    chartStatus.data.datasets[0].data = values;
                    chartStatus.data.datasets[0].backgroundColor = labels.map(l => statusColors[l] || '#6b7280');
                    chartStatus.update();
                }
            }, 'json');
        }

        $('#btnFiltrar').click(atualizarDashboard);
        atualizarDashboard();
    });
</script>