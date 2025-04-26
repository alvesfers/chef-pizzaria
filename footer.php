</main>

<footer class="bg-primary text-primary-content mt-10 p-6">
    <div class="container mx-auto text-center space-y-4">
        <div class="flex justify-center space-x-6">
            <a href="https://wa.me/+5511999999999" target="_blank"><i class="fab fa-whatsapp text-2xl hover:text-green-400"></i></a>
            <a href="https://instagram.com/pizzariabellamassa" target="_blank"><i class="fab fa-instagram text-2xl hover:text-pink-400"></i></a>
        </div>
        <p>Rua dos Sabores, 456 - Centro</p>
        <p>© 2025 Pizzaria Bella Massa. Todos os direitos reservados.</p>
    </div>
</footer>

</body>
<script>
    $(document).ready(function() {
        $('#btnLogout').click(function() {
            $.post('logout.php', {}, function(response) {
                if (response.status === 'ok') {
                    Swal.fire({
                        title: 'Deslogado!',
                        text: 'Você saiu da sua conta.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Erro', 'Não foi possível sair.', 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Erro', 'Erro na comunicação com o servidor.', 'error');
            });
        });
    });

    $('#telefone').mask('(00) 00000-0000');
    $('#cep').mask('00000-000');
</script>

</html>