<?php include_once 'assets/header.php'; ?>

<div class="container mx-auto p-4 space-y-6">
    <h1 class="text-2xl font-bold mb-4">Configurações da Loja</h1>

    <form id="formDadosLoja" class="space-y-6">
        <!-- Accordion de Dados -->
        <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox" checked />
            <div class="collapse-title font-medium">Dados da Loja</div>
            <div class="collapse-content grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="form-control">
                    <span class="label-text">Nome da Loja</span>
                    <input id="nome_loja" name="nome_loja" class="input input-bordered" />
                </label>
                <label class="form-control">
                    <span class="label-text">CEP</span>
                    <input id="cep" name="cep" class="input input-bordered" />
                </label>
                <label class="form-control md:col-span-2">
                    <span class="label-text">Endereço Completo</span>
                    <input id="endereco_completo" name="endereco_completo" class="input input-bordered" />
                </label>
            </div>
        </div>

        <?php if ($teste != 1): ?>
            <!-- Contato & Redes -->
            <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
                <input type="checkbox" checked />
                <div class="collapse-title font-medium">Contato & Redes Sociais</div>
                <div class="collapse-content grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="form-control">
                        <span class="label-text">WhatsApp</span>
                        <input id="whatsapp" name="whatsapp" class="input input-bordered" />
                    </label>
                    <label class="form-control">
                        <span class="label-text">Instagram</span>
                        <input id="instagram" name="instagram" class="input input-bordered" />
                    </label>
                    <label class="form-control md:col-span-2">
                        <span class="label-text">Chave Google Maps</span>
                        <input id="google" name="google" type="password" class="input input-bordered" />
                    </label>
                </div>
            </div>

        <?php endif; ?>

        <!-- Entrega -->
        <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox" checked />
            <div class="collapse-title font-medium">Entrega & Retirada</div>
            <div class="collapse-content grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="form-control">
                    <span class="label-text">Preço Base (R$)</span>
                    <input id="preco_base" name="preco_base" type="number" step="0.01" class="input input-bordered" />
                </label>
                <label class="form-control">
                    <span class="label-text">Preço por KM (R$)</span>
                    <input id="preco_km" name="preco_km" type="number" step="0.01" class="input input-bordered" />
                </label>
                <label class="form-control">
                    <span class="label-text">Limite de Entrega (KM)</span>
                    <input id="limite_entrega" name="limite_entrega" type="number" class="input input-bordered" />
                </label>
                <label class="form-control">
                    <span class="label-text">Tempo Entrega (min)</span>
                    <input id="tempo_entrega" name="tempo_entrega" type="number" class="input input-bordered" />
                </label>
                <label class="form-control">
                    <span class="label-text">Tempo Retirada (min)</span>
                    <input id="tempo_retirada" name="tempo_retirada" type="number" class="input input-bordered" />
                </label>
                <label class="form-control flex items-center gap-2 mt-6">
                    <span class="label-text">Usar horários de atendimento</span>
                    <input type="checkbox" class="toggle" name="usar_horarios" />
                </label>
            </div>
        </div>

        <!-- Logo -->
        <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox" checked />
            <div class="collapse-title font-medium">Logo da Loja</div>
            <div class="collapse-content space-y-4">
                <label class="form-control">
                    <span class="label-text">Upload da Logo</span>
                    <input type="file" id="logo" accept="image/*" class="file-input file-input-bordered w-full" />
                </label>
                <img id="logo-preview" class="max-h-24" />
            </div>
        </div>

        <!-- Tema -->
        <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox" checked />
            <div class="collapse-title font-medium">Tema da Loja</div>
            <div class="collapse-content">
                <div class="rounded-box grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="theme-picker">
                    <?php include 'theme-picker.php'; ?>
                </div>
            </div>
        </div>
    </form>

    <!-- Ações -->
    <div class="flex flex-wrap gap-4 justify-between">
        <button id="btnSalvarLoja" class="btn btn-primary w-full md:w-auto">Salvar Dados</button>
        <label for="modal-horarios" class="btn w-full md:w-auto">Horários de Atendimento</label>
        <label for="modal-regras-frete" class="btn btn-outline w-full md:w-auto">Regras de Frete</label>
    </div>
</div>

<!-- Modal Horários -->
<input type="checkbox" id="modal-horarios" class="modal-toggle" />
<div class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
        <h3 class="text-lg font-bold mb-4">Horários de Atendimento</h3>
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Dia</th>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                    <th>Ativo</th>
                </tr>
            </thead>
            <tbody id="tabelaHorarios"></tbody>
        </table>
        <div class="modal-action">
            <label for="modal-horarios" class="btn">Fechar</label>
            <button id="btnSalvarHorarios" class="btn btn-success">Salvar Horários</button>
        </div>
    </div>
</div>

<!-- Modal Regras Frete -->
<input type="checkbox" id="modal-regras-frete" class="modal-toggle" />
<div class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
        <h3 class="font-bold text-lg mb-4">Regras de Frete</h3>
        <div id="regrasFrete" class="space-y-4 max-h-[60vh] overflow-y-auto"></div>
        <div class="flex justify-between mt-4">
            <button id="btnNovaRegra" class="btn btn-outline btn-sm">+ Nova Regra</button>
            <button id="btnSalvarRegras" class="btn btn-success">Salvar Regras</button>
        </div>
        <div class="modal-action">
            <label for="modal-regras-frete" class="btn">Fechar</label>
        </div>
    </div>
</div>

<script src="assets/js/loja.js"></script>
<?php include_once 'assets/footer.php'; ?>