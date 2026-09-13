<?php 
    require_once 'vendor/autoload.php';

    include("autenticar.php");
    include("conexao.php");

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    $chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

    header('Content-Type: application/json; charset=utf-8');

    //Valida e captura o usuário logado via Token JWT
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

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $titulo_meta = $input['titulo_meta'] ?? '';
        $minuto_alvo = $input['minuto_alvo'] ?? '';
        $data_limite  = $input['data_limite'] ?? '';
        // Se não passar status, assume 'Pendente'
        $status       = $input['status'] ?? 'Pendente';

         if (empty($titulo_meta) || empty($minuto_alvo) || empty($data_limite)) {
            echo json_encode(["erro" => "Por favor, preencha todos os campos."]);
            exit;
        } 

        $sql = "INSERT INTO metas (titulo_meta, minuto_alvo, data_limite, status) VALUES (?, ?, ?, ?, ?, ?)";
         $stmt = $mysqli->prepare($sql);

        if($stmt){
            $stmt->bind_param("iissss", $titulo_meta, $minuto_alvo, $data_limite, $status);

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
        echo json_encode(["erro" => "Erro de sintaxe no servidor."]);
    }
        
    }
?>