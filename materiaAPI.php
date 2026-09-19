<?php 
header('Content-Type: application/json; charset=utf-8');

include("autenticar.php"); // Assume-se que 'autenticar.php' já valida o JWT e define a variável $id_usuario
include("conexao.php");    // Contém a variável $mysqli

// Captura o método HTTP (GET, POST, PUT, DELETE)
$metodo = $_SERVER['REQUEST_METHOD'];

// Lê os dados JSON enviados pelo JavaScript (se houver)
$input = json_decode(file_get_contents('php://input'), true);

switch ($metodo) {

    // --- 1. LISTAR MATÉRIAS (GET) ---
    case 'GET':
        $sql = "SELECT id_materia, nome_materia, cor_materia FROM materia WHERE id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();

            $materias = [];
            while ($linha = $resultado->fetch_assoc()) {
                $materias[] = $linha;
            }

            http_response_code(200);
            echo json_encode($materias);
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao buscar matérias no banco de dados."]);
        }
        break;

    // --- 2. CADASTRAR MATÉRIA (POST) ---
    case 'POST':
        $nome_materia = $input['nome_materia'] ?? '';
        $cor_materia  = $input['cor_materia'] ?? '#706f6f';

        if (empty($nome_materia)) {
            http_response_code(400);
            echo json_encode(["erro" => "Por favor, preencha o nome da matéria."]);
            exit;
        }

        $sql = "INSERT INTO materia (id_usuario, nome_materia, cor_materia) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("iss", $id_usuario, $nome_materia, $cor_materia);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["mensagem" => "Matéria cadastrada com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao cadastrar matéria no banco de dados."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta no servidor."]);
        }
        break;

    // --- 3. ALTERAR MATÉRIA (PUT) ---
    case 'PUT':
        $id_materia   = $input['id_materia'] ?? null;
        $nome_materia = $input['nome_materia'] ?? '';
        $cor_materia  = $input['cor_materia'] ?? '#706f6f';

        if (empty($id_materia) || empty($nome_materia)) {
            http_response_code(400);
            echo json_encode(["erro" => "ID e nome da matéria são obrigatórios para atualização."]);
            exit;
        }

        $sql = "UPDATE materia SET nome_materia = ?, cor_materia = ? WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssii", $nome_materia, $cor_materia, $id_materia, $id_usuario);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["mensagem" => "Matéria alterada com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao atualizar a matéria."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta no servidor."]);
        }
        break;

    // --- 4. APAGAR MATÉRIA (DELETE) ---
    case 'DELETE':
        $id_materia = $input['id_materia'] ?? null;

        if (empty($id_materia)) {
            http_response_code(400);
            echo json_encode(["erro" => "ID da matéria não informado."]);
            exit;
        }

        $sql = "DELETE FROM materia WHERE id_materia = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $id_materia, $id_usuario);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["mensagem" => "Matéria excluída com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao apagar matéria."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta no servidor."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
        break;
}
?>