<?php 

    if (!isset($_SESSION)) {
        session_start();
    } 

    include("conexao.php");

    $erro = [];

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $email_usuario = trim($_POST['email'] ?? '');
        $senha_usuario = trim($_POST['senha'] ?? '');

        if(empty($email_usuario) || empty($senha_usuario)){
            $erro[] = "Preencha todos os campos!";
        } else{
            $sql = "SELECT senha_usuario, id_usuario FROM usuario WHERE email_usuario = ?";

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
                        $_SESSION['id_usuario'] = $usuario['id_usuario'];
                        $_SESSION['nome_usuario'] = $usuario['nome_usuario'];

                        header("Location: index.php");
                    } else {
                        $erro[] = "Email ou senha incorretos!";
                    }
                }

                $stmt->close();
            } else {
                $erro[] = "Erro interno ao processar login.";
            }
         
        }
    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Studium</title>
</head>
<body>
    <?php
    
        if(count($erro) > 0){
            foreach($erro as $msg){
                echo "<p>$msg</p>";
            }
        }

    ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>