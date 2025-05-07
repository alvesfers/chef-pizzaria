<?php
$host = 'localhost';         // ou o IP do seu servidor
$db   = 'pizza';             // nome do banco de dados
$user = 'root';              // usuário do banco
$pass = '';                  // senha do banco
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lança exceções em erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // usa prepares reais
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Você pode registrar esse erro em log em produção
    exit('Erro na conexão com o banco de dados: ' . $e->getMessage());
}
?>
