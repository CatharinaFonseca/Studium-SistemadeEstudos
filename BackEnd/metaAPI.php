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
        echo json_encode(["erro" => "Sessão inválida ou expirada."]);
        exit;
    }

    $metodo = $_SERVER['REQUEST_METHOD'];

    // Listar
    if ($metodo === 'GET') {
        $sql = "SELECT m.id_meta, m.id_materia, m.titulo_meta, m.minuto_alvo, m.data_limite, m.status_meta, mat.nome_materia 
                FROM meta m 
                LEFT JOIN materia mat ON m.id_materia = mat.id_materia 
                WHERE m.id_usuario = ? 
                ORDER BY m.id_meta DESC";

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $metas = $resultado->fetch_all(MYSQLI_ASSOC);

            echo json_encode($metas);
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao listar metas."]);
        }
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Cadastrar meta
    if ($metodo === 'POST') {
        $id_materia  = $input['id_materia'] ?? null;
        $titulo_meta = trim($input['titulo_meta'] ?? '');
        $minuto_alvo = $input['minuto_alvo'] ?? '';
        $data_limite = !empty($input['data_limite']) ? $input['data_limite'] : null;
        $status_meta = !empty($input['status_meta']) ? $input['status_meta'] : 'Pendente';

        if (empty($id_materia) || empty($titulo_meta) || empty($minuto_alvo)) {
            http_response_code(400);
            echo json_encode(["erro" => "Preencha a matéria, o título e os minutos alvo."]);
            exit;
        }

        $sql = "INSERT INTO meta (id_materia, id_usuario, titulo_meta, minuto_alvo, data_limite, status_meta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("iisiss", $id_materia, $id_usuario, $titulo_meta, $minuto_alvo, $data_limite, $status_meta);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["mensagem" => "Meta cadastrada com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao cadastrar meta no banco de dados."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta."]);
        }
        exit;
    }

    // Alterar meta
    if ($metodo === 'PUT') {
        $id_meta     = $input['id_meta'] ?? null;
        $id_materia  = $input['id_materia'] ?? null;
        $titulo_meta = trim($input['titulo_meta'] ?? '');
        $minuto_alvo = $input['minuto_alvo'] ?? '';
        $data_limite = !empty($input['data_limite']) ? $input['data_limite'] : null;
        $status_meta = $input['status_meta'] ?? 'Pendente';

        if (empty($id_meta) || empty($id_materia) || empty($titulo_meta) || empty($minuto_alvo)) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos para atualização."]);
            exit;
        }

        $sql = "UPDATE meta SET id_materia = ?, titulo_meta = ?, minuto_alvo = ?, data_limite = ?, status_meta = ? WHERE id_meta = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("isissii", $id_materia, $titulo_meta, $minuto_alvo, $data_limite, $status_meta, $id_meta, $id_usuario);

            if ($stmt->execute()) {
                echo json_encode(["mensagem" => "Meta atualizada com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao atualizar meta."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro na preparação da consulta de atualização."]);
        }
        exit;
    }

    // Excluir meta 
    if ($metodo === 'DELETE') {
        $id_meta = $input['id_meta'] ?? null;

        if (empty($id_meta)) {
            http_response_code(400);
            echo json_encode(["erro" => "ID da meta não fornecido."]);
            exit;
        }

        $sql = "DELETE FROM meta WHERE id_meta = ? AND id_usuario = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $id_meta, $id_usuario);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(["mensagem" => "Meta excluída com sucesso!"]);
                } else {
                    http_response_code(404);
                    echo json_encode(["erro" => "Meta não encontrada ou sem permissão."]);
                }
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao excluir meta."]);
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