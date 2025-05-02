<?php
include_once 'header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado.</p>";
    include_once 'footer.php';
    exit;
}

$idProduto = (int) $_GET['id'];
date_default_timezone_set('America/Sao_Paulo');

$mapaDias = [
    'monday' => 'segunda',
    'tuesday' => 'terça',
    'wednesday' => 'quarta',
    'thursday' => 'quinta',
    'friday' => 'sexta',
    'saturday' => 'sábado',
    'sunday' => 'domingo',
];
$diaSemana = $mapaDias[strtolower(date('l'))];

// Produto atual
$stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ? AND produto_ativo = 1");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado ou inativo.</p>";
    include_once 'footer.php';
    exit;
}

$valorPromo = $pdo->prepare("SELECT valor_promocional FROM tb_campanha_produto_dia WHERE id_produto = ? AND dia_semana = ? AND ativo = 1");
$valorPromo->execute([$idProduto, $diaSemana]);
$promo = $valorPromo->fetchColumn();
$valorProduto = $promo ?: $produto['valor_produto'];

$qtdSabores = (int) ($produto['qtd_sabores'] ?? 1);
$tipoCalculo = $produto['tipo_calculo_preco'] ?? 'maior';

// Sabores válidos
$sabores = [];
if ($qtdSabores > 1) {
    $stmtSabores = $pdo->query("SELECT p.id_produto, p.nome_produto, p.valor_produto, s.nome_subcategoria
        FROM tb_produto p
        LEFT JOIN tb_subcategoria_produto sp ON p.id_produto = sp.id_produto
        LEFT JOIN tb_subcategoria s ON sp.id_subcategoria = s.id_subcategoria
        WHERE p.produto_ativo = 1 
        AND p.qtd_sabores <= 1 
        AND p.nome_produto NOT LIKE '%combo%' 
        AND p.id_categoria = {$produto['id_categoria']}
        ORDER BY s.nome_subcategoria, p.nome_produto
    ");
    $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);
}

// Adicionais inclusos
$stmt = $pdo->prepare("SELECT id_adicional FROM tb_produto_adicional_incluso WHERE id_produto = ?");
$stmt->execute([$idProduto]);
$adicionaisInclusos = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_adicional'));

// Tipos de adicionais
$stmtTipos = $pdo->prepare("SELECT ta.id_tipo_adicional, ta.nome_tipo_adicional, pta.max_inclusos 
    FROM tb_produto_tipo_adicional pta
    JOIN tb_tipo_adicional ta ON ta.id_tipo_adicional = pta.id_tipo_adicional
    WHERE pta.id_produto = ?");
$stmtTipos->execute([$idProduto]);
$tiposAdicionais = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// Adicionais por tipo
$adicionaisPorTipo = [];
foreach ($tiposAdicionais as $tipo) {
    $stmtAdd = $pdo->prepare("SELECT * FROM tb_adicional WHERE id_tipo_adicional = ? AND adicional_ativo = 1");
    $stmtAdd->execute([$tipo['id_tipo_adicional']]);
    $adicionaisPorTipo[] = [
        'tipo' => $tipo,
        'adicionais' => $stmtAdd->fetchAll(PDO::FETCH_ASSOC)
    ];
}
?>

<div class="container mx-auto px-4 py-10 max-w-2xl" x-data="produtoHandler(<?= $valorProduto ?>, <?= $qtdSabores ?>, '<?= $tipoCalculo ?>')">
    <div class="flex items-center justify-center relative mb-6">
        <a href="index.php" class="absolute left-0 text-primary hover:underline flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="text-xl font-bold text-center"><?= htmlspecialchars($produto['nome_produto']) ?></h1>
    </div>

    <p class="text-gray-500 mb-6"><?= nl2br(htmlspecialchars($produto['descricao_produto'] ?? '')) ?></p>

    <form id="formProduto" @submit.prevent="enviarFormulario">
        <input type="hidden" name="id_produto" value="<?= $idProduto ?>">

        <div class="space-y-6">

            <?php if ($qtdSabores > 1): ?>
                <div class="space-y-4">
                    <h3 class="font-semibold text-lg mb-2">Escolha <?= $qtdSabores ?> sabor(es)</h3>
                    <div class="flex gap-2 overflow-x-auto mb-4">
                        <button type="button" @click="filtroSubcategoria = ''" class="btn btn-sm" :class="{'btn-primary': filtroSubcategoria === '', 'btn-outline': filtroSubcategoria !== ''}">Todos</button>
                        <template x-for="sub in subcategorias" :key="sub">
                            <button type="button" @click="filtroSubcategoria = sub" class="btn btn-sm"
                                    :class="{'btn-primary': filtroSubcategoria === sub, 'btn-outline': filtroSubcategoria !== sub}">
                                <span x-text="sub"></span>
                            </button>
                        </template>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($sabores as $sabor): ?>
                            <label class="flex items-center gap-2" x-show="filtroSubcategoria === '' || filtroSubcategoria === '<?= htmlspecialchars($sabor['nome_subcategoria'] ?? 'Sem Categoria') ?>'">
                                <input type="checkbox" name="sabores[]" value="<?= $sabor['id_produto'] ?>"
                                       class="checkbox checkbox-sm checkbox-sabor"
                                       data-valor="<?= $sabor['valor_produto'] ?>"
                                       @change="atualizarSabores">
                                <span class="text-sm"><?= htmlspecialchars($sabor['nome_produto']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-sm text-gray-600 mt-2">
                        <span x-text="saboresSelecionados.length"></span> / <?= $qtdSabores ?> selecionados
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($adicionaisPorTipo as $grupo): ?>
                <?php $tipo = $grupo['tipo']; ?>
                <div>
                    <label class="font-semibold block mb-1">
                        <?= htmlspecialchars($tipo['nome_tipo_adicional']) ?>
                        <?php if ($tipo['max_inclusos'] > 0): ?>
                            <span class="text-sm text-gray-400">(até <?= $tipo['max_inclusos'] ?> inclusos)</span>
                        <?php endif; ?>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($grupo['adicionais'] as $add): ?>
                            <label class="flex items-center gap-2">
                                <input type="checkbox"
                                       class="checkbox checkbox-sm adicional"
                                       name="adicionais[<?= $tipo['id_tipo_adicional'] ?>][]"
                                       data-valor="<?= $add['valor_adicional'] ?>"
                                       value="<?= $add['id_adicional'] ?>"
                                       @change="calcularTotal"
                                    <?= in_array($add['id_adicional'], $adicionaisInclusos) ? 'checked' : '' ?>>
                                <span class="text-sm"><?= htmlspecialchars($add['nome_adicional']) ?></span>
                                <span class="text-xs text-gray-500">R$<?= number_format($add['valor_adicional'], 2, ',', '.') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div>
                <label class="block mb-1 font-semibold">Quantidade</label>
                <input type="number" name="quantidade" min="1" x-model="quantidade" class="input input-bordered w-24" @input="calcularTotal">
            </div>

            <div class="text-xl font-bold">
                Total: <span x-text="formatarPreco(total)"></span>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">Adicionar ao Carrinho</button>
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
        subcategorias: <?= json_encode(array_values(array_unique(array_column($sabores, 'nome_subcategoria')))) ?>,

        atualizarSabores(event) {
            const checkboxes = document.querySelectorAll('.checkbox-sabor:checked');
            if (checkboxes.length > qtdSabores) {
                event.target.checked = false;
                Swal.fire('Atenção', 'Você pode escolher no máximo ' + qtdSabores + ' sabor(es).', 'warning');
                return;
            }

            this.saboresSelecionados = Array.from(checkboxes).map(el => ({
                id: el.value,
                valor: parseFloat(el.dataset.valor)
            }));

            this.calcularTotal();
        },

        calcularTotal() {
            let base = valorBase;

            if (qtdSabores > 1 && this.saboresSelecionados.length === qtdSabores) {
                const valores = this.saboresSelecionados.map(s => s.valor);
                base = tipoCalculo === 'media'
                    ? valores.reduce((a, b) => a + b, 0) / valores.length
                    : Math.max(...valores);
            }

            let extras = 0;
            document.querySelectorAll('input.adicional:checked').forEach(input => {
                extras += parseFloat(input.dataset.valor);
            });

            this.total = (base + extras) * this.quantidade;
        },

        formatarPreco(v) {
            return 'R$' + v.toFixed(2).replace('.', ',');
        },

        enviarFormulario() {
            if (qtdSabores > 0 && this.saboresSelecionados.length !== qtdSabores) {
                Swal.fire('Atenção', 'Selecione exatamente ' + qtdSabores + ' sabor(es).', 'warning');
                return;
            }

            const formData = $('#formProduto').serialize();
            $.post('adicionar_carrinho.php', formData, function (res) {
                if (res.status === 'ok') {
                    Swal.fire({
                        title: 'Adicionado ao Carrinho!',
                        text: 'Deseja escolher mais produtos ou ir para o carrinho?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Ir para o Carrinho',
                        cancelButtonText: 'Escolher mais'
                    }).then(result => {
                        window.location.href = result.isConfirmed ? 'carrinho.php' : 'index.php';
                    });
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        }
    }
}
</script>

<?php include_once 'footer.php'; ?>
