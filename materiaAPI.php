<?php 

    header('Content-Type: application/json; charset=utf-8');
    
    include("autenticar.php");
    include("conexao.php");

    //Valida e captura o usuário logado via Token JWT
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $nome_materia = $input['nome_materia'] ?? '';
        $cor_materia = $input['cor_materia'] ?? '#706f6f';;

        if (empty($nome_materia)) {
            http_response_code(400);
            echo json_encode(["erro" => "Por favor, preencha o nome da matéria."]);
            exit;
    }

        $sql = "INSERT INTO materias (nome_materia, cor_materia) VALUES (?, ?)";
        $stmt = $mysqli->prepare($sql);

        if($stmt){
            $stmt->bind_param("iss", $nome_materia, $cor_materia);

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
        echo json_encode(["erro" => "Erro de sintaxe no servidor."]);
    }
        
    }
?>