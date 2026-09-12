<?php 

    include("conexao.php");

    $erro = [];

    if(isset($_POST['recuperar'])){

        $email = $mysqli->escape_string($_POST['email']);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
           $erro[] = "E-mail inválido!";
        }

        $sql_code = "SELECT senha_usuario, id_usuario FROM usuario WHERE email_usuario = '$email'";
        $sql_query = $mysqli->query($sql_code) or die($mysqli->error);
        $dado = $sql_query->fetch_assoc();
        $total = $sql_query->num_rows;

        if($total == 0){
            $erro[] = "Este e-mail não pertence a nenhum usuário cadastrado!";
        }

        if(count($erro) == 0 && $total > 0){

            $novaSenha = substr(password_hash(time(), PASSWORD_DEFAULT), 0, 6);
            $novaSenhaCriptografada = password_hash($novaSenha, PASSWORD_DEFAULT);

            if(1==1 /*mail($email, "Recuperação de senha", "Sua nova senha é:" . $novaSenha)*/){
                $sql_code = "UPDATE usuario SET senha_usuario = '$novaSenhaCriptografada' WHERE email_usuario = '$email'";
                $sql_query = $mysqli->query($sql_code) or die($mysqli->error);

                if($sql_query){
                    $erro[] = "Senha atualizada com sucesso!";
                }
            }
        }

    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
</head>
<body>
    <?php
    
        if(count($erro) > 0){
            foreach($erro as $msg){
                echo "<p>$msg</p>";
            }
        }

    ?>

    <form action="" method="POST">
    <input type="email" name="email" placeholder="Digite seu e-mail" required>
    <input type="submit" value="Recuperar senha" name="recuperar">
</form>
</body>
</html>
