<?php
include_once 'assets/header.php';

// 1) Todos os tipos de adicionais (ativos e inativos)
$tipoAdicionais = $pdo->query("
    SELECT 
      id_tipo_adicional,
      nome_tipo_adicional,
      obrigatorio,
      multipla_escolha,
      max_escolha,
      tipo_ativo
    FROM tb_tipo_adicional
    ORDER BY nome_tipo_adicional
")->fetchAll(PDO::FETCH_ASSOC);

// 2) Todos os adicionais
$adicionais = $pdo->query("
    SELECT 
      id_adicional,
      id_tipo_adicional,
      nome_adicional,
      valor_adicional,
      adicional_ativo
    FROM tb_adicional
    ORDER BY nome_adicional
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Tipos de Adicional</h1>

    <!-- botão criar -->
    <div class="flex flex-col sm:flex-row gap-2 mb-4">
        <button id="btn-new-tipo" class="btn btn-primary flex-1">
            Novo Tipo
        </button>
    </div>

    <!-- tabela responsiva -->
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Obrigatório</th>
                    <th>Múltipla Escolha</th>
                    <th>Max Escolha</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tipo-table-body"></tbody>
        </table>
    </div>
</div>

<!-- Modal Tipo de Adicional -->
<input type="checkbox" id="modal-tipo" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-full sm:max-w-2xl">
        <h3 id="modal-tipo-title" class="font-bold text-lg mb-4">Novo Tipo</h3>
        <form id="tipo-form">
            <input type="hidden" id="tf-id" name="id_tipo_adicional" />

            <div class="mb-4">
                <label class="block font-medium mb-1">Nome do Tipo</label>
                <input type="text" id="tf-nome" name="nome_tipo_adicional"
                    class="input input-bordered w-full" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1">Obrigatório?</label>
                    <select id="tf-obrig" name="obrigatorio"
                        class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Múltipla Escolha?</label>
                    <select id="tf-multi" name="multipla_escolha"
                        class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Max Escolha</label>
                    <input type="number" id="tf-max" name="max_escolha"
                        class="input input-bordered w-full" min="0" />
                </div>
                <div>
                    <label class="block font-medium mb-1">Ativo?</label>
                    <select id="tf-ativo" name="tipo_ativo"
                        class="select select-bordered w-full">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" />

            <h4 class="font-semibold mb-2">Adicionais deste Tipo</h4>
            <div class="overflow-x-auto mb-2" id="tf-adicionais-list">
                <!-- tabela de adicionais, via JS -->
            </div>
            <button type="button" id="btn-new-adicional"
                class="btn btn-sm btn-secondary mb-4">
                Novo Adicional
            </button>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <label for="modal-tipo" class="btn">Cancelar</label>
            </div>
        </form>
    </div>
</div>

<!-- Modal Adicional -->
<input type="checkbox" id="modal-adicional" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-full sm:max-w-md">
        <h3 id="modal-adicional-title" class="font-bold text-lg mb-4">Novo Adicional</h3>
        <form id="adicional-form">
            <input type="hidden" id="af-id" name="id_adicional" />
            <input type="hidden" id="af-tipo" name="id_tipo_adicional" />

            <div class="mb-4">
                <label class="block font-medium mb-1">Nome do Adicional</label>
                <input type="text" id="af-nome" name="nome_adicional"
                    class="input input-bordered w-full" required />
            </div>
            <div class="mb-4">
                <label class="block font-medium mb-1">Valor</label>
                <input type="number" step="0.01" id="af-valor" name="valor_adicional"
                    class="input input-bordered w-full" required />
            </div>
            <div class="mb-4">
                <label class="block font-medium mb-1">Ativo?</label>
                <select id="af-ativo" name="adicional_ativo"
                    class="select select-bordered w-full">
                    <option value="1">Sim</option>
                    <option value="0">Não</option>
                </select>
            </div>
            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <label for="modal-adicional" class="btn">Cancelar</label>
            </div>
        </form>
    </div>
</div>

<script>
    window.__tipoAdicionais__ = <?= json_encode($tipoAdicionais, JSON_UNESCAPED_UNICODE) ?>;
    window.__adicionais__ = <?= json_encode($adicionais,    JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/adicionais.js"></script>
<?php include_once 'assets/footer.php'; ?>