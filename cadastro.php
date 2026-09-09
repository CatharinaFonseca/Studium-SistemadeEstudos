<?php 

    include("conexao.php");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nome_usuario = $_POST['nome_usuario'] ?? '';
        $email_usuario = $_POST['email_usuario'] ?? '';
        $senha_usuario = $_POST['senha_usuario'] ?? '';

        if (empty($nome_usuario) || empty($email_usuario) || empty($senha_usuario)) {
            die("Por favor, preencha todos os campos.");
        } else {
            $senha_usuario = password_hash($senha_usuario, PASSWORD_DEFAULT);
        }

        $sql = "INSERT INTO `usuario`( `nome_usuario`, `email_usuario`, `senha_usuario`) VALUES ('$nome_usuario','$email_usuario','$senha_usuario')";

        if(mysqli_query($mysqli, $sql)){
            echo "Cadastro realizado com sucesso!";
        } else {
            echo "Erro ao cadastrar: ";
        }
    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="POST">
        <input type="text" name="nome_usuario" placeholder="Nome" required>
        <input type="email" name="email_usuario" placeholder="E-mail" required>
        <input type="password" name="senha_usuario" placeholder="Senha" required>
        <button type="submit">Entrar</button>
    </form> 
</body>
</html>