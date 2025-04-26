<?php
// proxy_google.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Só permite se o usuário estiver logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Confere se veio a URL
if (!isset($_GET['url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'URL não informada']);
    exit;
}

// Valida que a URL é da API do Google Distance Matrix
$url = $_GET['url'];

if (strpos($url, 'https://maps.googleapis.com/maps/api/distancematrix/json') !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'URL não autorizada']);
    exit;
}

// Faz a requisição segura para o Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao Google Maps']);
    exit;
}

curl_close($ch);

// Retorna a resposta do Google para o front
header('Content-Type: application/json');
echo $response;
