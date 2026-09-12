<?php
    require_once 'vendor/autoload.php';

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    $chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

    //Captura todos os cabeçalhos da requisição
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["erro" => "Token não fornecido ou inválido."]);
        exit;
    }

    // Extrai apenas a string do token
    $jwt = $matches[1]; 

    try {
        //Decodifica e valida a assinatura e data de expiração do JWT
        $dados_usuario = JWT::decode($jwt, new Key($chave_secreta, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["erro" => "Token inválido ou expirado: " . $e->getMessage()]);
        exit;
}