<?php
include("autenticar.php");
include("conexao.php");

header('Content-Type: application/json; charset=utf-8');


$sql = "SELECT id_materia, nome_materia FROM materia WHERE id_usuario = ?";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = [];
    while ($linha = $result->fetch_assoc()) {
        $materias[] = $linha;
    }

    echo json_encode($materias);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao carregar matérias."]);
}