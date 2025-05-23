<?php
// atendimento.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once 'assets/header.php';

$dadosLoja     = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$precoBase     = floatval($dadosLoja['preco_base']);
$precoKm       = floatval($dadosLoja['preco_km']);
$enderecoLoja  = trim($dadosLoja['endereco_completo']);
$googleMapsKey = $dadosLoja['google'];
$limiteEntrega = isset($dadosLoja['limite_entrega']) ? floatval($dadosLoja['limite_entrega']) : null;
$regrasFrete = $pdo->query("SELECT * FROM tb_regras_frete WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

$entregadores = $pdo->query("
    SELECT f.id_funcionario,u.nome_usuario AS nome
      FROM tb_funcionario f
      JOIN tb_usuario u USING(id_usuario)
     WHERE f.ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$formasPgto = $pdo->query("
    SELECT id_forma,nome_pgto
      FROM tb_forma_pgto
     WHERE pagamento_ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$tiposEntrega = ['retirada' => 'Retirada na loja', 'entrega' => 'Entrega'];
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
                    </div>
                </div>
            </div>

            <div class="modal-action">
                <label for="modal-new-order" class="btn">Cancelar</label>
                <button id="desk-confirm-btn"
                    class="btn btn-primary"
                    disabled>
                    Finalizar Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Detalhe de Produto -->
    <input type="checkbox" id="modal-product-detail" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box w-full max-w-3xl">
            <h3 id="detail-prod-name" class="text-2xl font-bold mb-4 text-center"></h3>

            <!-- Sabores -->
            <div id="detail-prod-flavors" class="mb-6 hidden">
                <h4 class="font-semibold mb-2 text-gray-700 border-b pb-1">Escolha os Sabores</h4>
                <div id="subcat-filter-wrapper" class="mb-3 hidden">
                    <label class="font-semibold text-sm mb-1 block">Filtrar por subcategoria:</label>
                    <div id="flavor-subcat-buttons" class="flex flex-wrap gap-2 text-sm"></div>
                </div>
                <div id="flavor-options" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                <p class="text-sm text-gray-500 mt-2">
                    Selecione até <span id="max-flavors" class="font-semibold text-gray-700"></span> sabor(es).
                </p>
            </div>

            <!-- Adicionais -->
            <div id="detail-prod-addons" class="mb-6">
                <h4 class="font-semibold mb-2 text-gray-700 border-b pb-1">Adicionais</h4>
                <div id="addon-options" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Os grupos de adicionais serão inseridos aqui via JS -->
                </div>
            </div>

            <div class="modal-action mt-6">
                <div class="text-lg font-bold text-left md:text-left flex-1">
                    Total: <span id="valor-total-modal" class="mr-2">R$ 0,00</span>
                    <input type="number"
                        id="detail-prod-qty"
                        value="1"
                        min="1"
                        class="input input-sm input-bordered w-24" />
                </div>
                <label for="modal-product-detail" class="btn">Fechar</label>
                <button id="add-to-cart-btn" class="btn btn-primary w-full md:w-auto">
                    Adicionar ao Carrinho
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Checkout -->
    <input type="checkbox" id="modal-checkout" class="modal-toggle" />
    <div class="modal modal-bottom sm:modal-middle">
        <form id="checkout-form">
            <div class="modal-box w-full max-w-md">
                <h3 class="font-bold text-lg mb-4">Finalizar Pedido</h3>

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
                    <div class="flex gap-2">
                        <button type="button" id="btnRetiradaCheckout" class="btn btn-sm btn-primary w-1/2">Retirada</button>
                        <button type="button" id="btnEntregaCheckout" class="btn btn-sm btn-outline w-1/2">Entrega</button>
                    </div>
                </div>
                <div id="blocoEnderecoCheckout" class="hidden space-y-4 mt-3">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block font-medium mb-1">Selecione o endereço</label>
                            <select id="selectEnderecoCheckout" class="select select-bordered w-full"></select>
                        </div>
                        <button type="button" id="btnNovoEnderecoCheckout" class="btn btn-primary btn-square mt-auto">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div id="formNovoEnderecoCheckout" class="hidden mt-6 space-y-4">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block font-medium mb-1">CEP</label>
                                <input type="text" id="cep" class="input input-bordered w-full" placeholder="00000-000">
                            </div>
                            <button type="button" id="btnBuscarCep" class="btn btn-primary btn-square mt-auto">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-2/3">
                                <label class="block font-medium mb-1">Rua</label>
                                <input type="text" id="rua" class="input input-bordered w-full">
                            </div>
                            <div class="w-1/3">
                                <label class="block font-medium mb-1">Número</label>
                                <input type="text" id="numero" class="input input-bordered w-full">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-1/2">
                                <label class="block font-medium mb-1">Bairro</label>
                                <input type="text" id="bairro" class="input input-bordered w-full">
                            </div>
                            <div class="w-1/2">
                                <label class="block font-medium mb-1">Apelido</label>
                                <input type="text" id="apelido" class="input input-bordered w-full" placeholder="Casa, Trabalho…">
                            </div>
                        </div>
                        <button type="button" id="btnSalvarEndereco" class="btn btn-success w-full">Salvar Endereço</button>
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="block font-medium mb-1">Desconto (R$)</label>
                    <input type="text" id="desconto" class="input input-bordered w-full currency" placeholder="R$ 00,00">
                </div>
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Forma de Pagamento</span></label>
                    <select id="checkout-payment" class="select select-bordered" required>
                        <?php foreach ($formasPgto as $f): ?>
                            <option value="<?= $f['id_forma'] ?>"><?= htmlspecialchars($f['nome_pgto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" id="checkout-id-cliente">
                <input type="hidden" id="checkout-id-endereco">
                <input type="hidden" id="checkout-valor-frete">
                <input type="hidden" id="checkout-distancia">
                <input type="hidden" id="checkout-tipo-entrega" value="retirada">


                <div class="modal-action">
                    <div class="text-lg font-bold text-left md:text-left flex-1">
                        Total: <span id="checkout-total" class="mr-2">R$ 0,00</span></div>
                    <label for="modal-checkout" class="btn">Cancelar</label>
                    <button type="submit" class="btn btn-success">Confirmar</button>
                </div>
            </div>
        </form>
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

<div id="loading-overlay" class="fixed inset-0 bg-black/50 z-50 hidden justify-center items-center">
    <span class="loading loading-spinner loading-lg text-white"></span>
</div>
<audio id="notification-sound" src="assets/audio/notification.mp3" preload="auto"></audio>
<!-- Injeção das variáveis JS -->
<script>
    window.__entregadores__ = <?= json_encode($entregadores, JSON_UNESCAPED_UNICODE) ?>;
    window.__formasPagamento__ = <?= json_encode($formasPgto, JSON_UNESCAPED_UNICODE) ?>;
    window.__tiposEntrega__ = <?= json_encode($tiposEntrega, JSON_UNESCAPED_UNICODE) ?>;
    window.__precoBase__ = <?= $precoBase ?>;
    window.__precoKm__ = <?= $precoKm ?>;
    window.__enderecoLoja__ = <?= json_encode($enderecoLoja, JSON_UNESCAPED_UNICODE) ?>;
    window.__googleMapsKey__ = <?= json_encode($googleMapsKey, JSON_UNESCAPED_UNICODE) ?>;
    window.__limiteEntrega__ = <?= $limiteEntrega !== null ? $limiteEntrega : 'null' ?>;
    window.__regrasFrete__ = <?= json_encode($regrasFrete) ?>;
    window.__nomeLoja__ = <?= json_encode($nomeLoja) ?>;
    window.__enderecoLoja__ = <?= json_encode($enderecoLoja) ?>;
</script>


<!-- Seus scripts separados por módulos -->
<script src="assets/js/atendimento/panelAtendimento.js"></script>
<script src="assets/js/atendimento/novoPedido.js"></script>
<script src="assets/js/atendimento/carrinho.js"></script>
<script src="assets/js/atendimento/produtoDetail.js"></script>
<script src="assets/js/atendimento/checkout.js"></script>
</div>

<?php include_once 'assets/footer.php'; ?>