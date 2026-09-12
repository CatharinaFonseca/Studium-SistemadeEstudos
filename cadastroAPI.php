<?php 

    include("conexao.php");

    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $nome_usuario = ($input['nome_usuario'] ?? $_POST['nome_usuario'] ?? '');
        $email_usuario = ($input['email_usuario'] ?? $_POST['email_usuario'] ?? '');
        $senha_usuario = trim($input['senha_usuario'] ?? $input['senha'] ?? $_POST['senha_usuario'] ?? '');

        if (empty($nome_usuario) || empty($email_usuario) || empty($senha_usuario)) {
            echo json_encode(["erro" => "Por favor, preencha todos os campos."]);
            exit;
        } 

        //Garantindo que o e-mail não está cadastrado
        $sql_check = "SELECT id_usuario FROM usuario WHERE email_usuario = ?";
        $stmt_check = $mysqli->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email_usuario);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();

            if ($res_check->num_rows > 0) {
                http_response_code(400);
                echo json_encode(["erro" => "Este e-mail já está cadastrado!"]);
                $stmt_check->close();
                exit;
            }
            $stmt_check->close();
        }

        $senha_usuario = password_hash($senha_usuario, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuario (nome_usuario, email_usuario, senha_usuario) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
        
            $stmt->bind_param("sss", $nome_usuario, $email_usuario, $senha_usuario);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["mensagem" => "Cadastro realizado com sucesso!"]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao cadastrar no banco de dados."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro de sintaxe no servidor."]);
        }
    }

?>