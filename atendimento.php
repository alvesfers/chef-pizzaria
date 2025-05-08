<?php
// atendimento.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'assets/header.php';  // já faz session_start() e define $pdo

// === Dados da loja ===
$dadosLoja        = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$precoBase        = floatval($dadosLoja['preco_base']);
$precoKm          = floatval($dadosLoja['preco_km']);
$enderecoLoja     = trim($dadosLoja['endereco_completo']);
$googleMapsKey    = $dadosLoja['google'];
$limiteEntrega    = isset($dadosLoja['limite_entrega'])
    ? floatval($dadosLoja['limite_entrega'])
    : null;

// --- Funções auxiliares ---
function getFlavorsAssoc(PDO $pdo): array
{
    $multi = $pdo->query("
        SELECT id_produto,id_categoria,qtd_sabores
          FROM tb_produto
         WHERE produto_ativo=1 AND qtd_sabores>1
    ")->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($multi as $p) {
        $stmt = $pdo->prepare("
            SELECT id_produto,
                   nome_produto AS nome,
                   valor_produto AS preco
              FROM tb_produto
             WHERE produto_ativo=1
               AND id_categoria = ?
               AND id_produto <> ?
        ");
        $stmt->execute([$p['id_categoria'], $p['id_produto']]);
        $out[$p['id_produto']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $out;
}

function getAddonsAssoc(PDO $pdo): array
{
    $rows = $pdo->query("
        SELECT pt.id_produto,
               a.id_adicional,
               a.nome_adicional AS nome,
               a.valor_adicional AS preco
          FROM tb_produto_tipo_adicional pt
          JOIN tb_adicional a
            ON a.id_tipo_adicional = pt.id_tipo_adicional
           AND a.adicional_ativo = 1
         WHERE pt.id_produto IN (
               SELECT id_produto FROM tb_produto WHERE produto_ativo=1
         )
    ")->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[$r['id_produto']][] = $r;
    }
    return $out;
}

// --- Dados para o front ---
$entregadores   = $pdo->query("
    SELECT f.id_funcionario,u.nome_usuario AS nome
      FROM tb_funcionario f
      JOIN tb_usuario u USING(id_usuario)
     WHERE f.ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$formasPgto     = $pdo->query("
    SELECT id_forma,nome_pgto
      FROM tb_forma_pgto
     WHERE pagamento_ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$tiposEntrega   = ['retirada' => 'Retirada na loja', 'entrega' => 'Entrega'];

$categorias     = $pdo->query("
    SELECT id_categoria,nome_categoria AS nome
      FROM tb_categoria
     WHERE categoria_ativa=1
     ORDER BY ordem_exibicao
")->fetchAll(PDO::FETCH_ASSOC);

$subcats        = $pdo->query("
    SELECT sc.id_subcategoria,sc.nome_subcategoria AS nome,scc.id_categoria
      FROM tb_subcategoria sc
      JOIN tb_subcategoria_categoria scc USING(id_subcategoria)
     WHERE sc.subcategoria_ativa=1
")->fetchAll(PDO::FETCH_ASSOC);

$produtos       = $pdo->query("
    SELECT id_produto,nome_produto AS nome,
           id_categoria,valor_produto,qtd_sabores
      FROM tb_produto
     WHERE produto_ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$flavorsAssoc   = getFlavorsAssoc($pdo);
$addonsAssoc    = getAddonsAssoc($pdo);

// --- Monta a lista de “tipos de adicionais por produto” igual ao produto.php ---
$stmtTipos = $pdo->query("
    SELECT pta.id_produto,
           ta.id_tipo_adicional,
           ta.nome_tipo_adicional,
           pta.max_inclusos
      FROM tb_produto_tipo_adicional pta
      JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
     WHERE pta.id_produto IN (
           SELECT id_produto FROM tb_produto WHERE produto_ativo=1
     )
");
$tiposPorProduto = [];
while ($row = $stmtTipos->fetch(PDO::FETCH_ASSOC)) {
    $tiposPorProduto[$row['id_produto']][] = $row;
}
$adicionaisPorTipo = [];
foreach ($tiposPorProduto as $idProduto => $grupos) {
    foreach ($grupos as $tipo) {
        $stmtAdd = $pdo->prepare("
            SELECT id_adicional,
                   nome_adicional AS nome,
                   valor_adicional AS preco
              FROM tb_adicional
             WHERE id_tipo_adicional = ?
               AND adicional_ativo = 1
        ");
        $stmtAdd->execute([$tipo['id_tipo_adicional']]);
        $adicionaisPorTipo[] = [
            'tipo'       => $tipo,
            'adicionais' => $stmtAdd->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
}
?>

<div class="container mx-auto p-4 mt-5">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Painel de Atendimento</h1>
        <!-- Botão de Novo Pedido -->
        <button id="new-order-btn" class="btn btn-primary">Novo Pedido</button>
    </div>

    <audio id="notification-sound" src="/assets/audio/notification.mp3" preload="auto"></audio>

    <!-- Seções por cada status -->
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Pendente</h2>
        <div id="orders-pendente" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Aceito</h2>
        <div id="orders-aceito" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Em Preparo</h2>
        <div id="orders-em_preparo" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Em Entrega</h2>
        <div id="orders-em_entrega" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Finalizado</h2>
        <div id="orders-finalizado" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-2">Cancelado</h2>
        <div id="orders-cancelado" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>

    <!-- Modal Novo Pedido -->
    <input type="checkbox" id="modal-new-order" class="modal-toggle" />
    <div class="modal modal-bottom sm:modal-middle">
        <div class="modal-box w-full max-w-5xl">
            <h3 class="font-bold text-lg mb-4">Criar Novo Pedido</h3>
            <div class="flex space-x-4">
                <!-- filtros e lista de produtos -->
                <div class="w-2/3">
                    <div class="flex space-x-2 mb-2">
                        <select id="filter-category" class="select select-bordered">
                            <option value="">Todas Categorias</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-subcategory" class="select select-bordered">
                            <option value="">Todas Subcategorias</option>
                            <?php foreach ($subcats as $s): ?>
                                <option value="<?= $s['id_subcategoria'] ?>"
                                    data-cat="<?= $s['id_categoria'] ?>">
                                    <?= htmlspecialchars($s['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text"
                            id="filter-search"
                            placeholder="Buscar..."
                            class="input input-bordered flex-grow" />
                    </div>
                    <div id="product-list"
                        class="grid grid-cols-2 md:grid-cols-3 gap-4 h-96 overflow-auto"></div>
                </div>

                <!-- carrinho + cliente -->
                <div class="w-1/3 p-2 rounded flex flex-col">
                    <h4 class="font-semibold mb-2">Carrinho</h4>
                    <div id="cart-items"
                        class="space-y-2 flex-grow overflow-auto h-96 bg-gray-50">
                    </div>
                    <div class="mt-4">
                        <p class="text-lg">
                            <strong>Total: </strong><span id="cart-total">R$ 0,00</span>
                        </p>
                    </div>

                    <!-- seção cliente -->
                    <div class="mt-4">
                        <!-- telefone + botão buscar -->
                        <div class="flex items-center space-x-2 mb-3">
                            <input type="text"
                                id="telefone"
                                class="input input-bordered flex-grow"
                                placeholder="(xx) xxxxx-xxxx" />
                            <button type="button"
                                id="telefone-search"
                                class="btn btn-square"
                                title="Buscar cliente">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <!-- nome do cliente (aparece após a busca) -->
                        <div id="desk-name-group" class="mb-4">
                            <input type="text"
                                id="desk-name"
                                class="input input-bordered w-full"
                                placeholder="Nome do Cliente"
                                disabled />
                        </div>

                        <!-- botão de finalizar pedido -->
                        <button id="desk-confirm-btn"
                            class="btn btn-primary w-full"
                            disabled>
                            Finalizar Pedido
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-action">
                <label for="modal-new-order" class="btn">Cancelar</label>
            </div>
        </div>
    </div>

    <!-- Modal Detalhe de Produto -->
    <input type="checkbox" id="modal-product-detail" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box w-3/4 max-w-3xl">
            <h3 id="detail-prod-name" class="font-bold text-lg mb-4"></h3>

            <!-- Sabores (só aparece se qtd_sabores > 1) -->
            <div id="detail-prod-flavors" class="mb-4 hidden">
                <h4 class="font-semibold mb-2">Sabores</h4>
                <div id="flavor-options" class="grid grid-cols-2 gap-4"></div>
                <p class="text-sm text-gray-600 mt-1">
                    Selecione até <span id="max-flavors"></span> sabor(es)
                </p>
            </div>

            <!-- Adicionais -->
            <div id="detail-prod-addons" class="mb-4">
                <h4 class="font-semibold mb-2">Adicionais</h4>
                <div id="addon-options"
                    class="grid grid-cols-2 gap-4 md:grid-cols-4"></div>
            </div>

            <div class="flex items-center space-x-2">
                <input type="number"
                    id="detail-prod-qty"
                    value="1" min="1"
                    class="input input-bordered w-20" />
                <button id="add-to-cart-btn" class="btn btn-primary">
                    Adicionar
                </button>
            </div>

            <div class="modal-action mt-4">
                <label for="modal-product-detail" class="btn">Fechar</label>
            </div>
        </div>
    </div>

    <!-- Modal Checkout -->
    <input type="checkbox" id="modal-checkout" class="modal-toggle" />
    <div class="modal modal-bottom sm:modal-middle">
        <div class="modal-box w-full max-w-md">
            <h3 class="font-bold text-lg mb-4">Finalizar Pedido</h3>
            <form id="checkout-form">
                <div class="form-control mb-2">
                    <label class="label"><span class="label-text">Nome do Cliente</span></label>
                    <input type="text" id="checkout-name" placeholder="Nome" class="input input-bordered" required />
                </div>
                <div class="form-control mb-2">
                    <label class="label"><span class="label-text">Telefone</span></label>
                    <input type="text" id="checkout-phone" placeholder="(xx)xxxxx-xxxx" class="input input-bordered" required />
                </div>
                <div class="form-control mb-2">
                    <label class="label"><span class="label-text">Tipo de Entrega</span></label>
                    <select id="checkout-delivery-type" class="select select-bordered" required>
                        <?php foreach ($tiposEntrega as $k => $l): ?>
                            <option value="<?= $k ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Forma de Pagamento</span></label>
                    <select id="checkout-payment" class="select select-bordered" required>
                        <?php foreach ($formasPgto as $f): ?>
                            <option value="<?= $f['id_forma'] ?>"><?= htmlspecialchars($f['nome_pgto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-full">Confirmar</button>
            </form>
            <div class="modal-action">
                <label for="modal-checkout" class="btn">Cancelar</label>
            </div>
        </div>
    </div>

    <!-- Modal Detalhe do Pedido -->
    <input type="checkbox" id="modal-order-detail" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <h3 class="font-bold text-lg mb-4">
                Detalhes do Pedido #<span id="order-detail-id"></span>
            </h3>
            <div id="order-detail-content" class="space-y-2"></div>
            <div class="modal-action">
                <label for="modal-order-detail" class="btn">Fechar</label>
            </div>
        </div>
    </div>

</div>

<!-- Injeção das variáveis JS -->
<script>
    window.__entregadores__ = <?= json_encode($entregadores,        JSON_UNESCAPED_UNICODE) ?>;
    window.__produtos__ = <?= json_encode($produtos,            JSON_UNESCAPED_UNICODE) ?>;
    window.__flavorsAssoc__ = <?= json_encode($flavorsAssoc,        JSON_UNESCAPED_UNICODE) ?>;
    window.__addonsAssoc__ = <?= json_encode($addonsAssoc,         JSON_UNESCAPED_UNICODE) ?>;
    window.__tiposAdicionais__ = <?= json_encode($adicionaisPorTipo,   JSON_UNESCAPED_UNICODE) ?>;
    window.__precoBase__ = <?= $precoBase ?>;
    window.__precoKm__ = <?= $precoKm ?>;
    window.__enderecoLoja__ = <?= json_encode($enderecoLoja,        JSON_UNESCAPED_UNICODE) ?>;
    window.__googleMapsKey__ = <?= json_encode($googleMapsKey,       JSON_UNESCAPED_UNICODE) ?>;
    window.__limiteEntrega__ = <?= $limiteEntrega !== null ? $limiteEntrega : 'null' ?>;
</script>

<!-- Seus scripts separados por módulos -->
<script src="assets/js/panelAtendimento.js"></script>
<script src="assets/js/novoPedido.js"></script>
<script src="assets/js/carrinho.js"></script>
<script src="assets/js/produtoDetail.js"></script>
<script src="assets/js/checkout.js"></script>
</div>

<?php include_once 'assets/footer.php'; ?>