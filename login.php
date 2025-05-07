<?php
include_once 'assets/header.php';

if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>

<div class="min-h-[calc(100vh-128px)] flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-base-100 p-8 rounded-lg shadow">
        <h1 class="text-2xl font-bold text-center mb-6">Entrar na Conta</h1>

        <form id="formLogin" action="#" method="post" class="space-y-4">

            <div>
                <label class="block font-medium mb-1">Telefone</label>
                <input type="tel" name="telefone" id="telefone" required class="input input-bordered w-full" placeholder="(11) 91234-5678">
            </div>
            <div>
                <label class="block font-medium mb-1">Senha</label>
                <input type="password" name="senha" required class="input input-bordered w-full">
            </div>

            <button type="submit" class="btn btn-primary w-full">Entrar</button>
        </form>

        <p class="text-center text-sm mt-4">
            Ainda não tem conta?
            <a href="cadastro.php" class="text-primary font-medium hover:underline">Cadastre-se aqui</a>
        </p>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#formLogin').on('submit', function(e) {
            e.preventDefault();

            const telefone = $('#telefone').val();
            const senha = $('[name="senha"]').val();

            $.ajax({
                url: 'crud/crud_usuario.php',
                method: 'POST',
                data: {
                    acao: 'login',
                    telefone: telefone,
                    senha: senha
                },
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: 'Aguarde...',
                        text: 'Validando login...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(res) {
                    Swal.close();
                    if (res.status === 'ok') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bem-vindo!',
                            text: res.mensagem,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'index.php';
                        });
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error(error);
                    Swal.fire('Erro', 'Erro na requisição. Tente novamente.', 'error');
                }
            });
        });
    });
</script>



<?php include_once 'assets/footer.php'; ?>