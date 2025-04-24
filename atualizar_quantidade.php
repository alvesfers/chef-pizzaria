<?php
session_start();

if (!isset($_POST['index'], $_POST['acao'])) {
    header('Location: carrinho.php');
    exit;
}

$index = (int) $_POST['index'];
$acao = $_POST['acao'];

if (isset($_SESSION['carrinho'][$index])) {
    if ($acao === 'aumentar') {
        $_SESSION['carrinho'][$index]['quantidade'] += 1;
    } elseif ($acao === 'diminuir' && $_SESSION['carrinho'][$index]['quantidade'] > 1) {
        $_SESSION['carrinho'][$index]['quantidade'] -= 1;
    }
}

header('Location: carrinho.php');
exit;
