<?php
require_once 'conexao.php';
session_start();

$usuarioLogado = $_SESSION['usuario'] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pizzaria Bella Massa - Pedido Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@2.51.5/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="flex flex-col min-h-screen bg-base-200">

    <header>
        <div class="navbar bg-primary text-primary-content fixed top-0 left-0 w-full z-50 px-4 h-16">
            <!-- Mobile -->
            <div class="navbar-start lg:hidden">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost btn-square">
                        <i class="fas fa-bars text-xl"></i>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-primary rounded-box w-52">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="index.php?#cardapio">Cardápio</a></li>
                        <li><a href="index.php?#contato">Contato</a></li>
                        <?php if ($usuarioLogado): ?>
                            <li><a href="meus_pedidos.php">Meus Pedidos</a></li>
                            <li><a href="logout.php" class="text-red-400">Sair</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Logo -->
            <div class="flex-1 justify-center lg:justify-start">
                <a href="index.php" class="btn btn-ghost normal-case text-xl font-bold tracking-wide">Pizzaria Bella Massa</a>
            </div>

            <!-- Desktop menu -->
            <div class="hidden lg:flex absolute left-1/2 transform -translate-x-1/2">
                <ul class="menu menu-horizontal px-1 gap-4">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php?#cardapio">Cardápio</a></li>
                    <li><a href="index.php?#contato">Contato</a></li>
                </ul>
            </div>

            <!-- Carrinho + Login/Profile -->
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

    <main class="flex-grow mt-12">
