<?php
// produto.php
include_once 'assets/header.php';

// Valida ID na URL
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado.</p>";
    include_once 'assets/footer.php';
    exit;
}
$idProduto = (int) $_GET['id'];

// 1) Carrega produto (inclui valor_extra_sabores)
$stmt = $pdo->prepare("
    SELECT *,
           valor_produto         AS base,
           qtd_produto,
           valor_extra_sabores
      FROM tb_produto
     WHERE id_produto   = ?
       AND produto_ativo = 1
");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produto) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado ou inativo.</p>";
    include_once 'assets/footer.php';
    exit;
}

// estoque lógico
$estoque = (int)$produto['qtd_produto'];
$maxQtd  = $estoque === 0
    ? 0
    : ($estoque > 0 ? $estoque : 10);

// promoção do dia
$stmtPromo = $pdo->prepare("
    SELECT valor_promocional
      FROM tb_campanha_produto_dia
     WHERE id_produto = ?
       AND dia_semana = ?
       AND ativo      = 1
");
$stmtPromo->execute([$idProduto, $diaSemana]);
$promo = $stmtPromo->fetchColumn();
$valorBase = $promo !== false
    ? (float)$promo
    : (float)$produto['base'];

// sobretaxa e multi-sabores
$valorExtra  = (float)$produto['valor_extra_sabores'];
$qtdSabores   = (int)$produto['qtd_sabores'];
$tipoCalculo  = $produto['tipo_calculo_preco'];

// sabores (se multi-sabores)
$sabores = [];
if ($qtdSabores > 1) {
    $stmtSabores = $pdo->prepare("
        SELECT p.id_produto,
               p.nome_produto,
               p.valor_produto,
               COALESCE(s.nome_subcategoria,'Sem Categoria') AS nome_subcategoria
          FROM tb_produto p
     LEFT JOIN tb_subcategoria_produto sp USING(id_produto)
     LEFT JOIN tb_subcategoria s           USING(id_subcategoria)
         WHERE p.produto_ativo = 1
           AND p.qtd_sabores  <= 1
           AND p.nome_produto NOT LIKE '%combo%'
           AND p.id_categoria = ?
      ORDER BY s.nome_subcategoria, p.nome_produto
    ");
    $stmtSabores->execute([$produto['id_categoria']]);
    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);
}

// adicionais inclusos por padrão
$stmtIncl = $pdo->prepare("
    SELECT id_adicional
      FROM tb_produto_adicional_incluso
     WHERE id_produto = ?
");
$stmtIncl->execute([$idProduto]);
$adicionaisInclusos = array_map('intval', $stmtIncl->fetchAll(PDO::FETCH_COLUMN));

// tipos de adicionais
$stmtTipos = $pdo->prepare("
    SELECT ta.id_tipo_adicional,
           ta.nome_tipo_adicional,
           pta.obrigatorio,
           pta.max_inclusos
      FROM tb_produto_tipo_adicional pta
      JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
     WHERE pta.id_produto = ?
       AND ta.tipo_ativo   = 1
");
$stmtTipos->execute([$idProduto]);
$tiposAdicionais = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// adicionais por tipo
$adicionaisPorTipo = [];
$stmtAdd = $pdo->prepare("
    SELECT id_adicional, nome_adicional, valor_adicional
      FROM tb_adicional
     WHERE id_tipo_adicional = ?
       AND adicional_ativo    = 1
");
foreach ($tiposAdicionais as $tipo) {
    $stmtAdd->execute([$tipo['id_tipo_adicional']]);
    $adicionaisPorTipo[] = [
        'tipo'       => $tipo,
        'adicionais' => $stmtAdd->fetchAll(PDO::FETCH_ASSOC),
    ];
}
?>

<div
    x-data="produtoHandler(
      <?= $valorBase ?>,
      <?= $qtdSabores ?>,
      '<?= $tipoCalculo ?>',
      <?= $maxQtd ?>,
      <?= $valorExtra ?>
  )"
    x-init="init()"
    class="relative">
    <!-- Conteúdo principal -->
    <div class="container mx-auto px-4 py-10 max-w-2xl">
        <!-- Título e badge -->
        <div class="flex items-center justify-center relative mb-6">
            <a href="index.php"
                class="absolute left-0 text-primary hover:underline flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h1 class="text-2xl font-bold text-center flex items-center gap-3">
                <?= htmlspecialchars($produto['nome_produto']) ?>
            </h1>
        </div>

        <!-- Descrição -->
        <p class="text-gray-600 mb-6">
            <?= nl2br(htmlspecialchars($produto['descricao_produto'] ?? '')) ?>
        </p>

        <form id="formProduto" @submit.prevent="enviarFormulario">
            <input type="hidden" name="id_produto" value="<?= $idProduto ?>">

            <!-- Sabores -->
            <?php if ($qtdSabores > 1): ?>
                <div class="card bg-base-100 shadow p-4 mb-6">
                    <h2 class="font-semibold mb-3 text-lg">Escolha <?= $qtdSabores ?> sabor(es)</h2>
                    <div class="flex gap-2 overflow-x-auto mb-4">
                        <button type="button"
                            @click="filtroSubcategoria = ''"
                            :class="filtroSubcategoria === '' ? 'btn-primary' : 'btn-outline'"
                            class="btn btn-sm">Todos</button>
                        <template x-for="sub in subcategorias" :key="sub">
                            <button type="button"
                                @click="filtroSubcategoria = sub"
                                :class="filtroSubcategoria === sub ? 'btn-primary' : 'btn-outline'"
                                class="btn btn-sm"><span x-text="sub"></span></button>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($sabores as $s): ?>
                            <div class="border rounded p-2 flex items-center gap-2 hover:bg-gray-50 transition"
                                x-show="filtroSubcategoria === '' || filtroSubcategoria === '<?= htmlspecialchars($s['nome_subcategoria']) ?>'">
                                <input type="checkbox"
                                    name="sabores[]"
                                    value="<?= $s['id_produto'] ?>"
                                    class="checkbox checkbox-sabor"
                                    data-valor="<?= $s['valor_produto'] ?>"
                                    @change="atualizarSabores">
                                <span class="text-sm"><?= htmlspecialchars($s['nome_produto']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-sm text-gray-700">
                        <span x-text="saboresSelecionados.length"></span> / <?= $qtdSabores ?> selecionados
                    </div>
                </div>
            <?php endif; ?>

            <!-- Adicionais -->
            <?php foreach ($adicionaisPorTipo as $grupo):
                $tipoAd = $grupo['tipo'];
            ?>
                <div class="card bg-base-100 shadow p-4 mb-6">
                    <h2 class="font-semibold mb-3 text-lg">
                        <?= htmlspecialchars($tipoAd['nome_tipo_adicional']) ?>
                        <?php if ($tipoAd['max_inclusos'] > 0): ?>
                            <span class="text-sm text-gray-500">(até <?= $tipoAd['max_inclusos'] ?> inclusos)</span>
                        <?php endif; ?>
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($grupo['adicionais'] as $add):
                            $in = in_array($add['id_adicional'], $adicionaisInclusos);
                        ?>
                            <label class="flex items-center gap-2 hover:bg-gray-50 p-2 rounded transition">
                                <input type="checkbox"
                                    name="adicionais[<?= $tipoAd['id_tipo_adicional'] ?>][]"
                                    value="<?= $add['id_adicional'] ?>"
                                    class="checkbox adicional"
                                    data-valor="<?= $add['valor_adicional'] ?>"
                                    data-tipo="<?= $tipoAd['id_tipo_adicional'] ?>"
                                    data-max="<?= $tipoAd['max_inclusos'] ?>"
                                    data-incluso="<?= $in ? '1' : '0' ?>"
                                    <?= $in ? 'checked' : '' ?>
                                    @change="calcularTotal">
                                <div>
                                    <div class="text-sm"><?= htmlspecialchars($add['nome_adicional']) ?></div>
                                    <div class="text-xs text-gray-500">R$<?= number_format($add['valor_adicional'], 2, ',', '.') ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Quantidade -->
            <div class="card bg-base-100 shadow p-4 mb-6 flex items-center justify-between">
                <!-- Label -->
                <span class="font-semibold">Quantidade</span>

                <!-- Controles de decrement/increment -->
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-sm" @click="decrement()">–</button>
                    <input
                        type="text"
                        readonly
                        x-model="quantidade"
                        class="input input-bordered w-16 text-center">
                    <button type="button" class="btn btn-sm" @click="increment()">+</button>
                </div>

                <!-- Máximo -->
                <span class="text-sm text-gray-500" x-show="maxQtd > 0">
                    Máximo: <span x-text="maxQtd"></span>
                </span>
            </div>

        </form>
    </div>

    <!-- Sticky footer -->
    <div class="fixed bottom-0 left-0 right-0 bg-base-100 border-t p-4 flex justify-between items-center">
        <div class="flex items-center gap-2 cursor-pointer" @click="showBreakdown = !showBreakdown">
            <span class="font-bold text-lg">Total:</span>
            <span class="text-xl font-bold text-primary" x-text="formatarPreco(total)"></span>
            <i class="fas fa-chevron-up transition-transform" :class="{'rotate-180': showBreakdown}"></i>
        </div>
        <button class="btn btn-accent"
            :disabled="maxQtd === 0 || quantidade > maxQtd"
            @click="enviarFormulario()">
            Adicionar ao Carrinho
        </button>
    </div>

    <!-- Breakdown Collapsible -->
    <div class="fixed bottom-16 left-0 right-0 bg-white border-t shadow-inner p-4"
        x-show="showBreakdown" x-transition>
        <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span>Base:</span><span x-text="formatarPreco(breakdown.base)"></span></div>
            <template x-if="qtdSabores > 1 && valorExtra > 0">
                <div class="flex justify-between"><span>Taxa sabores:</span><span x-text="formatarPreco(valorExtra)"></span></div>
            </template>
            <div class="flex justify-between"><span>Adicionais:</span><span x-text="formatarPreco(breakdown.extras)"></span></div>
            <hr>
            <div class="flex justify-between font-semibold"><span>Total:</span><span x-text="formatarPreco(breakdown.total)"></span></div>
        </div>
    </div>
</div>

<script>
    function produtoHandler(valorBase, qtdSabores, tipoCalculo, maxQtd, valorExtra) {
        return {
            valorBase,
            qtdSabores,
            tipoCalculo,
            maxQtd,
            valorExtra,
            quantidade: 1,
            total: 0,
            breakdown: {
                base: 0,
                extras: 0,
                total: 0
            },
            saboresSelecionados: [],
            filtroSubcategoria: '',
            subcategorias: <?= json_encode(array_values(array_unique(array_column($sabores, 'nome_subcategoria'))), JSON_UNESCAPED_UNICODE) ?>,
            showBreakdown: false,

            init() {
                this.calcularTotal();
            },
            decrement() {
                if (this.quantidade > 1) {
                    this.quantidade--;
                    this.calcularTotal();
                }
            },
            increment() {
                if (this.quantidade < this.maxQtd) {
                    this.quantidade++;
                    this.calcularTotal();
                }
            },

            atualizarSabores(e) {
                const checked = [...document.querySelectorAll('.checkbox-sabor:checked')];
                if (checked.length > qtdSabores) {
                    e.target.checked = false;
                    Swal.fire('Atenção', `Escolha no máximo ${qtdSabores} sabor(es).`, 'warning');
                    return;
                }
                this.saboresSelecionados = checked.map(ch => ({
                    valor: parseFloat(ch.dataset.valor)
                }));
                this.calcularTotal();
            },

            calcularTotal() {
                let base = valorBase;
                if (qtdSabores > 1 && this.saboresSelecionados.length === qtdSabores) {
                    const vals = this.saboresSelecionados.map(s => s.valor);
                    base = tipoCalculo === 'media' ?
                        vals.reduce((a, b) => a + b, 0) / vals.length :
                        Math.max(...vals);
                    base += valorExtra;
                }
                let extras = 0,
                    byTipo = {};
                document.querySelectorAll('input.adicional:checked').forEach(ch => {
                    const tp = ch.dataset.tipo;
                    (byTipo[tp] = byTipo[tp] || []).push(ch);
                });
                Object.values(byTipo).forEach(list => {
                    const maxInc = +list[0].dataset.max;
                    list.sort((a, b) => (b.dataset.incluso === '1') - (a.dataset.incluso === '1'))
                        .slice(maxInc).forEach(ch => extras += +ch.dataset.valor);
                });
                this.total = (base + extras) * this.quantidade;
                this.breakdown = {
                    base,
                    extras,
                    total: this.total
                };
            },

            formatarPreco(v) {
                return 'R$' + v.toFixed(2).replace('.', ',');
            },

            enviarFormulario() {
                if (this.maxQtd === 0) {
                    Swal.fire('Erro', 'Produto sem estoque.', 'error');
                    return;
                }
                if (this.quantidade > this.maxQtd) {
                    Swal.fire('Erro', `Máximo ${this.maxQtd}.`, 'warning');
                    return;
                }
                if (qtdSabores > 1 && this.saboresSelecionados.length !== qtdSabores) {
                    Swal.fire('Atenção', `Selecione ${qtdSabores} sabor(es).`, 'warning');
                    return;
                }
                const payload = {
                    action: 'add',
                    id_produto: +$('[name="id_produto"]').val(),
                    quantidade: this.quantidade,
                    sabores: [],
                    adicionais: {}
                };
                $('#formProduto input[name="sabores[]"]:checked').each((_, el) => payload.sabores.push(+el.value));
                $('#formProduto input.adicional:checked').each((_, el) => {
                    const $el = $(el),
                        tp = $el.data('tipo');
                    (payload.adicionais[tp] = payload.adicionais[tp] || []).push(+el.value);
                });
                $.ajax({
                    url: 'crud/crud_carrinho.php',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify(payload)
                }).done(res => {
                    if (res.status === 'ok') {
                        Swal.fire({
                            title: 'Adicionado ao Carrinho!',
                            text: 'Ir para o carrinho ou continuar?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Carrinho',
                            cancelButtonText: 'Continuar'
                        }).then(({
                            isConfirmed
                        }) => window.location = isConfirmed ? 'carrinho.php' : 'index.php');
                    } else Swal.fire('Erro', res.mensagem || 'Não foi possível adicionar.', 'error');
                }).fail(() => Swal.fire('Erro', 'Falha de comunicação.', 'error'));
            }
        }
    }
</script>

<?php include_once 'assets/footer.php'; ?>