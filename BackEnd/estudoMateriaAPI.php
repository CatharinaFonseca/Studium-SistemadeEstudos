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

// Consulta agrupada trazendo a cor cadastrada da matéria
$sql = "SELECT mat.nome_materia, mat.cor_materia, SUM(r.minuto_dedicado) as total_minutos
        FROM registro_estudo r
        INNER JOIN materia mat ON r.id_materia = mat.id_materia
        WHERE r.id_usuario = ?
        GROUP BY r.id_materia, mat.nome_materia, mat.cor_materia";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $labels = [];
    $data = [];
    $cores = [];

    while ($linha = $resultado->fetch_assoc()) {
        $labels[] = $linha['nome_materia'];
        $data[]   = (int)$linha['total_minutos'];
        // Se a matéria não tiver cor cadastrada, define uma cor padrão
        $cores[]  = !empty($linha['cor_materia']) ? $linha['cor_materia'] : '#36A2EB';
    }

    echo json_encode([
        "labels"   => $labels,
        "datasets" => $data,
        "cores"    => $cores
    ]);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar dados do gráfico."]);
}
?>