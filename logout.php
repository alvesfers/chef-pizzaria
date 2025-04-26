<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Remove apenas o usuário da sessão
unset($_SESSION['usuario']);

echo json_encode(['status' => 'ok']);
