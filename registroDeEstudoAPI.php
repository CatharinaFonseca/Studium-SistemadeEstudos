<?php 
    require_once 'vendor/autoload.php';

    include("autenticar.php");
    include("conexao.php");

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    $chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

    header('Content-Type: application/json; charset=utf-8');

    // Valida e captura o usuário logado via Token JWT
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
        echo json_encode(["erro" => "Sessão inválida ou expirada."]);
        exit;
    }

    $metodo = $_SERVER['REQUEST_METHOD'];

    // Listar
    if ($metodo === 'GET') {
        $sql = "SELECT r.id_registro_estudo, r.id_materia, r.minuto_dedicado, r.data_registro_estudo, r.observacao, r.id_meta, mat.nome_materia 
                FROM registro_estudo r 
                LEFT JOIN materia mat ON r.id_materia = mat.id_materia 
                WHERE r.id_usuario = ? 
                ORDER BY r.data_registro_estudo DESC, r.id_registro_estudo DESC";

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $registros = $resultado->fetch_all(MYSQLI_ASSOC);

            echo json_encode($registros);
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao listar registros de estudo."]);
        }
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Cadastrar Registro
    if ($metodo === 'POST') {
        $id_materia           = $input['id_materia'] ?? null;
        $id_meta              = !empty($input['id_meta']) ? (int)$input['id_meta'] : null;
        $minuto_dedicado      = $input['minuto_dedicado'] ?? '';
        $data_registro_estudo = $input['data_registro_estudo'] ?? '';
        $observacao           = trim($input['observacao'] ?? '');

        if (empty($id_materia) || empty($minuto_dedicado) || empty($data_registro_estudo)) {
            http_response_code(400);
            echo json_encode(["erro" => "Por favor, preencha a matéria, minutos e data."]);
            exit;
        }

        $sql = "INSERT INTO registro_estudo (id_materia, id_usuario, minuto_dedicado, data_registro_estudo, observacao, id_meta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("iiissi", $id_materia, $id_usuario, $minuto_dedicado, $data_registro_estudo, $observacao, $id_meta);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["mensagem" => "Registro de estudo cadastrado com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao cadastrar registro de estudo no banco de dados."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro de sintaxe no servidor."]);
        }
        exit;
    }

    // Atualizar Registro
    if ($metodo === 'PUT') {
        $id_registro_estudo   = $input['id_registro_estudo'] ?? null;
        $id_materia           = $input['id_materia'] ?? null;
        $id_meta              = !empty($input['id_meta']) ? (int)$input['id_meta'] : null;
        $minuto_dedicado      = $input['minuto_dedicado'] ?? '';
        $data_registro_estudo = $input['data_registro_estudo'] ?? '';
        $observacao           = trim($input['observacao'] ?? '');

        if (empty($id_registro_estudo) || empty($id_materia) || empty($minuto_dedicado) || empty($data_registro_estudo)) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos para atualização do registro."]);
            exit;
        }

        $sql = "UPDATE registro_estudo SET id_materia = ?, minuto_dedicado = ?, data_registro_estudo = ?, observacao = ?, id_meta = ? WHERE id_registro_estudo = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("iissiii", $id_materia, $minuto_dedicado, $data_registro_estudo, $observacao, $id_meta, $id_registro_estudo, $id_usuario);

            if ($stmt->execute()) {
                echo json_encode(["mensagem" => "Registro de estudo atualizado com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao atualizar registro de estudo."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta de atualização."]);
        }
        exit;
    }

    // Excluir Registro
    if ($metodo === 'DELETE') {
        $id_registro_estudo = $input['id_registro_estudo'] ?? null;

        if (empty($id_registro_estudo)) {
            http_response_code(400);
            echo json_encode(["erro" => "ID do registro de estudo não fornecido."]);
            exit;
        }

        $sql = "DELETE FROM registro_estudo WHERE id_registro_estudo = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $id_registro_estudo, $id_usuario);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(["mensagem" => "Registro de estudo excluído com sucesso!"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["erro" => "Registro não encontrado ou sem permissão."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao excluir registro de estudo."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta de exclusão."]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido."]);
?>