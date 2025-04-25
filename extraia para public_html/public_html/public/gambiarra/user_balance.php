<?php
header('Content-Type: application/json');
// Receber dados da solicitação POST JSON
$data = json_decode(file_get_contents("php://input"), true);
// Verificar se o JSON foi decodificado com sucesso
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    // Erro ao decodificar o JSON
     http_response_code(400); // Bad Request
    //echo json_encode(array('error' => 'Erro na decodificação do JSON.'));
    exit;
} 


$responseData = [
	'user_balance' => 25000
];

echo json_encode($data);