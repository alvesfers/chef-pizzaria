<?php
include_once 'assets/header.php';

$usuarioLogado = $_SESSION['usuario'] ?? null;
if (!$usuarioLogado || !in_array($usuarioLogado['tipo_usuario'], ['admin', 'funcionario'])) {
    header('Location: meus_dados.php');
    exit;
}

$categorias = $pdo->query("
    SELECT id_categoria, nome_categoria
    FROM tb_categoria
    WHERE categoria_ativa = 1
    ORDER BY ordem_exibicao
")->fetchAll(PDO::FETCH_ASSOC);

$subcats = $pdo->query("
    SELECT sc.id_subcategoria, sc.nome_subcategoria, scc.id_categoria
    FROM tb_subcategoria sc
    INNER JOIN tb_subcategoria_categoria scc ON scc.id_subcategoria = sc.id_subcategoria
    WHERE sc.subcategoria_ativa = 1
    ORDER BY scc.id_categoria, sc.nome_subcategoria
")->fetchAll(PDO::FETCH_ASSOC);

$produtos = $pdo->query("
    SELECT p.*, c.nome_categoria
    FROM tb_produto p
    LEFT JOIN tb_categoria c USING(id_categoria)
    ORDER BY p.nome_produto, p.produto_ativo DESC
")->fetchAll(PDO::FETCH_ASSOC);

$tipoCat = $pdo->query("
    SELECT tac.id_tipo_adicional, tac.id_categoria, ta.nome_tipo_adicional, 0 AS obrigatorio, 0 AS max_inclusos
    FROM tb_tipo_adicional_categoria tac
    INNER JOIN tb_tipo_adicional ta USING(id_tipo_adicional)
    ORDER BY tac.id_categoria, ta.nome_tipo_adicional
")->fetchAll(PDO::FETCH_ASSOC);

$adicionais = $pdo->query("
    SELECT id_adicional, id_tipo_adicional, nome_adicional, valor_adicional
    FROM tb_adicional
    WHERE adicional_ativo = 1
    ORDER BY id_tipo_adicional, nome_adicional
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Produtos</h1>

    <div class="flex flex-col lg:flex-row gap-2 mb-4">
        <div class="flex flex-col sm:flex-row gap-2 flex-1">
            <select id="filter-category" class="select select-bordered flex-1">
                <option value="">Todas Categorias</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome_categoria']) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="filter-subcategory" class="select select-bordered flex-1">
                <option value="">Todas Subcategorias</option>
                <?php foreach ($subcats as $s): ?>
                    <option value="<?= $s['id_subcategoria'] ?>" data-cat="<?= $s['id_categoria'] ?>"><?= htmlspecialchars($s['nome_subcategoria']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" id="filter-name" placeholder="Procurar nome..." class="input input-bordered flex-1" />
        </div>

        <button id="btn-new-product" class="btn btn-primary">Novo Produto</button>
    </div>

    <div class="overflow-x-auto mb-4">
        <table class="table w-full">
            <thead>
                <tr>
                    <th class="sortable" data-field="nome_produto">Nome <span class="sort-indicator"></span></th>
                    <th class="sortable" data-field="nome_categoria">Categoria <span class="sort-indicator"></span></th>
                    <th class="sortable" data-field="valor_produto">Preço <span class="sort-indicator"></span></th>
                    <th class="sortable" data-field="produto_ativo">Ativo <span class="sort-indicator"></span></th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="product-table-body"></tbody>
        </table>
    </div>

    <div id="pagination" class="flex justify-center mt-4"></div>
</div>

<input type="checkbox" id="modal-product" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-full sm:max-w-lg md:max-w-3xl">
        <h3 id="modal-title" class="font-bold text-lg mb-4">Novo Produto</h3>
        <form id="product-form">
            <input type="hidden" id="pf-id" name="id_produto" />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block font-medium mb-1">Nome</label>
                    <input type="text" id="pf-nome" name="nome_produto" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="block font-medium mb-1">Preço</label>
                    <input type="text" id="pf-valor" name="valor_produto" class="input input-bordered w-full currency" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block font-medium mb-1">Descrição</label>
                    <textarea id="pf-descricao" name="descricao_produto" class="textarea textarea-bordered w-full" rows="1"></textarea>
                </div>
                <div>
                    <label class="block font-medium mb-1">Categoria</label>
                    <select id="pf-categoria" name="id_categoria" class="select select-bordered w-full" required>
                        <option value="">— selecione —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome_categoria']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Subcategorias</label>
                <div id="pf-subcategorias" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 gap-2 max-h-32 overflow-auto border p-2 rounded"></div>
            </div>

            <hr class="my-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1">Qtd Sabores</label>
                    <input type="number" id="pf-qtd-sabores" name="qtd_sabores" class="input input-bordered w-full" min="1" />
                </div>
                <div id="pf-tipo-calculo-group" class="hidden">
                    <label class="block font-medium mb-1">Tipo de Cálculo</label>
                    <select id="pf-tipo-calculo" name="tipo_calculo_preco" class="select select-bordered w-full">
                        <option value="maior">Maior</option>
                        <option value="media">Média</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Controle de Estoque</label>
                    <select id="pf-ctrl-estoque" name="controle_estoque" class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Qtd Estoque</label>
                    <input type="number" id="pf-estoque" name="qtd_produto" class="input input-bordered w-full" min="0" />
                </div>
            </div>

            <div>
                <label class="block font-medium mb-1">Adicionais Inclusos?</label>
                <select id="pf-has-inclusos" name="has_inclusos" class="select select-bordered w-full">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            </div>

            <div id="pf-inclusos-section" class="hidden space-y-4 p-4 border rounded mb-4">
                <div id="pf-inclusos-quantidade" class="space-y-2"></div>
                <a href="#" id="link-definir-itens" class="text-sm text-blue-600 hover:underline">Deseja definir os itens inclusos?</a>
                <div id="pf-inclusos-itens" class="space-y-2 hidden"></div>
            </div>

            <div class="mb-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="pf-ativo" name="produto_ativo" value="1" checked />
                    <span>Produto Ativo</span>
                </label>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <label for="modal-product" class="btn">Cancelar</label>
            </div>
        </form>
    </div>
</div>

<script>
    window.__categorias__ = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
    window.__subcategorias__ = <?= json_encode($subcats, JSON_UNESCAPED_UNICODE) ?>;
    window.__produtos__ = <?= json_encode($produtos, JSON_UNESCAPED_UNICODE) ?>;
    window.__tipoCat__ = <?= json_encode($tipoCat, JSON_UNESCAPED_UNICODE) ?>;
    window.__adicionais__ = <?= json_encode($adicionais, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="assets/js/produtos.js"></script>
<?php include_once 'assets/footer.php'; ?>
