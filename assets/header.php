<?php
// assets/header.php
require_once 'assets/conexao.php';
session_start();

$usuarioLogado = $_SESSION['usuario'] ?? null;
$tipoUsuario   = $usuarioLogado['tipo_usuario'] ?? 'cliente';
$isAdmin       = in_array($tipoUsuario, ['admin', 'funcionario']);

$dadosLoja    = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nomeLoja     = $dadosLoja['nome_loja'] ?? 'Pizzaria';
$whatsapp     = preg_replace('/\D/', '', $dadosLoja['whatsapp'] ?? '');
$instagram    = $dadosLoja['instagram'] ?? null;
$enderecoLoja = $dadosLoja['endereco_completo'] ?? '';
$emailLoja    = $dadosLoja['email'] ?? '';
$tema         = $dadosLoja['tema'] ?? 'light';
$teste        = $dadosLoja['ambiente_teste'] ?? 0;

date_default_timezone_set('America/Sao_Paulo');

$mapaDias = [
    'Monday'    => 'segunda',
    'Tuesday'   => 'terça',
    'Wednesday' => 'quarta',
    'Thursday'  => 'quinta',
    'Friday'    => 'sexta',
    'Saturday'  => 'sábado',
    'Sunday'    => 'domingo',
];

$horaAtual        = date('H:i:s');
$diaIngles        = date('l');
$diaSemana        = $mapaDias[$diaIngles] ?? '';
$diaInglesOntem   = date('l', strtotime('-1 day'));
$diaSemanaOntem   = $mapaDias[$diaInglesOntem] ?? '';

// Consulta de horários
$stmtHoje = $pdo->prepare("
    SELECT hora_abertura, hora_fechamento
      FROM tb_horario_atendimento
     WHERE dia_semana = ? AND ativo = 1
     LIMIT 1
");
$stmtHoje->execute([$diaSemana]);
$horarioHoje = $stmtHoje->fetch(PDO::FETCH_ASSOC);

$stmtOntem = $pdo->prepare("
    SELECT hora_abertura, hora_fechamento
      FROM tb_horario_atendimento
     WHERE dia_semana = ? AND ativo = 1
     LIMIT 1
");
$stmtOntem->execute([$diaSemanaOntem]);
$horarioOntem = $stmtOntem->fetch(PDO::FETCH_ASSOC);

function estaAberta($horaAtual, $abertura, $fechamento)
{
    if ($abertura <= $fechamento) {
        return $horaAtual >= $abertura && $horaAtual <= $fechamento;
    } else {
        return $horaAtual >= $abertura || $horaAtual <= $fechamento;
    }
}

$abertaHoje = $horarioHoje
    ? estaAberta($horaAtual, $horarioHoje['hora_abertura'], $horarioHoje['hora_fechamento'])
    : false;

$abertaOntemOvernight = false;
if ($horarioOntem && $horarioOntem['hora_abertura'] > $horarioOntem['hora_fechamento']) {
    $abertaOntemOvernight = estaAberta($horaAtual, $horarioOntem['hora_abertura'], $horarioOntem['hora_fechamento']);
}

$aberta = $abertaHoje || $abertaOntemOvernight;

if ($abertaHoje) {
    $diaRegra = $diaSemana;
} elseif ($abertaOntemOvernight) {
    $diaRegra = $diaSemanaOntem;
} else {
    $diaRegra = null;
}

$aberta = ($dadosLoja['usar_horarios'] == 0) ? 0 : 1;

$statusLoja = $aberta
    ? "Estamos aceitando pedidos!"
    : "Estamos fechados no momento.";
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= htmlspecialchars($tema) ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($nomeLoja) ?> - Pedido Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/daisyui@2.51.5/dist/full.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/logo">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo">
</head>

<body class="flex flex-col min-h-screen bg-base-200">
    <script>
        window.LOJA = {
            nome: <?= json_encode($nomeLoja) ?>,
            whatsapp: <?= json_encode($whatsapp) ?>,
            instagram: <?= json_encode($instagram) ?>,
            instagram: <?= json_encode($instagram) ?>,
            teste: <?= json_encode($teste) ?>
        };
    </script>
    <style>
        .swal2-confirm {
            background-color: #3085d6 !important;
            color: white !important;
        }

        .swal2-cancel {
            background-color: #993323 !important;
            color: white !important;
        }

        @media (min-width:768px) and (max-width:1023px) {
            .menu-horizontal>li>a {
                padding-left: .5rem;
                padding-right: .5rem;
            }
        }
    </style>

    <header>
        <div class="navbar bg-primary text-primary-content fixed top-0 left-0 w-full z-50 px-4 h-16">
            <!-- Início / hambúrguer -->
            <div class="navbar-start">
                <!-- mobile: hambúrguer -->
                <div class="lg:hidden">
                    <div class="dropdown">
                        <label tabindex="0" class="btn btn-ghost btn-square">
                            <i class="fas fa-bars text-xl"></i>
                        </label>
                        <ul tabindex="0"
                            class="menu menu-sm dropdown-content mt-3 p-2 shadow bg-primary rounded-box w-52">
                            <li><a href="index.php">Início</a></li>
                            <li><a href="index.php?#cardapio">Cardápio</a></li>
                            <?php if ($isAdmin): ?>
                                <li><a href="atendimento.php">Atendimento</a></li>
                                <li tabindex="0">
                                    <a>Gerenciar</a>
                                    <ul class="p-2 bg-primary">
                                        <li><a href="produtos.php">Produtos</a></li>
                                        <li><a href="categorias.php">Categorias</a></li>
                                        <li><a href="adicionais.php">Adicionais</a></li>
                                        <li><a href="cupons.php">Cupons</a></li>
                                        <li><a href="usuarios.php">Usuários</a></li>
                                    </ul>
                                </li>
                                <li><a href="loja.php">Minha Loja</a></li>
                            <?php else: ?>
                                <?php if ($usuarioLogado): ?>
                                    <li><a href="meus_dados.php">Meus Dados</a></li>
                                    <li><a href="meus_pedidos.php">Meus Pedidos</a></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($usuarioLogado): ?>
                                <li><a id="btnLogout" class="text-red-400">Sair</a></li>
                            <?php else: ?>
                                <li><a href="login.php">Entrar</a></li>
                                <li><a href="cadastro.php">Cadastrar</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <!-- desktop: logo -->
                <a href="index.php" class="btn btn-ghost normal-case text-xl font-bold tracking-wide hidden lg:inline-flex">
                    <?= htmlspecialchars($nomeLoja) ?>
                </a>
            </div>

            <a href="index.php" class="btn btn-ghost normal-case text-xl font-bold tracking-wide lg:hidden">
                <?= htmlspecialchars($nomeLoja) ?>
            </a>
            <!-- Menu centralizado em desktop -->
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal gap-4">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php?#cardapio">Cardápio</a></li>

                    <?php if ($isAdmin): ?>
                        <li><a href="atendimento.php">Atendimento</a></li>
                        <li tabindex="0">
                            <a>Gerenciar</a>
                            <ul class="p-2 bg-primary">
                                <li><a href="produtos.php">Produtos</a></li>
                                <li><a href="categorias.php">Categorias</a></li>
                                <li><a href="adicionais.php">Adicionais</a></li>
                                <li><a href="cupons.php">Cupons</a></li>
                                <li><a href="usuarios.php">Usuários</a></li>
                            </ul>
                        </li>
                        <li><a href="loja.php">Minha Loja</a></li>
                    <?php else: ?>
                        <?php if ($usuarioLogado): ?>
                            <li><a href="meus_dados.php">Meus Dados</a></li>
                            <li><a href="meus_pedidos.php">Meus Pedidos</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($usuarioLogado): ?>
                        <li><a id="btnLogout" class="text-red-400">Sair</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Entrar</a></li>
                        <li><a href="cadastro.php">Cadastrar</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Carrinho no final, sempre visível -->
            <div class="navbar-end">
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