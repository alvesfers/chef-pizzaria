<?php
session_start();

if (isset($_POST['index']) && isset($_SESSION['carrinho'][$_POST['index']])) {
    unset($_SESSION['carrinho'][$_POST['index']]);
    $_SESSION['carrinho'] = array_values($_SESSION['carrinho']); // reindexa
}

header("Location: carrinho.php");
exit;
