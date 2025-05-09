<?php
include_once 'assets/header.php';

date_default_timezone_set('America/Sao_Paulo');
$hora = date('H:i');

// determina se a loja está aberta com base no horário
$aberta = ($hora >= '18:00' && $hora <= '23:59');
$statusLoja = $aberta
    ? 'Estamos aceitando pedidos!'
    : 'Estamos fechados no momento.';

// mapeia o dia da semana para português
$diaIngles = strtolower(date('l'));
$mapaDias = [
    'monday'    => 'segunda',
    'tuesday'   => 'terça',
    'wednesday' => 'quarta',
    'thursday'  => 'quinta',
    'friday'    => 'sexta',
    'saturday'  => 'sábado',
    'sunday'    => 'domingo',
];
$diaSemana = $mapaDias[$diaIngles];

// 1) categorias ativas
$categorias = $pdo
    ->query("
        SELECT *
          FROM tb_categoria
         WHERE categoria_ativa = 1
      ORDER BY ordem_exibicao
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

// 2) subcategorias ativas
$subcategorias = $pdo
    ->query("
        SELECT *
          FROM tb_subcategoria
         WHERE subcategoria_ativa = 1
      ORDER BY nome_subcategoria
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

// 3) produtos ativos e com estoque disponível (qtd_produto <> 0)
$produtos = $pdo
    ->query("
        SELECT *
          FROM tb_produto
         WHERE produto_ativo = 1
           AND qtd_produto <> 0
      ORDER BY nome_produto
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

// 4) promoções do dia
$stmtPromo = $pdo->prepare("
    SELECT p.nome_produto,
           c.valor_promocional
      FROM tb_campanha_produto_dia c
      JOIN tb_produto p USING(id_produto)
     WHERE c.dia_semana = ?
       AND c.ativo = 1
");
$stmtPromo->execute([$diaSemana]);
$promocoes = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

// 5) brindes do dia
$stmtBrinde = $pdo->prepare("
    SELECT nome_campanha,
           quantidade_min_produtos,
           descricao_brinde
      FROM tb_campanha_brinde
     WHERE dia_semana = ?
       AND ativo = 1
");
$stmtBrinde->execute([$diaSemana]);
$brindes = $stmtBrinde->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="py-3 bg-accent text-white text-center mt-4">
    <p class="font-bold text-lg"><?= $statusLoja ?></p>
    <a class="text-sm underline hover:text-white/90" href="#">Ver horário de atendimento</a>
</section>

<section class="px-4 mt-2" x-data="{ mostrarPromocoes: false }">
    <?php if (count($promocoes) > 0 || count($brindes) > 0): ?>
        <button
            @click="mostrarPromocoes = !mostrarPromocoes"
            class="btn btn-sm btn-outline btn-accent mb-2">
            Ver promoções do dia
        </button>
        <div x-show="mostrarPromocoes" class="bg-base-100 shadow p-4 rounded-lg">
            <h3 class="text-lg font-bold mb-2">
                Promoções de <?= ucfirst($diaSemana) ?>
            </h3>
            <ul class="list-disc list-inside text-sm space-y-1 mb-3">
                <?php foreach ($promocoes as $promo): ?>
                    <li>
                        <?= htmlspecialchars($promo['nome_produto']) ?>
                        por
                        <span class="font-semibold text-green-600">
                            R$<?= number_format($promo['valor_promocional'], 2, ',', '.') ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($brindes): ?>
                <h4 class="text-md font-semibold mb-1">Brindes:</h4>
                <ul class="list-disc list-inside text-sm space-y-1">
                    <?php foreach ($brindes as $brinde): ?>
                        <li>
                            <?= htmlspecialchars($brinde['nome_campanha']) ?>:
                            ao comprar <?= $brinde['quantidade_min_produtos'] ?> produto(s),
                            <?= htmlspecialchars($brinde['descricao_brinde']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section
    id="cardapio"
    class="py-10 bg-base-200"
    x-data="{
      categoriaAtiva: 'todas',
      subcategoriaAtiva: 'todas',
      termoBusca: '',
    }">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-6">Nosso Cardápio</h2>

        <!-- Busca por nome -->
        <div class="mb-6 flex justify-center">
            <input
                type="text"
                x-model="termoBusca"
                class="input input-bordered w-full max-w-md"
                placeholder="Buscar por nome..." />
        </div>

        <!-- Filtro de Categorias -->
        <div class="mb-4 overflow-x-auto">
            <div class="flex space-x-2 w-max min-w-full px-2">
                <button
                    @click="categoriaAtiva = 'todas'"
                    :class="categoriaAtiva === 'todas' ? 'btn-primary' : 'btn-outline'"
                    class="btn">Todas</button>
                <?php foreach ($categorias as $cat): ?>
                    <button
                        @click="categoriaAtiva = '<?= $cat['id_categoria'] ?>'"
                        :class="categoriaAtiva === '<?= $cat['id_categoria'] ?>' ? 'btn-primary' : 'btn-outline'"
                        class="btn">
                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filtro de Subcategorias -->
        <div class="mb-8 overflow-x-auto">
            <div class="flex space-x-2 w-max min-w-full px-2">
                <button
                    @click="subcategoriaAtiva = 'todas'"
                    :class="subcategoriaAtiva === 'todas' ? 'btn-primary' : 'btn-outline'"
                    class="btn btn-sm">Todas</button>
                <?php foreach ($subcategorias as $sub): ?>
                    <button
                        @click="subcategoriaAtiva = '<?= $sub['id_subcategoria'] ?>'"
                        :class="subcategoriaAtiva === '<?= $sub['id_subcategoria'] ?>' ? 'btn-primary' : 'btn-outline'"
                        class="btn btn-sm">
                        <?= htmlspecialchars($sub['nome_subcategoria']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lista de produtos -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($produtos as $produto): ?>
                <?php
                $stmt = $pdo->prepare("
                    SELECT id_subcategoria
                      FROM tb_subcategoria_produto
                     WHERE id_produto = ?
                ");
                $stmt->execute([$produto['id_produto']]);
                $subs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $subList    = implode(',', $subs);
                $isCombo    = stripos($produto['nome_produto'], 'combo') !== false;
                $categoriaId = $isCombo ? 'combo' : $produto['id_categoria'];
                $nomeProdMin = strtolower($produto['nome_produto']);
                ?>
                <div
                    class="card bg-base-100 shadow-xl"
                    x-show="
                      (categoriaAtiva === 'todas' || categoriaAtiva == '<?= $categoriaId ?>')
                      && (subcategoriaAtiva === 'todas' || '<?= $subList ?>'.split(',').includes(subcategoriaAtiva.toString()))
                      && '<?= $nomeProdMin ?>'.includes(termoBusca.toLowerCase())
                    ">
                    <div class="card-body flex flex-col justify-between">
                        <div>
                            <h3 class="card-title"><?= htmlspecialchars($produto['nome_produto']) ?></h3>

                            <?php if ($produto['qtd_sabores'] > 1): ?>
                                <p class="text-sm text-gray-500 italic">
                                    Obs.: O preço será calculado pela
                                    <strong>
                                        <?= $produto['tipo_calculo_preco'] === 'media' ? 'média' : 'maior valor' ?>
                                    </strong>
                                    .
                                </p>
                            <?php else: ?>
                                <p class="text-sm text-gray-600">
                                    R$<?= number_format($produto['valor_produto'], 2, ',', '.') ?>
                                </p>
                            <?php endif ?>
                        </div>
                        <div class="card-actions mt-4">
                            <a
                                href="produto.php?id=<?= $produto['id_produto'] ?>"
                                class="btn btn-primary w-full">Fazer Pedido</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include_once 'assets/footer.php'; ?>