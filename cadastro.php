<?php
include_once 'header.php';

if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>

<div class="container mx-auto max-w-md px-4 py-10">
    <h1 class="text-2xl font-bold text-center mb-6">Criar Conta</h1>

    <form action="cadastro_action.php" method="post" class="space-y-4">
        <div>
            <label class="block font-medium mb-1">Nome</label>
            <input type="text" name="nome" required class="input input-bordered w-full">
        </div>
        <div>
            <label class="block font-medium mb-1">Telefone</label>
            <input type="tel" name="telefone" required class="input input-bordered w-full" placeholder="(11) 91234-5678">
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

<?php include_once 'footer.php'; ?>
