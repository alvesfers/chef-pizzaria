<?php
// usuarios.php
require_once 'assets/header.php';

// só administradores podem acessar
if (!($isAdmin ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Usuários Admin</h1>
        <button id="btnNovoUsuario" class="btn btn-primary">Novo Usuário</button>
    </div>

    <table id="tableUsuarios" class="table w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Ativo</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal de Cadastro/Edição -->
<input type="checkbox" id="modal-usuario" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-lg">
        <h3 id="titulo-modal" class="font-bold text-lg mb-4">Novo Usuário</h3>
        <form id="formUsuario" class="space-y-4">
            <input type="hidden" id="id_usuario" name="id_usuario">

            <div>
                <label class="block font-medium">Nome</label>
                <input type="text" id="nome_usuario" name="nome_usuario" class="input input-bordered w-full" required>
            </div>
            <div>
                <label class="block font-medium">Telefone</label>
                <input type="text" id="telefone" name="telefone" class="input input-bordered w-full" required>
            </div>
            <div>
                <label class="block font-medium">Tipo de Usuário</label>
                <select id="tipo_usuario" name="tipo_usuario" class="select select-bordered w-full" required>
                    <option value="admin">Admin</option>
                    <option value="cliente">Cliente</option>
                    <option value="funcionario">Funcionário</option>
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="usuario_ativo" name="usuario_ativo" class="checkbox" checked />
                <label for="usuario_ativo">Ativo?</label>
            </div>
            <div>
                <label class="block font-medium">Senha</label>
                <input type="password" id="senha_usuario" name="senha_usuario" class="input input-bordered w-full" required>
            </div>

            <div class="modal-action">
                <label for="modal-usuario" class="btn">Cancelar</label>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'assets/footer.php'; ?>

<script>
    $(function() {
        function carregarUsuarios() {
            $.post('crud/crud_usuarios.php', {
                action: 'listar_usuarios'
            }, function(res) {
                if (res.status === 'sucesso') {
                    let html = '';
                    res.dados.forEach(u => {
                        html += `
            <tr>
              <td>${u.id_usuario}</td>
              <td>${u.nome_usuario}</td>
              <td>${u.telefone_usuario}</td>
              <td>${u.tipo_usuario}</td>
              <td>${u.usuario_ativo ? 'Sim' : 'Não'}</td>
              <td>${u.criado_em}</td>
              <td class="space-x-2">
                <button class="btn btn-sm btn-info btn-edit" data-id="${u.id_usuario}">Editar</button>
                <button class="btn btn-sm btn-error btn-delete" data-id="${u.id_usuario}">Inativar</button>
              </td>
            </tr>`;
                    });
                    $('#tableUsuarios tbody').html(html);
                } else {
                    Swal.fire('Erro', 'Não foi possível carregar usuários.', 'error');
                }
            }, 'json');
        }

        carregarUsuarios();

        // abrir modal para novo usuário
        $('#btnNovoUsuario').click(() => {
            $('#titulo-modal').text('Novo Usuário');
            $('#formUsuario')[0].reset();
            $('#id_usuario').val('');
            $('#usuario_ativo').prop('checked', true);
            $('#modal-usuario').prop('checked', true);
        });

        // cadastro/edição
        $('#formUsuario').submit(function(e) {
            e.preventDefault();
            const ativo = $('#usuario_ativo').is(':checked') ? 1 : 0;
            const dados = $(this).serializeArray();
            dados.push({
                name: 'usuario_ativo',
                value: ativo
            });
            dados.push({
                name: 'action',
                value: 'salvar_usuario'
            });
            $.post('crud/crud_usuarios.php', dados, function(res) {
                if (res.status === 'sucesso') {
                    Swal.fire('Sucesso', res.mensagem, 'success');
                    $('#modal-usuario').prop('checked', false);
                    carregarUsuarios();
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        });

        // editar
        $('#tableUsuarios').on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $.post('crud/crud_usuarios.php', {
                action: 'get_usuario',
                id_usuario: id
            }, function(res) {
                if (res.status === 'sucesso') {
                    const u = res.dados;
                    $('#titulo-modal').text('Editar Usuário');
                    $('#id_usuario').val(u.id_usuario);
                    $('#nome_usuario').val(u.nome_usuario);
                    $('#telefone').val(u.telefone_usuario);
                    $('#tipo_usuario').val(u.tipo_usuario);
                    $('#usuario_ativo').prop('checked', u.usuario_ativo == 1);
                    $('#senha_usuario').val('');
                    $('#modal-usuario').prop('checked', true);
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json');
        });

        // inativar
        $('#tableUsuarios').on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Inativar usuário?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, inativar'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('crud/crud_usuarios.php', {
                        action: 'deletar_usuario',
                        id_usuario: id
                    }, function(res) {
                        if (res.status === 'sucesso') {
                            Swal.fire('Feito', res.mensagem, 'success');
                            carregarUsuarios();
                        } else {
                            Swal.fire('Erro', res.mensagem, 'error');
                        }
                    }, 'json');
                }
            });
        });
    });
</script>