<?php
    //vamos montar a pasta gold_api 
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
    function webhook($data){
        // URL de SUA API
        $url = 'https://webhook.site/f71176ba-fe8d-44f4-897a-5f1c9341900e';
        // cria um resource cURL
        $ch = curl_init($url);
        // monte um array que conterá os campos a serem enviados
        // Vamos neste tutorial optar por usar um array para montar os campos de post. Este é o campo de form-data.
        // Nossa api específica espera um array em formato JSON com uma única chave "text" e seu respectivo conteúdo que será analisado.
        $data = $data;
        // vamos usar a função json encode para transformar nosso array em uma string Json válida
        $corpo = json_encode($data);// agora vamos anexar o corpo em formato json da sua requisição
        curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
        // defina o conteúdo do envio como json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        // ative o recebimento de retorno da requisição
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // executa a requisição POST
        $resultado = curl_exec($ch);
        // encerra conexão e libera variável
        curl_close($ch);
    }
        webhook($data);
        $responseData = [
            'id_user' => $data['user_code'],
            'saldo' => $data['user_balance']
        ];
        $jsondata = $responseData ;
        webhook($jsondata)

        //webhook($data['user_balance']);

        echo json_encode($responseData);

?>