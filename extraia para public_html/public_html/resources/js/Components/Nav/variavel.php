<?php

// Verifica se o método de requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica se o nome de usuário e o valor da aposta foram enviados
    if (isset($_POST["username"])) {
        // Obtém o nome de usuário e o valor da aposta do POST
        $username = $_POST["username"];

        // Aqui você deve incluir o arquivo de configuração do banco de dados
        // Conecta ao banco de dados (substitua 'localhost', 'username', 'password' e 'dbname' pelos valores reais)
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=gugagg", "gugagg", "Swich@90");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consulta para obter os dados do usuário com base no nome de usuário
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE name = :username");
            $stmtUser->bindParam(":username", $username, PDO::PARAM_STR);
            $stmtUser->execute();
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Se o usuário não for encontrado, envie uma resposta de erro
                http_response_code(404); // Not Found
                echo json_encode(array("message" => "Usuário não encontrado."));
                exit;
            }

            // Obtém o valor da variável 'app' do usuário
            $appVariable = $user['app'];

            // Retorna a variável 'app' no JSON
            echo json_encode(array("app" => $appVariable));
        } catch (PDOException $e) {
            // Em caso de erro, envie uma resposta de erro com a mensagem de erro
            error_log("Erro ao processar a aposta: " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Erro interno do servidor. Por favor, tente novamente mais tarde."));
        }

        // Fecha a conexão com o banco de dados
        unset($pdo);
    } else {
        // Se o nome de usuário ou o valor da aposta não foram enviados, envie uma resposta de erro
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Nome de usuário ou valor da aposta não foram fornecidos."));
    }
} else {
    // Se o método de requisição não for POST, envie uma resposta de erro
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Método não permitido."));
}
?>
