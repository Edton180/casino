<?php

// Carregar as variáveis do arquivo .env
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    die($e->getMessage());
}

// Configurações do banco de dados
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_DATABASE'];
$dbUser = $_ENV['DB_USERNAME'];
$dbPass = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexão bem-sucedida\n";

    $sql = "UPDATE wallets SET cashbackliquido = 1 WHERE cashback > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo "Atualização concluída: " . $stmt->rowCount() . " registros atualizados.\n";

} catch (PDOException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n";
}

