<?php
require_once 'assets/conexao.php';
session_start();

// usuário logado e permissões
$usuarioLogado = $_SESSION['usuario'] ?? null;
$tipoUsuario   = $usuarioLogado['tipo_usuario'] ?? 'cliente';
$isAdmin       = in_array($tipoUsuario, ['admin', 'funcionario']);

// dados da loja
$dadosLoja    = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")
    ->fetch(PDO::FETCH_ASSOC);
$nomeLoja     = $dadosLoja['nome_loja'] ?? 'Pizzaria';
$whatsapp     = preg_replace('/\D/', '', $dadosLoja['whatsapp'] ?? '');
$instagram    = $dadosLoja['instagram'] ?? null;
$enderecoLoja = $dadosLoja['endereco_completo'] ?? '';
$tema         = $dadosLoja['tema'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= htmlspecialchars($tema) ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($nomeLoja) ?> - Pedido Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@2.51.5/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="icon" href="/assets/favicon.ico" />
</head>

<body class="flex flex-col min-h-screen bg-base-200">
    <script>
        window.LOJA = {
            nome: <?= json_encode($nomeLoja) ?>,
            whatsapp: <?= json_encode($whatsapp) ?>,
            instagram: <?= json_encode($instagram) ?>,
            endereco: <?= json_encode($enderecoLoja) ?>
        };
    </script>
    <style>
        .swal2-confirm {
            background-color: #3085d6 !important;
            color: white !important;
            border: none !important;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 500;
            font-size: 14px;
        }

        .swal2-cancel {
            background-color: rgb(153, 35, 35) !important;
            color: white !important;
            border: none !important;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 500;
            font-size: 14px;
        }
    </style>

    <header>
        <div class="navbar bg-primary text-primary-content fixed top-0 left-0 w-full z-50 px-4 h-16">
            <!-- MOBILE: botão hambúrguer -->
            <div class="navbar-start lg:hidden">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost btn-square">
                        <i class="fas fa-bars text-xl"></i>
                    </label>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-primary rounded-box w-52">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="index.php?#cardapio">Cardápio</a></li>
                        <li><a href="index.php?#contato">Contato</a></li>

                        <?php if ($usuarioLogado): ?>

                            <?php if ($isAdmin): ?>
                                <li><a href="produtos.php">Produtos</a></li>
                                <li><a href="categorias.php">Categorias</a></li>
                                <li><a href="adicionais.php">Adicionais</a></li>
                            <?php else: ?>
                                <li><a href="meus_dados.php">Meus Dados</a></li>
                            <?php endif; ?>

                            <li><a href="meus_pedidos.php">Meus Pedidos</a></li>
                            <li><a id="btnLogout" class="text-red-400">Sair</a></li>

                        <?php else: ?>

                            <li><a href="login.php">Login</a></li>
                            <li><a href="cadastro.php">Cadastro</a></li>

                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- LOGO -->
            <div class="flex-1 justify-center lg:justify-start">
                <a href="index.php" class="btn btn-ghost normal-case text-xl font-bold tracking-wide">
                    <?= htmlspecialchars($nomeLoja) ?>
                </a>
            </div>

            <!-- DESKTOP: menu horizontal -->
            <div class="hidden lg:flex absolute left-1/2 transform -translate-x-1/2">
                <ul class="menu menu-horizontal px-1 gap-4">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php?#cardapio">Cardápio</a></li>
                    <li><a href="index.php?#contato">Contato</a></li>

                    <?php if ($usuarioLogado): ?>

                        <?php if ($isAdmin): ?>
                            <li><a href="produtos.php">Produtos</a></li>
                            <li><a href="categorias.php">Categorias</a></li>
                            <li><a href="adicionais.php">Adicionais</a></li>
                        <?php else: ?>
                            <li><a href="meus_dados.php">Meus Dados</a></li>
                        <?php endif; ?>

                        <li><a href="meus_pedidos.php">Meus Pedidos</a></li>
                        <li><a id="btnLogout" class="text-red-400">Sair</a></li>

                    <?php else: ?>

                        <li><a href="login.php">Login</a></li>
                        <li><a href="cadastro.php">Cadastro</a></li>

                    <?php endif; ?>
                </ul>
            </div>

            <!-- CARRINHO -->
            <div class="navbar-end gap-2">
                <a href="carrinho.php" class="btn btn-ghost btn-square relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="badge badge-sm badge-error absolute -top-1 -right-1">
                        <?= isset($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0 ?>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow mt-16">