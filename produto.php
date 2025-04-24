<?php
include_once 'header.php';

if (!isset($_GET['id'])) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado.</p>";
    include_once 'footer.php';
    exit;
}

$idProduto = (int) $_GET['id'];

// Mapeia dia da semana
date_default_timezone_set('America/Sao_Paulo');
$mapaDias = [
    'monday' => 'segunda',
    'tuesday' => 'terça',
    'wednesday' => 'quarta',
    'thursday' => 'quinta',
    'friday' => 'sexta',
    'saturday' => 'sábado',
    'sunday' => 'domingo'
];
$diaSemana = $mapaDias[strtolower(date('l'))];

// Buscar dados do produto
$stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ? AND produto_ativo = 1");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado ou inativo.</p>";
    include_once 'footer.php';
    exit;
}

// Buscar valor promocional (se houver)
$stmt = $pdo->prepare("
    SELECT valor_promocional 
    FROM tb_campanha_produto_dia 
    WHERE id_produto = ? AND dia_semana = ? AND ativo = 1
");
$stmt->execute([$idProduto, $diaSemana]);
$valorPromo = $stmt->fetchColumn();

$valorProduto = $valorPromo ?: $produto['valor_produto'];

// Buscar tipos de adicionais permitidos
$stmtTipos = $pdo->prepare("
    SELECT ta.id_tipo_adicional, ta.nome_tipo_adicional 
    FROM tb_produto_tipo_adicional pta
    JOIN tb_tipo_adicional ta ON ta.id_tipo_adicional = pta.id_tipo_adicional
    WHERE pta.id_produto = ?
");
$stmtTipos->execute([$idProduto]);
$tiposAdicionais = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// Buscar adicionais por tipo
$adicionaisPorTipo = [];
foreach ($tiposAdicionais as $tipo) {
    $stmtAdd = $pdo->prepare("
        SELECT * FROM tb_adicional 
        WHERE id_tipo_adicional = ? AND adicional_ativo = 1
    ");
    $stmtAdd->execute([$tipo['id_tipo_adicional']]);
    $adicionaisPorTipo[$tipo['nome_tipo_adicional']] = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mx-auto px-4 py-10 max-w-2xl">
    <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($produto['nome_produto']) ?></h1>
    <p class="text-gray-500 mb-6"><?= nl2br(htmlspecialchars($produto['descricao_produto'] ?? '')) ?></p>

    <form id="formProduto" method="post" action="adicionar_carrinho.php">
        <input type="hidden" name="id_produto" value="<?= $idProduto ?>">
        <div class="space-y-6">

            <?php foreach ($adicionaisPorTipo as $tipo => $adicionais): ?>
                <div>
                    <label class="font-semibold block mb-1"><?= htmlspecialchars($tipo) ?></label>
                    <div class="space-y-2">
                        <?php foreach ($adicionais as $add): ?>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="checkbox adicional"
                                    name="adicionais[]"
                                    data-valor="<?= $add['valor_adicional'] ?>"
                                    value="<?= $add['id_adicional'] ?>">
                                <?= htmlspecialchars($add['nome_adicional']) ?>
                                <span class="text-sm text-gray-500">R$<?= number_format($add['valor_adicional'], 2, ',', '.') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div>
                <label class="block mb-1 font-semibold">Quantidade</label>
                <input type="number" name="quantidade" id="quantidade" value="1" min="1" class="input input-bordered w-24">
            </div>

            <div class="text-xl font-bold">
                Total: <span id="valorTotal">R$<?= number_format($valorProduto, 2, ',', '.') ?></span>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">Adicionar ao Carrinho</button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        function calcularTotal() {
            let base = parseFloat(<?= $valorProduto ?>);
            let qtd = parseInt($('#quantidade').val()) || 1;
            let adicionais = 0;

            $('.adicional:checked').each(function() {
                adicionais += parseFloat($(this).data('valor'));
            });

            const total = (base + adicionais) * qtd;
            $('#valorTotal').text('R$' + total.toFixed(2).replace('.', ','));
        }

        $('.adicional, #quantidade').on('change keyup', calcularTotal);
    });
</script>

<?php include_once 'footer.php'; ?>