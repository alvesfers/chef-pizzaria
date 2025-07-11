<?php
// assets/conexao.php

/**
 * Carrega variáveis do arquivo .env na raiz do projeto
 *
 * @param string $path Caminho completo para o .env
 */
function loadEnv(string $path): void
{
    if (! file_exists($path)) {
        throw new RuntimeException(".env não encontrado em: $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // ignora comentários e linhas vazias
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        // só linhas com chave=valor
        if (! strpos($line, '=')) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        // remove aspas simples ou duplas se existirem
        if ((substr($value, 0, 1) === '"'  && substr($value, -1) === '"')
            || (substr($value, 0, 1) === "'"  && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }
        // popula $_ENV e getenv()
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// 1) Carrega o .env (ajuste o caminho conforme sua estrutura)
loadEnv(__DIR__ . '/../.env');

// 2) Lê as variáveis de ambiente
$host    = getenv('DB_HOST')    ?: 'localhost';
$db      = getenv('DB_NAME')    ?: 'pizza';
$user    = getenv('DB_USER')    ?: 'root';
$pass    = getenv('DB_PASS')    ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

// 3) Prepara o DSN e opções do PDO
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4) Conecta
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erro na conexão com o banco de dados: ' . $e->getMessage());
}
