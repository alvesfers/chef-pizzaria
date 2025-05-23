<?php
include_once 'assets/header.php';

$categorias = $pdo
    ->query("
        SELECT 
          id_categoria,
          nome_categoria,
          tem_qtd,
          categoria_ativa,
          ordem_exibicao
        FROM tb_categoria
        ORDER BY ordem_exibicao, nome_categoria
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

$subcats = $pdo
    ->query("
        SELECT 
          id_subcategoria,
          nome_subcategoria
        FROM tb_subcategoria
        WHERE subcategoria_ativa = 1
        ORDER BY nome_subcategoria
    ")
    ->fetchAll(PDO::FETCH_ASSOC);

$tipoAdicionais = $pdo
    ->query("
        SELECT 
          id_tipo_adicional,
          nome_tipo_adicional
        FROM tb_tipo_adicional
        WHERE tipo_ativo = 1
        ORDER BY nome_tipo_adicional
    ")
    ->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Categorias</h1>

    <div class="flex flex-col sm:flex-row gap-2 mb-4">
        <button id="btn-new-category" class="btn btn-primary flex-1">
            Nova Categoria
        </button>
        <button id="btn-manage-subcats" class="btn btn-secondary flex-1">
            Subcategorias
        </button>
    </div>

    <div class="overflow-x-auto mb-4">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tem Qtd</th>
                    <th>Ativa</th>
                    <th>Ordem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="category-table-body"></tbody>
        </table>
    </div>

    <div id="pagination" class="flex justify-center mt-4"></div>
</div>

<input type="checkbox" id="modal-category" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-full sm:max-w-md">
        <h3 id="modal-category-title" class="font-bold text-lg mb-4">Nova Categoria</h3>
        <form id="category-form">
            <input type="hidden" id="cf-id" name="id_categoria" />

            <div class="mb-4">
                <label class="block font-medium mb-1">Nome da Categoria</label>
                <input type="text" id="cf-nome" name="nome_categoria"
                    class="input input-bordered w-full" required />
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Categorias Relacionadas</label>
                <div id="cf-categorias-relacionadas" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-auto border p-2 rounded">

                </div>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Subcategorias</label>
                <div id="cf-subcategorias" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-auto border p-2 rounded">
                </div>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Tipos de Adicional</label>
                <div id="cf-tipo-adicionais" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-auto border p-2 rounded">

                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1">Tem Qtd?</label>
                    <select id="cf-tem-qtd" name="tem_qtd"
                        class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Ativa?</label>
                    <select id="cf-ativa" name="categoria_ativa"
                        class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Ordem de Exibição</label>
                <input type="number" id="cf-ordem" name="ordem_exibicao"
                    class="input input-bordered w-full" min="0" required />
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <label for="modal-category" class="btn">Cancelar</label>
            </div>
        </form>
    </div>
</div>

<input type="checkbox" id="modal-subcats" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-full sm:max-w-lg">
        <h3 class="font-bold text-lg mb-4">Gerenciar Subcategorias</h3>

        <div id="subcat-list" class="mb-4 flex flex-wrap gap-2"></div>

        <form id="subcat-form" class="mb-4">
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" id="sc-nome" name="nome_subcategoria"
                    class="input input-bordered flex-1"
                    placeholder="Nova Subcategoria" required />
                <button type="submit" class="btn btn-primary">
                    Adicionar
                </button>
            </div>
        </form>

        <div class="modal-action">
            <label for="modal-subcats" class="btn">Fechar</label>
        </div>
    </div>
</div>

<script>
    window.__categorias__ = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
    window.__subcategorias__ = <?= json_encode($subcats, JSON_UNESCAPED_UNICODE) ?>;
    window.__tipoAdicionais__ = <?= json_encode($tipoAdicionais, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="assets/js/categorias.js"></script>
<?php include_once 'assets/footer.php'; ?>