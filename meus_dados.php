<?php
require_once 'assets/conexao.php';
include_once 'assets/header.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$idUsuario = $_SESSION['usuario']['id'];

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE id_usuario = ?");
$stmt->execute([$idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Endereços ativos
$stmt = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_usuario = ? AND endereco_ativo = 1");
$stmt->execute([$idUsuario]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-2xl font-bold mb-6">Meus Dados</h1>

    <form id="formDadosUsuario" class="space-y-6">
        <input type="hidden" name="acao" value="atualizar_usuario">

        <div>
            <label class="block mb-1 font-semibold">Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome_usuario']) ?>" class="input input-bordered w-full" required>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($usuario['telefone_usuario']) ?>" id="telefone" class="input input-bordered w-full" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 font-semibold">Senha Atual</label>
                <input type="password" name="senha_atual" class="input input-bordered w-full">
            </div>
            <div>
                <label class="block mb-1 font-semibold">Nova Senha</label>
                <input type="password" name="nova_senha" class="input input-bordered w-full">
            </div>
        </div>

        <h2 class="text-xl font-bold mt-10">Meus Endereços</h2>

        <div class="mt-4">
            <select id="selectEndereco" class="select select-bordered w-full mb-3">
                <option value="">-- Selecione um endereço para editar --</option>
                <?php foreach ($enderecos as $e): ?>
                    <option value="<?= $e['id_endereco'] ?>" data-endereco='<?= json_encode($e) ?>'>
                        <?= htmlspecialchars($e['apelido'] . ' - ' . $e['rua'] . ', ' . $e['numero']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex gap-2 mb-4">
                <button type="button" id="btnEditarEndereco" class="btn btn-outline btn-sm flex-1">✏️ Editar</button>
                <button type="button" id="btnNovoEndereco" class="btn btn-outline btn-sm flex-1">➕ Novo</button>
                <button type="button" id="btnExcluirEndereco" class="btn btn-outline btn-error btn-sm flex-1">🗑 Excluir</button>
            </div>

            <div id="formEndereco" class="hidden border p-4 rounded bg-base-200 space-y-3">
                <input type="hidden" name="id_endereco" id="id_endereco">

                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block font-semibold">CEP</label>
                        <input type="text" id="cep" class="input input-bordered w-full">
                    </div>
                    <div>
                        <label class="block font-semibold">Apelido</label>
                        <input type="text" id="apelido" class="input input-bordered w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold">Rua</label>
                        <input type="text" id="rua" class="input input-bordered w-full">
                    </div>
                    <div>
                        <label class="block font-semibold">Número</label>
                        <input type="text" id="numero" class="input input-bordered w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold">Complemento</label>
                        <input type="text" id="complemento" class="input input-bordered w-full">
                    </div>
                    <div>
                        <label class="block font-semibold">Bairro</label>
                        <input type="text" id="bairro" class="input input-bordered w-full">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">Ponto de Referência</label>
                    <input type="text" id="ponto_referencia" class="input input-bordered w-full">
                </div>

                <button type="button" class="btn btn-success w-full" id="btnSalvarEndereco">Salvar Endereço</button>
            </div>
        </div>

        <div class="mt-6 text-right">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <button type="button" class="btn btn-outline btn-error ml-2" id="btnDesativarConta">Desativar Conta</button>
        </div>
    </form>
</div>

<script>
    $('#telefone').mask('(00) 00000-0000');
    $('#cep').mask('00000-000');

    // Salvar dados pessoais
    $('#formDadosUsuario').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post('crud/crud_usuario.php', formData, function(res) {
            if (res.status === 'ok') {
                Swal.fire('Sucesso', res.mensagem, 'success');
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }, 'json');
    });

    // Editar endereço
    $('#btnEditarEndereco').click(function() {
        const option = $('#selectEndereco option:selected');
        const data = option.data('endereco');
        if (!data) {
            Swal.fire('Atenção', 'Selecione um endereço para editar.', 'warning');
            return;
        }
        $('#formEndereco').removeClass('hidden');
        $('#id_endereco').val(data.id_endereco);
        $('#cep').val(data.cep);
        $('#apelido').val(data.apelido);
        $('#rua').val(data.rua);
        $('#numero').val(data.numero);
        $('#complemento').val(data.complemento);
        $('#bairro').val(data.bairro);
        $('#ponto_referencia').val(data.ponto_de_referencia);
    });

    // Novo endereço
    $('#btnNovoEndereco').click(function() {
        $('#formEndereco').removeClass('hidden');
        $('#id_endereco').val('');
        $('#cep, #apelido, #rua, #numero, #complemento, #bairro, #ponto_referencia').val('');
    });

    // Excluir endereço
    $('#btnExcluirEndereco').click(function() {
        const id = $('#selectEndereco').val();
        if (!id) {
            Swal.fire('Atenção', 'Selecione um endereço para excluir.', 'warning');
            return;
        }
        Swal.fire({
            title: 'Tem certeza?',
            text: 'Deseja desativar este endereço?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, desativar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('crud/crud_endereco.php', {
                    acao: 'excluir',
                    id_endereco: id
                }, function(res) {
                    if (res.status === 'ok') {
                        Swal.fire('Pronto', res.mensagem, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json');
            }
        });
    });

    // Buscar endereço pelo CEP
    $('#cep').on('change', function() {
        const cep = $(this).val().replace(/\D/g, '');
        if (cep.length !== 8) return;
        $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
            if (!data.erro) {
                $('#rua').val(data.logradouro);
                $('#bairro').val(data.bairro);
            }
        });
    });

    // Salvar endereço
    $('#btnSalvarEndereco').click(function() {
        const dados = {
            acao: $('#id_endereco').val() ? 'editar' : 'cadastrar',
            id_endereco: $('#id_endereco').val(),
            cep: $('#cep').val(),
            apelido: $('#apelido').val(),
            rua: $('#rua').val(),
            numero: $('#numero').val(),
            complemento: $('#complemento').val(),
            bairro: $('#bairro').val(),
            ponto_referencia: $('#ponto_referencia').val()
        };

        $.post('crud/crud_endereco.php', dados, function(res) {
            if (res.status === 'ok') {
                Swal.fire('Sucesso', res.mensagem, 'success').then(() => location.reload());
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }, 'json');
    });

    // Desativar conta
    $('#btnDesativarConta').click(function() {
        Swal.fire({
            title: 'Tem certeza?',
            text: 'Deseja desativar sua conta? Você não poderá mais fazer pedidos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Desativar',
            input: 'textarea',
            inputPlaceholder: 'Motivo (opcional)'
        }).then(result => {
            if (result.isConfirmed) {
                $.post('crud/crud_usuario.php', {
                    acao: 'desativar',
                    motivo: result.value
                }, function(res) {
                    if (res.status === 'ok') {
                        Swal.fire('Conta desativada', res.mensagem, 'success').then(() => {
                            window.location.href = 'logout.php';
                        });
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json');
            }
        });
    });
</script>

<?php include_once 'assets/footer.php'; ?>
