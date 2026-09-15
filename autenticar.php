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
        $decoded = JWT::decode($jwt, new Key($chave_secreta, 'HS256'));
        // Token é válido! $decoded contém as informações do usuário.
        $id_usuario = $decoded->sub;
    } catch (Exception $e) {
        http_response_code(401);
        // EXIBE O ERRO REAL QUE O JWT RETORNOU:
        echo json_encode([
            "erro" => "Sessão inválida",
            "detalhe" => $e->getMessage()
        ]);
        exit();
    }