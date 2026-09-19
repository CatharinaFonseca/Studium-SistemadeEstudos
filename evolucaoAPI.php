<?php
require_once 'vendor/autoload.php';

include("autenticar.php");
include("conexao.php");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

header('Content-Type: application/json; charset=utf-8');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado."]);
    exit;
}

try {
    $jwt = $matches[1];
    $dados_token = JWT::decode($jwt, new Key($chave_secreta, 'HS256'));
    $id_usuario = $dados_token->sub;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["erro" => "Sessão inválida."]);
    exit;
}

// Busca estudos dos últimos 14 dias agrupados por data
$sql = "SELECT DATE_FORMAT(data_registro_estudo, '%d/%m/%Y') as data_fmt, SUM(minuto_dedicado) as total_minutos
        FROM registro_estudo
        WHERE id_usuario = ? AND data_registro_estudo >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY data_registro_estudo
        ORDER BY data_registro_estudo ASC";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $datas = [];
    $minutos = [];

    while ($linha = $resultado->fetch_assoc()) {
        $datas[] = $linha['data_fmt'];
        $minutos[] = (int)$linha['total_minutos'];
    }

    echo json_encode([
        "labels" => $datas,
        "datasets" => $minutos
    ]);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao carregar evolução diária."]);
}
?>