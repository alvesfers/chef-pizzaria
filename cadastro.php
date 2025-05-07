<?php
include_once 'assets/header.php';

if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>

<div class="min-h-[calc(100vh-128px)] flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-base-100 p-8 rounded-lg shadow">
        <h1 class="text-2xl font-bold text-center mb-6">Criar Conta</h1>

        <form id="formCadastro" class="space-y-4">
            <div>
                <label class="block font-medium mb-1">Nome</label>
                <input type="text" name="nome" required class="input input-bordered w-full">
            </div>
            <div>
                <label class="block font-medium mb-1">Telefone</label>
                <input type="tel" name="telefone" id="telefone" required class="input input-bordered w-full" placeholder="(11) 91234-5678">
            </div>
            <div>
                <label class="block font-medium mb-1">Senha</label>
                <input type="password" name="senha" required class="input input-bordered w-full">
            </div>

            <button type="submit" class="btn btn-primary w-full">Cadastrar</button>
        </form>

        <p class="text-center text-sm mt-4">
            Já tem conta?
            <a href="login.php" class="text-primary font-medium hover:underline">Entrar</a>
        </p>
    </div>
</div>

<script>
    $('#formCadastro').on('submit', function(e) {
        e.preventDefault();

        const nome = $('[name="nome"]').val();
        const telefone = $('#telefone').val();
        const senha = $('[name="senha"]').val();

        $.post('crud/crud_usuario.php', {
            acao: 'cadastrar_e_logar',
            nome,
            telefone,
            senha
        }, function(res) {
            if (res.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Conta criada!',
                    text: res.mensagem,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php';
                });
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }, 'json');
    });
</script>

<?php include_once 'assets/footer.php'; ?>