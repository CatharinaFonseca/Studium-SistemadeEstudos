<?php 
    require_once 'vendor/autoload.php';
    include("conexao.php");

    use Firebase\JWT\JWT;

    $chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

    header('Content-Type: application/json; charset=utf-8');

    $erro = [];

    $resposta =[];

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        // Para ler dados enviados via JSON no PHP
        $input = json_decode(file_get_contents('php://input'), true);

        $email_usuario = trim($_POST['email'] ?? $input['email'] ?? '');
        $senha_usuario = trim($_POST['senha'] ?? $input['senha'] ?? '');

        if(empty($email_usuario) || empty($senha_usuario)){
            echo json_encode( $erro[] = "Preencha todos os campos!");
            exit;
        } else{
            $sql = "SELECT senha_usuario, nome_usuario, id_usuario FROM usuario WHERE email_usuario = ?";

            $stmt = $mysqli->prepare($sql);

            if($stmt){
                $stmt->bind_param("s", $email_usuario);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows === 0){
                    $erro[] = "Email ou senha incorretos!";
                } else {
                    $usuario = $result->fetch_assoc();
                    if(password_verify($senha_usuario, $usuario['senha_usuario'])){

                    $tempo_atual = time();
                    $tempo_expiracao = $tempo_atual + (60 * 60 * 8);

                    $payload = [
                        'iat' => $tempo_atual,                  
                        'exp' => $tempo_expiracao,             
                        'sub' => $usuario['id_usuario'],        
                        'nome' => $usuario['nome_usuario']      
                    ];
                    
                    $jwt = JWT::encode($payload, $chave_secreta, 'HS256');

                    // Retorna o token para o cliente
                    http_response_code(200);
                    echo json_encode([
                        "mensagem" => "Login realizado com sucesso!",
                        "token" => $jwt,
                        "expira_em" => date('Y-m-d H:i:s', $tempo_expiracao)
                    ]);

                        //header("Location: index.php");
                    } else {
                        echo json_encode($erro[] = "Email ou senha incorretos!");
                    }
                }

                $stmt->close();
            } else {
                echo json_encode($erro[] = "Erro interno ao processar login.");
            }
         
        }
    }

?>