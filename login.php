<?php 
    include("conexao.php");

    $erro = [];

    if(isset($_POST['email']) && strlen($_POST['email']) > 0){
        if (!isset($_SESSION))
            session_start();
        
        $_SESSION['email'] = $mysqli->escape_string($_POST['email']);
        $_SESSION['senha'] = md5(md5($_POST['senha']));

        $sql_code = "SELECT senha_usuario, id_usuario FROM usuario WHERE email_usuario = '$_SESSION[email]'";
        $sql_query = $mysqli->query($sql_code) or die($mysqli->error);
        $dado = $sql_query->fetch_assoc();
        $total = $sql_query->num_rows;

        if($total == 0){
            $erro[] = "Este e-mail não pertence a nenhum usuário cadastrado!";
        } else {
            if($dado['senha_usuario'] == $_SESSION['senha']){
                $_SESSION['usuario'] = $dado['id_usuario'];
            } else {
                $erro[] = "Senha incorreta!";
            }
        }    

        if(count($erro) == 0 || !isset($erro)){
            echo "<script>alert('Login realizado com sucesso!');location.href = 'index.php';</script>";
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