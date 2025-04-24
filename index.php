<?php
include_once 'header.php';

// Horário da pizzaria
date_default_timezone_set('America/Sao_Paulo');
$hora = date('H:i');
$aberta = ($hora >= '18:00' && $hora <= '23:59');
$statusLoja = $aberta ? 'Estamos aceitando pedidos!' : 'Estamos fechados no momento.';

// Mapeamento de dias da semana
$diaIngles = strtolower(date('l'));
$mapaDias = [
    'monday'    => 'segunda',
    'tuesday'   => 'terça',
    'wednesday' => 'quarta',
    'thursday'  => 'quinta',
    'friday'    => 'sexta',
    'saturday'  => 'sábado',
    'sunday'    => 'domingo'
];
$diaSemana = $mapaDias[$diaIngles];

// Buscar categorias
$categorias = $pdo->query("SELECT * FROM tb_categoria WHERE categoria_ativa = 1 ORDER BY ordem_exibicao")->fetchAll(PDO::FETCH_ASSOC);

// Buscar subcategorias
$subcategorias = $pdo->query("SELECT * FROM tb_subcategoria WHERE subcategoria_ativa = 1")->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos ativos
$produtos = $pdo->query("SELECT * FROM tb_produto WHERE produto_ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

// Buscar promoções do dia
$stmtPromo = $pdo->prepare("
    SELECT p.nome_produto, c.valor_promocional
    FROM tb_campanha_produto_dia c
    JOIN tb_produto p ON p.id_produto = c.id_produto
    WHERE c.dia_semana = ? AND c.ativo = 1
");
$stmtPromo->execute([$diaSemana]);
$promocoes = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

// Buscar brindes do dia
$stmtBrinde = $pdo->prepare("
    SELECT nome_campanha, quantidade_min_produtos, descricao_brinde
    FROM tb_campanha_brinde
    WHERE dia_semana = ? AND ativo = 1
");
$stmtBrinde->execute([$diaSemana]);
$brindes = $stmtBrinde->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="py-3 bg-accent text-white text-center mt-4">
    <p class="font-bold text-lg"><?= $statusLoja ?></p>
    <a class="text-sm underline hover:text-white/90" href="#">Ver horário de atendimento</a>
</section>

<?php if (count($promocoes) > 0 || count($brindes) > 0): ?>
    <section class="px-4 mt-2">
        <button onclick="$('#promocoesBox').toggle()" class="btn btn-sm btn-outline btn-accent mb-2">
            Ver promoções do dia
        </button>
        <div id="promocoesBox" class="hidden bg-base-100 shadow p-4 rounded-lg">
            <h3 class="text-lg font-bold mb-2">Promoções de <?= ucfirst($diaSemana) ?></h3>
            <?php if (count($promocoes) > 0): ?>
                <ul class="list-disc list-inside text-sm space-y-1 mb-3">
                    <?php foreach ($promocoes as $promo): ?>
                        <li>
                            <?= htmlspecialchars($promo['nome_produto']) ?> por
                            <span class="font-semibold text-green-600">R$<?= number_format($promo['valor_promocional'], 2, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (count($brindes) > 0): ?>
                <h4 class="text-md font-semibold mb-1">Brindes:</h4>
                <ul class="list-disc list-inside text-sm space-y-1">
                    <?php foreach ($brindes as $brinde): ?>
                        <li>
                            <?= htmlspecialchars($brinde['nome_campanha']) ?>: ao comprar <?= $brinde['quantidade_min_produtos'] ?> produto(s), <?= htmlspecialchars($brinde['descricao_brinde']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section id="cardapio" class="py-10 bg-base-200">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-6">Nosso Cardápio</h2>

        <!-- Filtro de Categorias -->
        <div class="mb-4 overflow-x-auto">
            <div class="flex space-x-2 w-max min-w-full px-2" id="filtroCategorias">
                <a href="#" class="btn btn-primary" data-categoria="todas">Todas</a>
                <a href="#" class="btn btn-outline" data-categoria="combo">Combos</a>
                <?php foreach ($categorias as $cat): ?>
                    <a href="#" class="btn btn-outline" data-categoria="<?= $cat['id_categoria'] ?>">
                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filtro de Subcategorias -->
        <div class="mb-8 overflow-x-auto">
            <div class="flex space-x-2 w-max min-w-full px-2" id="filtroSubcategorias">
                <a href="#" class="btn btn-sm btn-outline" data-subcategoria="todas">Todas</a>
                <?php foreach ($subcategorias as $sub): ?>
                    <a href="#" class="btn btn-sm btn-outline" data-subcategoria="<?= $sub['id_subcategoria'] ?>">
                        <?= htmlspecialchars($sub['nome_subcategoria']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lista de Produtos -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($produtos as $produto): ?>
                <?php
                $stmt = $pdo->prepare("SELECT id_subcategoria FROM tb_subcategoria_categoria WHERE id_categoria = ?");
                $stmt->execute([$produto['id_categoria']]);
                $subs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $subList = implode(',', $subs);
                $isCombo = stripos($produto['nome_produto'], 'combo') !== false;
                ?>
                <div class="card bg-base-100 shadow-xl"
                     data-categoria="<?= $isCombo ? 'combo' : $produto['id_categoria'] ?>"
                     data-subcategorias="<?= $subList ?>">
                    <div class="card-body flex flex-col justify-between">
                        <div>
                            <h3 class="card-title"><?= htmlspecialchars($produto['nome_produto']) ?></h3>
                            <p class="text-sm text-gray-600">R$<?= number_format($produto['valor_produto'], 2, ',', '.') ?></p>
                        </div>
                        <div class="card-actions mt-4">
                            <a href="produto.php?id=<?= $produto['id_produto'] ?>" class="btn btn-primary w-full">Fazer Pedido</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $('#filtroCategorias a').on('click', function(e) {
            e.preventDefault();
            $('#filtroCategorias a').removeClass('btn-primary').addClass('btn-outline');
            $(this).removeClass('btn-outline').addClass('btn-primary');
            const idCategoria = $(this).data('categoria');

            $('.card[data-categoria]').each(function() {
                const cat = $(this).data('categoria');
                if (idCategoria === 'todas' || cat == idCategoria) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $('#filtroSubcategorias a').on('click', function(e) {
            e.preventDefault();
            $('#filtroSubcategorias a').removeClass('btn-primary').addClass('btn-outline');
            $(this).removeClass('btn-outline').addClass('btn-primary');
            const idSub = $(this).data('subcategoria');

            $('.card[data-subcategorias]').each(function() {
                const subs = $(this).data('subcategorias').toString().split(',');
                if (idSub === 'todas' || subs.includes(idSub.toString())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });
</script>

<?php include_once 'footer.php'; ?>
