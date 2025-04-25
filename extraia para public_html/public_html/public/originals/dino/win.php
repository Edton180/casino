<?php
error_log('Recebendo a requisição...');

// Verifica se a conexão é segura ou não
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Obtém o host do servidor
$host = $_SERVER['HTTP_HOST'];

// Combina o protocolo e o host para formar a base da URL
$baseUrl = $protocol . $host;

// Verifica se a requisição contém os dados necessários
if (isset($_POST['val'], $_POST['bet'], $_POST['token'])) {
    error_log('Dados recebidos: val=' . $_POST['val'] . ', bet=' . $_POST['bet'] . ', token=' . $_POST['token']);

    // Verifica se a operação já foi realizada
    if (isset($_SESSION['operation_completed']) && $_SESSION['operation_completed']) {
        error_log('Operação já foi realizada anteriormente na sessão atual.');
        echo json_encode(['success' => false, 'message' => 'Operação já realizada.']);
        exit; // Interrompe a execução do script
    }

    $valorAcumulado = $_POST['val'];
    $entrada = $_POST['bet'];
    $token = $_POST['token'];

    // Concatena a base da URL com o caminho do endpoint
    $fullUrl = $baseUrl . "/vgames/game/sub";

    // Inicializa o cURL
    $ch = curl_init($fullUrl);
    error_log('Iniciando chamada ao endpoint...');

    // Configura as opções do cURL
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'bet' => $entrada,
        'val' => $valorAcumulado,
        'token' => $token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a chamada ao endpoint
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Erro ao executar a chamada cURL: ' . curl_error($ch));
    }

    // Fecha a sessão cURL
    curl_close($ch);

    // Decodifica a resposta JSON
    $responseData = json_decode($response, true);
    error_log('Resposta recebida: ' . $response);

    // Verifica a resposta
    if (isset($responseData['success']) && $responseData['success']) {
        $_SESSION['operation_completed'] = true; // Marca a operação como concluída
        error_log('Saldo adicionado com sucesso.');
        echo json_encode(['success' => true, 'message' => 'Saldo adicionado com sucesso.']);
    } else {
        error_log('Falha ao adicionar saldo.');
        echo json_encode(['success' => false, 'message' => 'Falha ao adicionar saldo.']);
    }
} else {
    error_log('Dados incompletos recebidos.');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
}

?>
