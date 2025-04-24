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
    'monday'    => 'segunda',
    'tuesday'   => 'terça',
    'wednesday' => 'quarta',
    'thursday'  => 'quinta',
    'friday'    => 'sexta',
    'saturday'  => 'sábado',
    'sunday'    => 'domingo',
];
$diaSemana = $mapaDias[strtolower(date('l'))];

// Produto
$stmt = $pdo->prepare("SELECT * FROM tb_produto WHERE id_produto = ? AND produto_ativo = 1");
$stmt->execute([$idProduto]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "<p class='text-center mt-10 text-red-500'>Produto não encontrado ou inativo.</p>";
    include_once 'footer.php';
    exit;
}

// Preço promocional
$stmt = $pdo->prepare("SELECT valor_promocional FROM tb_campanha_produto_dia WHERE id_produto = ? AND dia_semana = ? AND ativo = 1");
$stmt->execute([$idProduto, $diaSemana]);
$valorPromo = $stmt->fetchColumn();
$valorProduto = $valorPromo ?: $produto['valor_produto'];

// Adicionais inclusos
$stmt = $pdo->prepare("SELECT id_adicional FROM tb_produto_adicional_incluso WHERE id_produto = ?");
$stmt->execute([$idProduto]);
$adicionaisInclusos = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_adicional'));

// Tipos de adicionais
$stmtTipos = $pdo->prepare("
    SELECT ta.id_tipo_adicional, ta.nome_tipo_adicional, pta.max_inclusos 
    FROM tb_produto_tipo_adicional pta
    JOIN tb_tipo_adicional ta ON ta.id_tipo_adicional = pta.id_tipo_adicional
    WHERE pta.id_produto = ?
");
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

<div class="container mx-auto px-4 py-10 max-w-2xl">
    <div class="flex items-center justify-center relative mb-6">
        <a href="index.php" class="absolute left-0 text-primary hover:underline flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1 class="text-xl font-bold text-center"><?= htmlspecialchars($produto['nome_produto']) ?></h1>
    </div>

    <p class="text-gray-500 mb-6"><?= nl2br(htmlspecialchars($produto['descricao_produto'] ?? '')) ?></p>

    <form id="formProduto">
        <input type="hidden" name="id_produto" value="<?= $idProduto ?>">
        <div class="space-y-6">

            <?php foreach ($adicionaisPorTipo as $grupo): ?>
                <?php $tipo = $grupo['tipo']; ?>
                <div data-tipo-id="<?= $tipo['id_tipo_adicional'] ?>" data-max-inclusos="<?= $tipo['max_inclusos'] ?>">
                    <label class="font-semibold block mb-1">
                        <?= htmlspecialchars($tipo['nome_tipo_adicional']) ?>
                        <?php if ($tipo['max_inclusos'] > 0): ?>
                            <span class="text-sm text-gray-400">(até <?= $tipo['max_inclusos'] ?> inclusos)</span>
                        <?php endif; ?>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($grupo['adicionais'] as $add): ?>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="checkbox checkbox-sm adicional"
                                    name="adicionais[<?= $tipo['id_tipo_adicional'] ?>][]"
                                    data-tipo="<?= $tipo['id_tipo_adicional'] ?>"
                                    data-valor="<?= $add['valor_adicional'] ?>"
                                    value="<?= $add['id_adicional'] ?>"
                                    <?= in_array($add['id_adicional'], $adicionaisInclusos) ? 'checked' : '' ?>>
                                <span class="text-sm"><?= htmlspecialchars($add['nome_adicional']) ?></span>
                                <span class="text-xs text-gray-500 valor-adicional">
                                    R$<?= number_format($add['valor_adicional'], 2, ',', '.') ?>
                                </span>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        function calcularTotal() {
            let base = parseFloat(<?= $valorProduto ?>);
            let qtd = parseInt($('#quantidade').val()) || 1;
            let totalExtras = 0;

            $('[data-tipo-id]').each(function () {
                const tipoId = $(this).data('tipo-id');
                const maxInclusos = parseInt($(this).data('max-inclusos')) || 0;
                const checkboxes = $(`input[data-tipo='${tipoId}']:checked`);

                const sorted = checkboxes.toArray().sort((a, b) => {
                    return parseFloat($(a).data('valor')) - parseFloat($(b).data('valor'));
                });

                sorted.forEach((el, index) => {
                    const $el = $(el);
                    const valor = parseFloat($el.data('valor'));
                    const span = $el.closest('label').find('.valor-adicional');

                    if (index < maxInclusos) {
                        span.removeClass('text-red-500').addClass('text-gray-500').text('Incluso');
                    } else {
                        totalExtras += valor;
                        span.removeClass('text-gray-500').addClass('text-red-500')
                            .text('R$' + valor.toFixed(2).replace('.', ',') + ' (extra)');
                    }
                });

                $(`input[data-tipo='${tipoId}']:not(:checked)`).each(function () {
                    const valor = parseFloat($(this).data('valor'));
                    $(this).closest('label').find('.valor-adicional')
                        .removeClass('text-red-500').addClass('text-gray-500')
                        .text('R$' + valor.toFixed(2).replace('.', ','));
                });
            });

            const total = (base + totalExtras) * qtd;
            $('#valorTotal').text('R$' + total.toFixed(2).replace('.', ','));
        }

        $('.adicional, #quantidade').on('change keyup', calcularTotal);
        calcularTotal();

        $('#formProduto').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();

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
                        if (result.isConfirmed) {
                            window.location.href = 'carrinho.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        });
    });
</script>

<?php include_once 'footer.php'; ?>
