<?php
include_once 'assets/header.php';

// Valida ID na URL
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado.</p>";
    include_once 'assets/footer.php';
    exit;
}

$idProduto = (int) $_GET['id'];
date_default_timezone_set('America/Sao_Paulo');

// Mapeia dia da semana para PT-BR
$mapaDias = [
    'monday'    => 'segunda',
    'tuesday'   => 'terça',
    'wednesday' => 'quarta',
    'thursday'  => 'quinta',
    'friday'    => 'sexta',
    'saturday'  => 'sábado',
    'sunday'    => 'domingo',
];
$diaSemana = $mapaDias[strtolower(date('l'))];

// 1) Busca produto ativo e com estoque
$stmt = $pdo->prepare("
    SELECT *
      FROM tb_produto
     WHERE id_produto   = ?
       AND produto_ativo = 1
       AND qtd_produto <> 0
");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produto) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado, inativo ou sem estoque.</p>";
    include_once 'assets/footer.php';
    exit;
}

// 2) Verifica promoção do dia
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
    : (float)$produto['valor_produto'];

// 3) Sabores (se multi-sabores)
$qtdSabores  = (int) ($produto['qtd_sabores'] ?? 1);
$tipoCalculo = $produto['tipo_calculo_preco'] ?? 'maior';

$sabores = [];
if ($qtdSabores > 1) {
    $stmtSabores = $pdo->prepare("
        SELECT p.id_produto,
               p.nome_produto,
               p.valor_produto,
               COALESCE(s.nome_subcategoria,'Sem Categoria') AS nome_subcategoria
          FROM tb_produto p
     LEFT JOIN tb_subcategoria_produto sp ON p.id_produto = sp.id_produto
     LEFT JOIN tb_subcategoria s           ON sp.id_subcategoria = s.id_subcategoria
         WHERE p.produto_ativo = 1
           AND p.qtd_sabores  <= 1
           AND p.nome_produto NOT LIKE '%combo%'
           AND p.id_categoria = ?
      ORDER BY s.nome_subcategoria, p.nome_produto
    ");
    $stmtSabores->execute([$produto['id_categoria']]);
    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Adicionais inclusos por padrão
$stmtIncl = $pdo->prepare("
    SELECT id_adicional
      FROM tb_produto_adicional_incluso
     WHERE id_produto = ?
");
$stmtIncl->execute([$idProduto]);
$adicionaisInclusos = array_map('intval', $stmtIncl->fetchAll(PDO::FETCH_COLUMN));

// 5) Tipos de adicionais para o produto
$stmtTipos = $pdo->prepare("
    SELECT ta.id_tipo_adicional,
           ta.nome_tipo_adicional,
           pta.obrigatorio,
           pta.max_inclusos
      FROM tb_produto_tipo_adicional pta
      JOIN tb_tipo_adicional ta
        ON ta.id_tipo_adicional = pta.id_tipo_adicional
       AND ta.tipo_ativo        = 1
     WHERE pta.id_produto = ?
");
$stmtTipos->execute([$idProduto]);
$tiposAdicionais = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// 6) Carrega cada adicional por tipo
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

<div class="container mx-auto px-4 py-10 max-w-2xl"
    x-data="produtoHandler(<?= $valorBase ?>, <?= $qtdSabores ?>, '<?= $tipoCalculo ?>')">

    <div class="flex items-center justify-center relative mb-6">
        <a href="index.php"
            class="absolute left-0 text-primary hover:underline flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="text-xl font-bold text-center">
            <?= htmlspecialchars($produto['nome_produto']) ?>
        </h1>
    </div>

    <p class="text-gray-500 mb-6">
        <?= nl2br(htmlspecialchars($produto['descricao_produto'] ?? '')) ?>
    </p>

    <form id="formProduto" @submit.prevent="enviarFormulario">
        <input type="hidden" name="id_produto" value="<?= $idProduto ?>">

        <div class="space-y-6">
            <!-- Sabores -->
            <?php if ($qtdSabores > 1): ?>
                <div class="space-y-4">
                    <h3 class="font-semibold text-lg mb-2">
                        Escolha <?= $qtdSabores ?> sabor(es)
                    </h3>
                    <div class="flex gap-2 overflow-x-auto mb-4">
                        <button type="button"
                            @click="filtroSubcategoria = ''"
                            class="btn btn-sm"
                            :class="filtroSubcategoria === '' ? 'btn-primary' : 'btn-outline'">
                            Todos
                        </button>
                        <template x-for="sub in subcategorias" :key="sub">
                            <button type="button"
                                @click="filtroSubcategoria = sub"
                                class="btn btn-sm"
                                :class="filtroSubcategoria === sub ? 'btn-primary' : 'btn-outline'">
                                <span x-text="sub"></span>
                            </button>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($sabores as $s): ?>
                            <label class="flex items-center gap-2"
                                x-show="filtroSubcategoria === '' || filtroSubcategoria === '<?= htmlspecialchars($s['nome_subcategoria']) ?>'">
                                <input type="checkbox"
                                    name="sabores[]"
                                    value="<?= $s['id_produto'] ?>"
                                    class="checkbox checkbox-sm checkbox-sabor"
                                    data-valor="<?= $s['valor_produto'] ?>"
                                    @change="atualizarSabores">
                                <span class="text-sm"><?= htmlspecialchars($s['nome_produto']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-2">
                        <span x-text="saboresSelecionados.length"></span>
                        / <?= $qtdSabores ?> selecionados
                    </div>
                </div>
            <?php endif; ?>

            <!-- Adicionais por tipo -->
            <?php foreach ($adicionaisPorTipo as $grupo):
                $tipo = $grupo['tipo'];
            ?>
                <div>
                    <label class="font-semibold block mb-1">
                        <?= htmlspecialchars($tipo['nome_tipo_adicional']) ?>
                        <?php if ($tipo['max_inclusos'] > 0): ?>
                            <span class="text-sm text-gray-400">
                                (até <?= $tipo['max_inclusos'] ?> inclusos)
                            </span>
                        <?php endif; ?>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($grupo['adicionais'] as $add):
                            $in = in_array($add['id_adicional'], $adicionaisInclusos);
                        ?>
                            <label class="flex items-center gap-2">
                                <input type="checkbox"
                                    name="adicionais[<?= $tipo['id_tipo_adicional'] ?>][]"
                                    value="<?= $add['id_adicional'] ?>"
                                    class="checkbox checkbox-sm adicional"
                                    data-valor="<?= $add['valor_adicional'] ?>"
                                    data-tipo="<?= $tipo['id_tipo_adicional'] ?>"
                                    data-max="<?= $tipo['max_inclusos'] ?>"
                                    data-incluso="<?= $in ? '1' : '0' ?>"
                                    <?= $in ? 'checked' : '' ?>
                                    @change="calcularTotal">
                                <span class="text-sm"><?= htmlspecialchars($add['nome_adicional']) ?></span>
                                <span class="text-xs text-gray-500">
                                    R$<?= number_format($add['valor_adicional'], 2, ',', '.') ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Quantidade -->
            <div>
                <label class="block mb-1 font-semibold">Quantidade</label>
                <input type="number"
                    name="quantidade"
                    min="1"
                    x-model="quantidade"
                    class="input input-bordered w-24"
                    @input="calcularTotal">
            </div>

            <!-- Total -->
            <div class="text-xl font-bold">
                Total: <span x-text="formatarPreco(total)"></span>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">
                Adicionar ao Carrinho
            </button>
        </div>
    </form>
</div>

<script>
    function produtoHandler(valorBase, qtdSabores, tipoCalculo) {
        return {
            quantidade: 1,
            total: valorBase,
            saboresSelecionados: [],
            filtroSubcategoria: '',
            subcategorias: <?= json_encode(array_values(array_unique(array_column($sabores, 'nome_subcategoria'))), JSON_UNESCAPED_UNICODE) ?>,

            atualizarSabores(e) {
                const checked = [...document.querySelectorAll('.checkbox-sabor:checked')];
                if (checked.length > qtdSabores) {
                    e.target.checked = false;
                    Swal.fire('Atenção', 'Escolha no máximo ' + qtdSabores + ' sabor(es).', 'warning');
                    return;
                }
                this.saboresSelecionados = checked.map(ch => ({
                    id: ch.value,
                    valor: parseFloat(ch.dataset.valor)
                }));
                this.calcularTotal();
            },

            calcularTotal() {
                // 1) Base
                let base = valorBase;
                if (qtdSabores > 1 && this.saboresSelecionados.length === qtdSabores) {
                    const vals = this.saboresSelecionados.map(s => s.valor);
                    base = tipoCalculo === 'media' ?
                        vals.reduce((a, b) => a + b, 0) / vals.length :
                        Math.max(...vals);
                }
                // 2) Agrupa e soma apenas extras
                let extras = 0;
                const byTipo = {};
                document.querySelectorAll('input.adicional:checked').forEach(ch => {
                    const tp = ch.dataset.tipo;
                    (byTipo[tp] = byTipo[tp] || []).push(ch);
                });
                Object.values(byTipo).forEach(list => {
                    const maxInc = parseInt(list[0].dataset.max, 10);
                    const sorted = list.sort((a, b) =>
                        (b.dataset.incluso === '1' ? 1 : 0) - (a.dataset.incluso === '1' ? 1 : 0)
                    );
                    sorted.slice(maxInc).forEach(ch => {
                        extras += parseFloat(ch.dataset.valor);
                    });
                });
                this.total = (base + extras) * this.quantidade;
            },

            formatarPreco(v) {
                return 'R$' + v.toFixed(2).replace('.', ',');
            },

            enviarFormulario() {
                if (qtdSabores > 1 && this.saboresSelecionados.length !== qtdSabores) {
                    Swal.fire('Atenção', 'Selecione exatamente ' + qtdSabores + ' sabor(es).', 'warning');
                    return;
                }
                const payload = {
                    action: 'add',
                    id_produto: parseInt($('[name="id_produto"]').val(), 10),
                    quantidade: parseInt(this.quantidade, 10),
                    sabores: [],
                    adicionais: {}
                };
                // sabores
                $('#formProduto input[name="sabores[]"]:checked').each((_, el) => {
                    payload.sabores.push(parseInt(el.value, 10));
                });
                // adicionais
                $('#formProduto input.adicional:checked').each((_, el) => {
                    const $el = $(el),
                        tp = $el.data('tipo');
                    (payload.adicionais[tp] = payload.adicionais[tp] || [])
                    .push(parseInt(el.value, 10));
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
                            text: 'Ir para o carrinho ou continuar comprando?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Carrinho',
                            cancelButtonText: 'Continuar'
                        }).then(({
                            isConfirmed
                        }) => {
                            window.location = isConfirmed ? 'carrinho.php' : 'index.php';
                        });
                    } else {
                        Swal.fire('Erro', res.mensagem || 'Não foi possível adicionar.', 'error');
                    }
                }).fail(() => {
                    Swal.fire('Erro', 'Falha de comunicação com o servidor.', 'error');
                });
            }
        };
    }
</script>

<?php include_once 'assets/footer.php'; ?>