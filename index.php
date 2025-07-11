<?php
include_once 'assets/header.php';

// 1) categorias ativas
$categorias = $pdo
    ->query("
    SELECT
      c.*
    FROM tb_categoria c
    JOIN tb_produto   p ON p.id_categoria       = c.id_categoria
    WHERE
      c.categoria_ativa = 1
      AND p.produto_ativo = 1
      AND p.qtd_produto   <> 0
    GROUP BY c.id_categoria
    ORDER BY c.ordem_exibicao
  ")
    ->fetchAll(PDO::FETCH_ASSOC);

// 2) subcategorias ativas
$subcategorias = $pdo
    ->query("
    SELECT
      s.*
    FROM tb_subcategoria s
    JOIN tb_subcategoria_produto sp ON sp.id_subcategoria = s.id_subcategoria
    JOIN tb_produto             p  ON p.id_produto      = sp.id_produto
    WHERE
      s.subcategoria_ativa = 1
      AND p.produto_ativo   = 1
      AND p.qtd_produto     <> 0
    GROUP BY s.id_subcategoria
    ORDER BY s.nome_subcategoria
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

// Ordena para que produtos com múltiplos sabores (“qtd_sabores > 1”) venham primeiro,
// e, dentro de cada grupo, mantém ordem alfabética por nome.
usort($produtos, function ($a, $b) {
    $aMulti = $a['qtd_sabores'] > 1 ? 1 : 0;
    $bMulti = $b['qtd_sabores'] > 1 ? 1 : 0;
    if ($aMulti !== $bMulti) {
        return $bMulti - $aMulti;
    }
    return strcmp($a['nome_produto'], $b['nome_produto']);
});

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
$promoNames = array_column($promocoes, 'nome_produto');

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

// 6) horários de atendimento ativos
$stmtAll = $pdo->query("
    SELECT dia_semana, hora_abertura, hora_fechamento
      FROM tb_horario_atendimento
     WHERE ativo = 1
     ORDER BY
       CASE dia_semana
         WHEN 'segunda' THEN 1
         WHEN 'terça'   THEN 2
         WHEN 'quarta'  THEN 3
         WHEN 'quinta'  THEN 4
         WHEN 'sexta'   THEN 5
         WHEN 'sábado'  THEN 6
         WHEN 'domingo' THEN 7
       END
");
$horarios = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// mapeia ID => nome de categoria para breadcrumbs
$catNamesMap = array_column($categorias, 'nome_categoria', 'id_categoria');
?>
<?php if (!$aberta): ?>
    <!-- Modal Loja Fechada -->
    <div id="modal-loja-fechada"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg max-w-sm text-center">
            <h2 class="text-xl font-bold mb-4">Loja Fechada</h2>
            <p class="mb-4">No momento estamos fechados. Por favor, volte no horário de atendimento.</p>
            <button id="btn-modal-close" class="btn">OK</button>
        </div>
    </div>
    <script>
        document.getElementById('btn-modal-close')
            .addEventListener('click', () => {
                document.getElementById('modal-loja-fechada').remove();
            });
    </script>
<?php endif; ?>

<section class="py-3 bg-accent text-white text-center">
    <p class="font-bold text-lg"><?= $statusLoja ?></p>
    <button
        id="btn-ver-horario"
        class="text-sm underline hover:text-white/90 bg-transparent border-none p-0">
        Ver horário de atendimento
    </button>
</section>

<section class="px-4 mt-2" x-data="{ mostrarPromocoes: false }">
    <?php if (count($promocoes) > 0 || count($brindes) > 0): ?>
        <button
            @click="mostrarPromocoes = !mostrarPromocoes"
            class="btn btn-sm btn-outline btn-accent mb-2">
            Ver promoções do dia
        </button>
        <div x-show="mostrarPromocoes" x-transition class="bg-base-100 shadow p-4 rounded-lg">
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
    x-data='{ 
      etapa: "categorias", 
      categoriaAtiva: null, 
      subcategoriaAtiva: "todas", 
      termoBusca: "", 
      categoriaIdToName: <?= json_encode($catNamesMap, JSON_HEX_APOS | JSON_HEX_QUOT) ?> 
    }'>
    <div class="container mx-auto px-4">

        <!-- CATEGORIAS -->
        <template x-if="etapa === 'categorias'" x-transition>
            <div>
                <h2 class="text-3xl font-bold text-center mb-6">Escolha uma Categoria</h2>
                <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    <?php foreach ($categorias as $cat): ?>
                        <div
                            class="card bg-base-100 shadow-md rounded-lg p-4 cursor-pointer hover:shadow-xl transition"
                            @click="
                              categoriaAtiva = '<?= $cat['id_categoria'] ?>';
                              etapa = 'produtos';
                              history.pushState(null, '', '?cat=<?= $cat['id_categoria'] ?>');
                            ">
                            <div class="card-body flex flex-col items-center">
                                <h3 class="card-title text-center mb-2">
                                    <?= htmlspecialchars($cat['nome_categoria']) ?>
                                </h3>
                                <!-- imagem opcional -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </template>

        <!-- PRODUTOS -->
        <template x-if="etapa === 'produtos'" x-transition>
            <div>

                <!-- Breadcrumbs & Voltar -->
                <div class="mb-4 flex items-center">
                    <button
                        class="btn btn-sm btn-outline mr-4"
                        @click="
                          etapa = 'categorias';
                          categoriaAtiva = null;
                          subcategoriaAtiva = 'todas';
                          termoBusca = '';
                          history.pushState(null, '', 'shop.php');
                        ">
                        ← Categorias
                    </button>
                    <nav class="text-sm text-gray-600">
                        Você está em:
                        <span class="font-semibold" x-text="categoriaIdToName[categoriaAtiva]"></span>
                    </nav>
                </div>

                <!-- Contador de resultados -->
                <div class="mb-4 text-center text-sm text-gray-700">
                    <span
                        x-text="`${Array.from($refs.produtosList.children)
                        .filter(el => el.style.display !== 'none').length} produtos encontrados`">
                    </span>
                    <button
                        class="ml-4 text-xs underline"
                        @click="
                          subcategoriaAtiva = 'todas';
                          termoBusca = '';
                        ">
                        Limpar filtros
                    </button>
                </div>

                <!-- Busca -->
                <div class="mb-6 flex justify-center">
                    <input
                        type="text"
                        x-model="termoBusca"
                        class="input input-bordered w-full max-w-md"
                        placeholder="Buscar por nome..." />
                </div>

                <!-- Subcategorias -->
                <div class="mb-4 overflow-x-auto">
                    <div class="flex space-x-2 w-max min-w-full px-2">
                        <button
                            @click="subcategoriaAtiva = 'todas'"
                            :class="subcategoriaAtiva === 'todas' ? 'btn-primary' : 'btn-outline'"
                            class="btn btn-sm">
                            Todas
                        </button>
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
                <div class="flex justify-center mb-6">
                    <span class="text-sm text-secondary">
                        ( Deslize para o lado para ver as subcategorias )
                    </span>
                </div>

                <!-- Grid de Produtos -->
                <div
                    class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3"
                    x-ref="produtosList">
                    <?php foreach ($produtos as $produto): ?>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT id_subcategoria
                              FROM tb_subcategoria_produto
                             WHERE id_produto = ?
                        ");
                        $stmt->execute([$produto['id_produto']]);
                        $subs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $subList     = implode(',', $subs);
                        $isCombo     = stripos($produto['nome_produto'], 'combo') !== false;
                        $categoriaId = $isCombo ? 'combo' : $produto['id_categoria'];
                        $nomeProdMin = strtolower($produto['nome_produto']);
                        ?>
                        <div
                            class="relative card bg-base-100 shadow-md rounded-lg overflow-hidden p-2"
                            x-show="
                                categoriaAtiva == '<?= $categoriaId ?>'
                                && (subcategoriaAtiva === 'todas' || '<?= $subList ?>'.split(',').includes(subcategoriaAtiva.toString()))
                                && '<?= $nomeProdMin ?>'.includes(termoBusca.toLowerCase())
                            "
                            x-transition>

                            <div class="card-body flex flex-col p-4 space-y-3">
                                <div class="flex justify-between items-center w-full">
                                    <h3 class="card-title mb-0 leading-tight">
                                        <?= htmlspecialchars($produto['nome_produto']) ?>
                                    </h3>
                                    <span class="text-sm text-gray-600 mb-0 leading-tight">
                                        <?php if ($produto['qtd_sabores'] > 1): ?>
                                            <b>(valor <?= $produto['tipo_calculo_preco'] ?>)</b>
                                        <?php else: ?>
                                            R$ <?= number_format($produto['valor_produto'], 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <?php if (!empty($produto['descricao_produto'])): ?>
                                    <p class="text-sm text-gray-600 overflow-hidden line-clamp-3">
                                        <?= htmlspecialchars($produto['descricao_produto']) ?>
                                    </p>
                                <?php endif; ?>

                                <a
                                    href="produto.php?id=<?= $produto['id_produto'] ?>"
                                    class="btn btn-primary w-full mt-auto"
                                    <?= $aberta ? '' : 'disabled' ?>>
                                    Fazer Pedido
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </template>

    </div>
</section>

<!-- Modal Horário -->
<div
    id="modal-horario"
    style="display:none"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
        <h2 class="text-2xl font-bold mb-4">Hor&nbsp;rio de Atendimento</h2>
        <table class="table table-zebra w-full mb-4">
            <thead>
                <tr>
                    <th>Dia</th>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horarios as $h): ?>
                    <?php $isRegra = ($h['dia_semana'] === $diaRegra); ?>
                    <tr class="<?= $isRegra ? 'font-bold' : '' ?>">
                        <td><?= ucfirst($h['dia_semana']) ?></td>
                        <td><?= date('H:i', strtotime($h['hora_abertura'])) ?></td>
                        <td><?= date('H:i', strtotime($h['hora_fechamento'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-primary btn-close w-full">Fechar</button>
    </div>
</div>

<script>
    $(function() {
        $('#btn-ver-horario').on('click', function(e) {
            e.preventDefault();
            $('#modal-horario').fadeIn(200);
        });
        $('#modal-horario .btn-close, #modal-horario').on('click', function(e) {
            if (e.target.id === 'modal-horario' || $(this).hasClass('btn-close')) {
                $('#modal-horario').fadeOut(200);
            }
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>