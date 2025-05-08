<?php
// produtos.php
include_once 'assets/header.php';

// --- Puxa do banco ---
$categorias = $pdo->query("
  SELECT id_categoria, nome_categoria 
    FROM tb_categoria 
   WHERE categoria_ativa = 1
   ORDER BY ordem_exibicao
")->fetchAll(PDO::FETCH_ASSOC);

$subcats = $pdo->query("
  SELECT sc.id_subcategoria, sc.nome_subcategoria, scc.id_categoria
    FROM tb_subcategoria sc
    JOIN tb_subcategoria_categoria scc USING(id_subcategoria)
   WHERE sc.subcategoria_ativa = 1
")->fetchAll(PDO::FETCH_ASSOC);

// produtos ativos
$produtos = $pdo->query("
  SELECT p.id_produto, p.nome_produto AS nome, p.id_categoria, p.valor_produto, p.qtd_sabores, c.nome_categoria
    FROM tb_produto p
    LEFT JOIN tb_categoria c USING(id_categoria)
   WHERE p.produto_ativo = 1
   ORDER BY p.id_produto
")->fetchAll(PDO::FETCH_ASSOC);

// tipos de adicionais definidos para cada categoria
$tipoCat = $pdo->query("
  SELECT tac.id_tipo_adicional, tac.id_categoria, ta.nome_tipo_adicional, ta.max_escolha
    FROM tb_tipo_adicional_categoria tac
    JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
")->fetchAll(PDO::FETCH_ASSOC);

// todos os adicionais ativos
$adicionais = $pdo->query("
  SELECT id_adicional, id_tipo_adicional, nome_adicional AS nome, valor_adicional AS preco
    FROM tb_adicional
   WHERE adicional_ativo = 1
")->fetchAll(PDO::FETCH_ASSOC);

// mapeia arrays para JS
?>
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Produtos</h1>

    <!-- filtros + botão novo -->
    <div class="flex gap-2 mb-4">
        <select id="filter-category" class="select select-bordered">
            <option value="">Todas Categorias</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome_categoria']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filter-subcategory" class="select select-bordered">
            <option value="">Todas Subcategorias</option>
            <?php foreach ($subcats as $s): ?>
                <option value="<?= $s['id_subcategoria'] ?>"
                    data-cat="<?= $s['id_categoria'] ?>">
                    <?= htmlspecialchars($s['nome_subcategoria']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text"
            id="filter-name"
            placeholder="Procurar nome..."
            class="input input-bordered flex-grow" />

        <button id="btn-new-product" class="btn btn-primary">Novo Produto</button>
    </div>

    <!-- tabela de produtos -->
    <table class="table w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Multi-Sabores</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="product-table-body"></tbody>
    </table>

    <!-- paginação -->
    <div id="pagination" class="flex justify-center mt-4"></div>
</div>

<!-- Modal Cadastro/Edição de Produto -->
<input type="checkbox" id="modal-product" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-3xl">
        <h3 id="modal-title" class="font-bold text-lg mb-4">Novo Produto</h3>
        <form id="product-form">
            <!-- campo oculto para o ID em edição -->
            <input type="hidden" id="pf-id" name="id_produto" />

            <!-- 1) Nome / Categoria -->
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block font-medium mb-1">Nome</label>
                    <input
                        type="text"
                        id="pf-nome"
                        name="nome_produto"
                        class="input input-bordered w-full"
                        required />
                </div>
                <div>
                    <label class="block font-medium mb-1">Categoria</label>
                    <select
                        id="pf-categoria"
                        name="id_categoria"
                        class="select select-bordered w-full"
                        required>
                        <option value="">— selecione —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id_categoria'] ?>">
                                <?= htmlspecialchars($c['nome_categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 2) Preço / Estoque / Descrição -->
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1">Preço</label>
                    <input
                        type="text"
                        id="pf-valor"
                        name="valor_produto"
                        class="input input-bordered w-full currency"
                        required />
                </div>
                <div>
                    <label class="block font-medium mb-1">Qtd Estoque</label>
                    <input
                        type="number"
                        id="pf-estoque"
                        name="qtd_produto"
                        class="input input-bordered w-full"
                        min="0" />
                </div>
                <div class="col-span-1">
                    <label class="block font-medium mb-1">Descrição</label>
                    <textarea
                        id="pf-descricao"
                        name="descricao_produto"
                        class="textarea textarea-bordered w-full"
                        rows="2"></textarea>
                </div>
            </div>

            <!-- 3) Subcategorias -->
            <div class="mb-4">
                <label class="block font-medium mb-1">Subcategorias</label>
                <div
                    id="pf-subcategorias"
                    class="grid grid-cols-3 gap-2 max-h-32 overflow-auto border p-2 rounded">
                    <!-- JS irá popular aqui com checkboxes -->
                </div>
            </div>

            <!-- 4) Sabores / Tipo de Cálculo / Inclusos -->
            <div class="grid grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1">Qtd Sabores</label>
                    <input
                        type="number"
                        id="pf-qtd-sabores"
                        name="qtd_sabores"
                        class="input input-bordered w-full"
                        min="1" />
                </div>
                <div id="pf-tipo-calculo-group" class="hidden">
                    <label class="block font-medium mb-1">Tipo de Cálculo</label>
                    <select
                        id="pf-tipo-calculo"
                        name="tipo_calculo_preco"
                        class="select select-bordered w-full">
                        <option value="maior">Maior</option>
                        <option value="media">Média</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Adicionais inclusos?</label>
                    <select
                        id="pf-has-inclusos"
                        name="has_inclusos"
                        class="select select-bordered w-full">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Modo de Inclusos</label>
                    <select
                        id="pf-incluso_mode"
                        name="incluso_mode"
                        class="select select-bordered w-full">
                        <option value="0">Quantidade</option>
                        <option value="1">Itens</option>
                    </select>
                </div>
            </div>

            <!-- 5) Configuração de inclusos (quantidade ou itens) -->
            <div
                id="pf-inclusos-section"
                class="hidden space-y-4 p-4 border rounded mb-4">
                <!-- Por quantidade -->
                <div id="pf-inclusos-quantidade" class="space-y-2">
                    <!-- JS irá popular aqui -->
                </div>
                <!-- Por itens -->
                <div id="pf-inclusos-itens" class="space-y-2 hidden">
                    <!-- JS irá popular aqui -->
                </div>
            </div>

            <!-- Ações -->
            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <label for="modal-product" class="btn">Cancelar</label>
            </div>
        </form>
    </div>
</div>



<script>
    window.__categorias__ = <?= json_encode($categorias,    JSON_UNESCAPED_UNICODE) ?>;
    window.__subcategorias__ = <?= json_encode($subcats,       JSON_UNESCAPED_UNICODE) ?>;
    window.__produtos__ = <?= json_encode($produtos,     JSON_UNESCAPED_UNICODE) ?>;
    window.__tipoCat__ = <?= json_encode($tipoCat,      JSON_UNESCAPED_UNICODE) ?>;
    window.__adicionais__ = <?= json_encode($adicionais,   JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/produtos.js"></script>
<?php include_once 'assets/footer.php'; ?>