<?php
// atendimento.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'assets/header.php';  // já faz session_start() e define $pdo

$dadosLoja        = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$precoBase        = floatval($dadosLoja['preco_base']);
$precoKm          = floatval($dadosLoja['preco_km']);
$enderecoLoja     = trim($dadosLoja['endereco_completo']);
$googleMapsApiKey = $dadosLoja['google'];
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

$categorias = $pdo->query("
    SELECT id_categoria,nome_categoria AS nome
      FROM tb_categoria
     WHERE categoria_ativa=1
     ORDER BY ordem_exibicao
")->fetchAll(PDO::FETCH_ASSOC);

$subcats = $pdo->query("
    SELECT sc.id_subcategoria,sc.nome_subcategoria AS nome,scc.id_categoria
      FROM tb_subcategoria sc
      JOIN tb_subcategoria_categoria scc USING(id_subcategoria)
     WHERE sc.subcategoria_ativa=1
")->fetchAll(PDO::FETCH_ASSOC);

$produtos     = $pdo->query("
    SELECT id_produto,nome_produto AS nome,
           id_categoria,valor_produto,qtd_sabores
      FROM tb_produto
     WHERE produto_ativo=1
")->fetchAll(PDO::FETCH_ASSOC);

$flavorsAssoc = getFlavorsAssoc($pdo);
$addonsAssoc  = getAddonsAssoc($pdo);
?>

<div class="container mx-auto p-4">
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
                                <option value="<?= $s['id_subcategoria'] ?>" data-cat="<?= $s['id_categoria'] ?>">
                                    <?= htmlspecialchars($s['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="filter-search" placeholder="Buscar..." class="input input-bordered flex-grow" />
                    </div>
                    <div id="product-list" class="grid grid-cols-2 md:grid-cols-3 gap-4 h-96 overflow-auto"></div>
                </div>

                <!-- carrinho + cliente -->
                <div class="w-1/3 p-2 rounded flex flex-col">
                    <h4 class="font-semibold mb-2">Carrinho</h4>
                    <div id="cart-items" class="space-y-2 flex-grow overflow-auto h-96 bg-gray-50">

                    </div>
                    <div class="mt-4">
                        <p class="text-lg"><strong>Total: </strong><span id="cart-total">R$ 0,00</span></p>
                    </div>
                    <!-- seção cliente -->
                    <div class="mt-4">
                        <!-- telefone + botão buscar -->
                        <div class="flex items-center space-x-2 mb-3">
                            <input
                                type="text"
                                id="desk-phone"
                                class="input input-bordered flex-grow"
                                placeholder="Telefone (xx) xxxxx-xxxx" />
                            <button
                                type="button"
                                id="desk-phone-search"
                                class="btn btn-square"
                                title="Buscar cliente">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <!-- nome do cliente (aparece após a busca) -->
                        <div id="desk-name-group" class="mb-4 hidden">
                            <input
                                type="text"
                                id="desk-name"
                                class="input input-bordered w-full"
                                placeholder="Nome do Cliente" />
                        </div>
                        <!-- botão de finalizar pedido -->
                        <button
                            id="desk-confirm-btn"
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
            <div id="detail-prod-flavors" class="mb-4"></div>
            <div id="detail-prod-addons" class="mb-4"></div>
            <div class="flex items-center space-x-2">
                <input type="number" id="detail-prod-qty" value="1" min="1" class="input input-bordered w-20" />
                <button id="add-to-cart-btn" class="btn btn-primary">Adicionar</button>
            </div>
            <div class="modal-action">
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

</div>

<script>
    $(function() {
        // ======= Globais =======
        const entregadores = <?= json_encode($entregadores, JSON_UNESCAPED_UNICODE) ?>;
        const STATUS_LABEL = {
            pendente: 'Pendente',
            aceito: 'Aceito',
            em_preparo: 'Em Preparo',
            em_entrega: 'Em Entrega',
            finalizado: 'Finalizado',
            cancelado: 'Cancelado'
        };
        const products = <?= json_encode($produtos, JSON_UNESCAPED_UNICODE) ?>;
        const flavorsById = <?= json_encode($flavorsAssoc, JSON_UNESCAPED_UNICODE) ?>;
        const addonsById = <?= json_encode($addonsAssoc, JSON_UNESCAPED_UNICODE) ?>;
        const precoBase = <?= $precoBase ?>;
        const precoKm = <?= $precoKm ?>;
        const enderecoLoja = "<?= addslashes($enderecoLoja) ?>";
        const googleMapsApiKey = "<?= $googleMapsApiKey ?>";
        const limiteEntrega = <?= $limiteEntrega !== null ? $limiteEntrega : 'null' ?>;
        let cart = [];

        // ======= Abrir modal Novo Pedido =======
        $('#new-order-btn').on('click', () => {
            $('#modal-new-order').prop('checked', true);
            renderCart();
        });

        // ======= Cliente / Endereço no balcão =======
        // injeta campos (podem vir ocultos no HTML também)
        $('#desk-customer-section').html(`
    <div class="mb-4">
      <label class="block font-medium mb-1">Telefone</label>
      <div class="flex gap-2">
        <input type="text" id="desk-phone" class="input input-bordered flex-1" placeholder="(xx) xxxxx-xxxx">
        <button id="desk-phone-btn" class="btn btn-sm btn-outline">Buscar</button>
      </div>
    </div>
    <div id="desk-name-group" class="mb-4 hidden">
      <label class="block font-medium mb-1">Nome</label>
      <input type="text" id="desk-name" class="input input-bordered w-full" placeholder="Nome do Cliente">
    </div>
    <div id="desk-address-group" class="mb-4 hidden">
      <label class="block font-medium mb-1">Endereço</label>
      <select id="desk-address-select" class="select select-bordered w-full mb-2"></select>
      <button type="button" id="desk-new-address-btn" class="btn btn-sm btn-outline">Novo Endereço</button>
      <div id="desk-new-address-form" class="mt-2 hidden space-y-2">
        <input type="text" id="desk-cep" class="input input-bordered w-full" placeholder="CEP">
        <input type="text" id="desk-rua" class="input input-bordered w-full" placeholder="Rua">
        <input type="text" id="desk-numero" class="input input-bordered w-full" placeholder="Número">
        <button type="button" id="desk-save-address-btn" class="btn btn-sm btn-success w-full">Salvar Endereço</button>
      </div>
    </div>
    <div id="desk-summary" class="mb-4 hidden">
      <p>Subtotal: R$ <span id="desk-subtotal">0,00</span></p>
      <p>Frete:    R$ <span id="desk-frete">0,00</span></p>
      <p>Total:    R$ <span id="desk-total">0,00</span></p>
    </div>
    <button id="desk-confirm-btn" class="btn btn-primary w-full" disabled>Finalizar Pedido</button>
  `);

        // busca usuário e endereços pelo botão
        $('#desk-phone-btn').on('click', function() {
            const phone = $('#desk-phone').val().replace(/\D/g, '');
            if (phone.length < 10) return Swal.fire('Erro', 'Telefone inválido', 'error');
            $.ajax({
                url: 'crud/teste.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'get_by_phone',
                    phone
                }),
                success(res) {
                    if (res.exists) {
                        window.deskUserId = res.id;
                        $('#desk-name-group').addClass('hidden');
                        $('#desk-name').val(res.name);
                    } else {
                        window.deskUserId = null;
                        $('#desk-name-group').removeClass('hidden');
                        $('#desk-name').val('');
                    }
                    loadAddresses(phone);
                }
            });
        });

        function loadAddresses(phone) {
            $.ajax({
                url: 'crud/crud_endereco.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'get_by_phone',
                    phone
                }),
                success(res) {
                    const $sel = $('#desk-address-select').empty();
                    if (res.addresses.length) {
                        res.addresses.forEach(a => {
                            $sel.append(`<option value="${a.id_endereco}">${a.rua}, ${a.numero} - ${a.bairro}</option>`);
                        });
                        $('#desk-new-address-form').addClass('hidden');
                        $('#desk-address-group').removeClass('hidden');
                        $('#desk-confirm-btn').prop('disabled', false);
                        calculateFrete();
                    } else {
                        $('#desk-new-address-form').removeClass('hidden');
                        $('#desk-address-group').removeClass('hidden');
                        $('#desk-confirm-btn').prop('disabled', true);
                    }
                }
            });
        }

        $('#desk-new-address-btn').on('click', () => {
            $('#desk-new-address-form').toggleClass('hidden');
        });

        $('#desk-save-address-btn').on('click', function() {
            const data = {
                action: 'create',
                id_user: window.deskUserId,
                cep: $('#desk-cep').val(),
                rua: $('#desk-rua').val(),
                numero: $('#desk-numero').val()
            };
            $.ajax({
                url: 'crud/crud_endereco.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success(res) {
                    if (res.status === 'ok') loadAddresses($('#desk-phone').val().replace(/\D/g, ''));
                    else Swal.fire('Erro', res.mensagem, 'error');
                }
            });
        });

        $('#desk-address-select').on('change', calculateFrete);

        function arredondarFrete(v) {
            const i = Math.floor(v),
                d = v - i;
            if (d <= 0.25) return i;
            if (d <= 0.75) return i + 0.5;
            return i + 1;
        }

        function calculateFrete() {
            const addr = $('#desk-address-select option:selected').text() + ', Brasil';
            const url = `https://maps.googleapis.com/maps/api/distancematrix/json?units=metric
      &origins=${encodeURIComponent(enderecoLoja)}
      &destinations=${encodeURIComponent(addr)}
      &key=${googleMapsApiKey}`.replace(/\s+/g, '');
            $.getJSON('/proxys/proxy_google.php?url=' + encodeURIComponent(url), resp => {
                const km = resp.rows[0].elements[0].distance.value / 1000;
                if (limiteEntrega !== null && km > limiteEntrega) {
                    return Swal.fire('Aviso', 'Fora da área de entrega', 'warning');
                }
                let frete = precoBase + precoKm * km;
                frete = Math.max(0, arredondarFrete(frete));
                const subtotal = cart.reduce((s, it) => s + it.qty * it.price, 0);
                $('#desk-subtotal').text(subtotal.toFixed(2).replace('.', ','));
                $('#desk-frete').text(frete.toFixed(2).replace('.', ','));
                $('#desk-total').text((subtotal + frete).toFixed(2).replace('.', ','));
                $('#desk-summary').removeClass('hidden');
                $('#desk-confirm-btn').prop('disabled', false);
                window.deskFrete = frete;
            });
        }

        $('#desk-confirm-btn').on('click', function() {
            const payload = {
                action: 'criar_pedido_balcao',
                nome_cliente: $('#desk-name').val() || $('#desk-name').text(),
                telefone_cliente: $('#desk-phone').val(),
                tipo_entrega: $('#checkout-delivery-type').val(),
                forma_pagamento: $('#checkout-payment').val(),
                valor_frete: window.deskFrete || 0,
                id_endereco: $('#desk-address-select').val(),
                items: cart
            };
            $.ajax({
                url: 'crud/crud_pedido.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success(r) {
                    if (r.status === 'ok') {
                        Swal.fire('Sucesso', 'Pedido criado!', 'success');
                        $('#modal-new-order').prop('checked', false);
                        cart = [];
                        renderCart();
                        fetchAndRender();
                    } else {
                        Swal.fire('Erro', r.mensagem, 'error');
                    }
                }
            });
        });

        // ======= Produtos & Carrinho =======
        function filterProducts() {
            const cat = $('#filter-category').val(),
                sub = $('#filter-subcategory').val(),
                txt = $('#filter-search').val().toLowerCase();
            const lista = products.filter(p => {
                const ok1 = !cat || p.id_categoria == cat;
                const catOfSub = $('#filter-subcategory option:selected').data('cat');
                const ok2 = !sub || catOfSub == p.id_categoria;
                const ok3 = !txt || p.nome.toLowerCase().includes(txt);
                return ok1 && ok2 && ok3;
            });
            const $pl = $('#product-list').empty();
            lista.forEach(p => {
                $pl.append(`
        <div class="card cursor-pointer hover:shadow-lg product-card" data-id="${p.id_produto}">
          <div class="card-body">
            <h4 class="card-title">${p.nome}</h4>
            <p>R$ ${parseFloat(p.valor_produto).toFixed(2)}</p>
          </div>
        </div>
      `);
            });
        }

        function renderCart() {
            const $ci = $('#cart-items').empty();
            let tot = 0;
            cart.forEach(it => {
                $ci.append(`<div>${it.qty}× ${it.prod.nome} — R$ ${it.price.toFixed(2)}</div>`);
                tot += it.qty * it.price;
            });
            $('#cart-total').text(`R$ ${tot.toFixed(2)}`);
        }

        $('#filter-category,#filter-subcategory').on('change', filterProducts);
        $('#filter-search').on('input', filterProducts);

        $('#product-list').on('click', '.product-card', function() {
            const id = $(this).data('id'),
                prod = products.find(x => x.id_produto == id);
            $('#detail-prod-name').text(prod.nome).data('id', id);
            const $df = $('#detail-prod-flavors').empty();
            if (prod.qtd_sabores > 1 && flavorsById[id]) {
                flavorsById[id].forEach(f => {
                    $df.append(`<label><input type="radio" name="flavor" value="${f.id_produto}"> ${f.nome} (+R$${parseFloat(f.preco).toFixed(2)})</label><br>`);
                });
            }
            const $da = $('#detail-prod-addons').empty();
            (addonsById[id] || []).forEach(a => {
                $da.append(`<label><input type="checkbox" name="addon" value="${a.id_adicional}"> ${a.nome} (+R$${parseFloat(a.preco).toFixed(2)})</label><br>`);
            });
            $('#detail-prod-qty').val(1);
            $('#modal-product-detail').prop('checked', true);
        });

        $('#add-to-cart-btn').on('click', function() {
            const id = $('#detail-prod-name').data('id'),
                prod = products.find(x => x.id_produto == id),
                qty = +$('#detail-prod-qty').val();
            let price = parseFloat(prod.valor_produto) || 0;
            const flav = $('input[name="flavor"]:checked').val();
            if (flav) {
                price = parseFloat(flavorsById[id].find(x => x.id_produto == flav).preco) || price;
            }
            const adds = $('input[name="addon"]:checked').map((i, cb) => +cb.value).get();
            adds.forEach(aid => {
                price += parseFloat(addonsById[id].find(x => x.id_adicional == aid).preco) || 0;
            });
            cart.push({
                prod,
                qty,
                flavorId: flav,
                addonsIds: adds,
                price
            });
            renderCart();
            $('#modal-product-detail').prop('checked', false);
        });

        $('#open-checkout-btn').on('click', () => $(' #modal-checkout').prop('checked', true));

        $('#checkout-form').on('submit', function(e) {
            e.preventDefault();
            const data = {
                action: 'criar_pedido_balcao',
                nome_cliente: $('#checkout-name').val(),
                telefone_cliente: $('#checkout-phone').val(),
                tipo_entrega: $('#checkout-delivery-type').val(),
                forma_pagamento: $('#checkout-payment').val(),
                items: cart
            };
            $.ajax({
                url: 'crud/crud_pedido.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success(r) {
                    if (r.status === 'ok') {
                        Swal.fire('Sucesso', 'Pedido criado!', 'success');
                        $('#modal-checkout,#modal-new-order').prop('checked', false);
                        cart = [];
                        renderCart();
                        fetchAndRender();
                    } else Swal.fire('Erro', r.mensagem, 'error');
                },
                error() {
                    Swal.fire('Erro', 'Falha de rede', 'error')
                }
            });
        });

        // ======= Painel de Atendimento =======
        function renderCard(p) {
            const phone = (p.telefone_cliente || '').replace(/\D/g, '');
            let statusOpts = '',
                delivOpts = `<option value="">— Selecione —</option>`;
            for (let k in STATUS_LABEL) {
                statusOpts += `<option value="${k}" ${p.status_pedido===k?'selected':''}>${STATUS_LABEL[k]}</option>`;
            }
            entregadores.forEach(e => {
                delivOpts += `<option value="${e.id_funcionario}" ${p.id_entregador==e.id_funcionario?'selected':''}>${e.nome}</option>`;
            });
            return `
      <div class="card bg-white shadow p-4" data-id="${p.id_pedido}" data-phone="${phone}">
        <h3 class="font-bold">Pedido #${p.id_pedido}</h3>
        <p><strong>Cliente:</strong> ${p.cliente}</p>
        <p><strong>Total:</strong> R$ ${parseFloat(p.valor_total).toFixed(2)}</p>
        <p><strong>Criado em:</strong> ${new Date(p.criado_em).toLocaleString('pt-BR')}</p>
        <div class="mt-3">
          <label class="block text-sm">Status:</label>
          <select class="select select-bordered w-full mb-2 status-select">${statusOpts}</select>
          <label class="block text-sm">Entregador:</label>
          <select class="select select-bordered w-full deliverer-select">${delivOpts}</select>
          <div class="flex gap-2 mt-2">
            <button class="btn btn-sm btn-success whatsapp-btn flex-1">WhatsApp</button>
            <button class="btn btn-sm btn-secondary print-btn flex-1">Imprimir</button>
          </div>
        </div>
      </div>
    `;
        }

        function fetchAndRender() {
            $.ajax({
                url: 'crud/crud_pedido.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'get_pendentes'
                }),
                success(data) {
                    Object.keys(STATUS_LABEL).forEach(s => $(`#orders-${s}`).empty());
                    data.forEach(p => $(`#orders-${p.status_pedido}`).append(renderCard(p)));
                }
            });
        }

        function updateStatus(id, novo, phone) {
            $.post('crud/crud_pedido.php', JSON.stringify({
                action: 'atualizar_status',
                id_pedido: id,
                status_pedido: novo
            }), function() {
                Swal.fire({
                        title: 'Status atualizado',
                        text: 'Enviar WhatsApp?',
                        icon: 'question',
                        showCancelButton: true
                    })
                    .then(res => {
                        if (res.isConfirmed) {
                            const msg = `Olá! Seu pedido #${id} agora está "${STATUS_LABEL[novo]}"`;
                            window.open(`https://wa.me/55${phone}?text=${encodeURIComponent(msg)}`, '_blank');
                        }
                        fetchAndRender();
                    });
            }, 'json');
        }

        function assignDeliverer(id, fid) {
            $.post('crud/crud_pedido.php', JSON.stringify({
                action: 'atribuir_entregador',
                id_pedido: id,
                id_entregador: fid
            }), fetchAndRender, 'json');
        }

        // eventos delegados
        $(document)
            .on('change', '.status-select', function() {
                const $c = $(this).closest('.card'),
                    id = $c.data('id'),
                    phone = $c.data('phone'),
                    novo = $(this).val();
                updateStatus(id, novo, phone);
            })
            .on('change', '.deliverer-select', function() {
                const $c = $(this).closest('.card'),
                    id = $c.data('id'),
                    fid = $(this).val();
                assignDeliverer(id, fid);
            })
            .on('click', '.whatsapp-btn', function() {
                const $c = $(this).closest('.card'),
                    id = $c.data('id'),
                    phone = $c.data('phone'),
                    status = $c.find('.status-select').val();
                const msg = `Olá! Seu pedido #${id} está "${STATUS_LABEL[status]}"`;
                window.open(`https://wa.me/55${phone}?text=${encodeURIComponent(msg)}`, '_blank');
            })
            .on('click', '.print-btn', function() {
                const id = $(this).closest('.card').data('id');
                $.post('crud/crud_pedido.php', JSON.stringify({
                    action: 'get_pedido',
                    id_pedido: id
                }), res => {
                    if (res.status !== 'ok') return alert(res.mensagem);
                    const p = res.pedido,
                        itens = res.itens;
                    const w = window.open('', '_blank', 'width=300,height=500');
                    w.document.write(`<html><head><title>Pedido #${p.id_pedido}</title>
          <style>body{font-family:monospace;white-space:pre}.center{text-align:center}</style>
          </head><body><div class="center">
          <strong>Minha Pizzaria</strong>\nPedido #${p.id_pedido}\n${new Date(p.criado_em).toLocaleString('pt-BR')}\n=========================\n</div>`);
                    itens.forEach(it => {
                        const unit = parseFloat(it.valor_unitario).toFixed(2);
                        w.document.write(`${it.quantidade}× ${it.nome_exibicao}  R$${unit}\n`);
                        if (it.sabores.length) w.document.write(`  Sabores: ${it.sabores.join(', ')}\n`);
                        it.adicionais.forEach(a => {
                            const addVal = parseFloat(a.valor_adicional).toFixed(2);
                            w.document.write(`  + ${a.nome_adicional} R$${addVal}\n`);
                        });
                    });
                    w.document.write(`=========================\nTotal: R$${parseFloat(p.valor_total).toFixed(2)}\n\nObrigado!\n</body></html>`);
                    w.document.close();
                    w.print();
                    w.close();
                }, 'json');
            });

        // inicialização
        filterProducts();
        fetchAndRender();
        setInterval(fetchAndRender, 15000);
    });
</script>


<?php include_once 'assets/footer.php'; ?>