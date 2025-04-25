<?php
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
    loadEnv(__DIR__ . '/../../../.env');
} catch (Exception $e) {
    die($e->getMessage());
}

// Configurações do banco de dados
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_DATABASE'];
$dbUser = $_ENV['DB_USERNAME'];
$dbPass = $_ENV['DB_PASSWORD'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica se o ID do usuário e o valor da aposta foram enviados
    if (isset($_POST["user_id"]) && isset($_POST["aposta"])) {
        // Obtém o ID do usuário e o valor da aposta do POST
        $userId = $_POST["user_id"];
        $valorAposta = $_POST["aposta"];

        // Aqui você deve incluir o arquivo de configuração do banco de dados
        // Conecta ao banco de dados (substitua 'localhost', 'username', 'password' e 'dbname' pelos valores reais)
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta para obter os dados da wallet do usuário com base no ID
            $stmtWallet = $pdo->prepare("SELECT * FROM wallets WHERE user_id = :userId");
            $stmtWallet->bindParam(":userId", $userId, PDO::PARAM_INT);
            $stmtWallet->execute();
            $wallet = $stmtWallet->fetch(PDO::FETCH_ASSOC);

            if (!$wallet) {
                // Se a wallet não for encontrada, envie uma resposta de erro
                http_response_code(404); // Not Found
                echo json_encode(array("message" => "Wallet do usuário não encontrada."));
                exit;
            }

            // Calcula o saldo disponível para apostas (balance + balance_bonus)
            $saldoDisponivel = $wallet['balance_withdrawal'] + $wallet['balance_bonus'];

            // Verifica se há saldo disponível para a aposta
            if ($saldoDisponivel < $valorAposta) {
                // Se não houver saldo suficiente, envie uma resposta de erro
                http_response_code(400); // Bad Request
                echo json_encode(array("message" => "Saldo insuficiente."));
                exit;
            }

            // Calcula o novo saldo após a aposta
            $novoSaldo = $wallet['balance_withdrawal'] - $valorAposta;
            if ($novoSaldo < 0) {
                // Se o saldo principal for insuficiente, deduz do saldo de bônus
                $novoSaldoBonus = $wallet['balance_bonus'] + $novoSaldo; // Adiciona a diferença ao saldo de bônus
                $novoSaldo = 0;
            } else {
                $novoSaldoBonus = $wallet['balance_bonus'];
            }

            // Atualiza os saldos na tabela de wallets
            $stmtUpdate = $pdo->prepare("UPDATE wallets SET balance_withdrawal = :novoSaldo, balance_bonus = :novoSaldoBonus WHERE user_id = :userId");
            $stmtUpdate->bindParam(":novoSaldo", $novoSaldo, PDO::PARAM_INT);
            $stmtUpdate->bindParam(":novoSaldoBonus", $novoSaldoBonus, PDO::PARAM_INT);
            $stmtUpdate->bindParam(":userId", $userId, PDO::PARAM_INT);
            $stmtUpdate->execute();

            // Retorna uma resposta de sucesso
            http_response_code(200); // OK
            echo json_encode(array("message" => "Aposta processada com sucesso.", "novoSaldo" => $novoSaldo, "novoSaldoBonus" => $novoSaldoBonus));
        } catch (PDOException $e) {
            // Em caso de erro, envie uma resposta de erro com a mensagem de erro
            error_log("Erro ao processar a aposta: " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Erro interno do servidor. Por favor, tente novamente mais tarde."));
        }

        // Fecha a conexão com o banco de dados
        unset($pdo);
    } else {
        // Se o ID do usuário ou o valor da aposta não foram enviados, envie uma resposta de erro
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "ID do usuário ou valor da aposta não foram fornecidos."));
    }
} else {
    // Se o método de requisição não for POST, envie uma resposta de erro
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Método não permitido."));
}
?>
