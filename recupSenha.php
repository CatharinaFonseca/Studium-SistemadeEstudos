<?php 
include("conexao.php");

$mensagens = [];

if (isset($_POST['recuperar'])) {

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $mensagens[] = "E-mail inválido!";
    } else {
        // 1. Verifica se o e-mail existe no banco
        $stmt = $mysqli->prepare("SELECT id_usuario FROM usuario WHERE email_usuario = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            $mensagens[] = "Este e-mail não pertence a nenhum usuário cadastrado!";
        } else {
            // 2. Gera uma nova senha provisória válida de 6 caracteres (ex: 8f3a1c)
            $novaSenhaTextoPuro = bin2hex(random_bytes(3)); 
            
            // 3. Criptografa a nova senha com BCRYPT
            $novaSenhaHash = password_hash($novaSenhaTextoPuro, PASSWORD_DEFAULT);

            // 4. Atualiza a senha no banco de dados
            $stmtUpdate = $mysqli->prepare("UPDATE usuario SET senha_usuario = ? WHERE email_usuario = ?");
            $stmtUpdate->bind_param("ss", $novaSenhaHash, $email);

            if ($stmtUpdate->execute()) {
                // Em um ambiente de produção, substitua esta exibição pelo envio real de e-mail (Ex: PHPMailer)
                $mensagens[] = "✅ Sua nova senha provisória é: <strong>$novaSenhaTextoPuro</strong>";
                $mensagens[] = "Utilize-a para fazer login e troque sua senha em seguida.";
            } else {
                $mensagens[] = "Erro ao atualizar a senha no banco de dados.";
            }
            $stmtUpdate->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - RepoStudium</title>
</head>
<body>

    <div class="card">
        <h2>🔑 Recuperar Senha</h2>

        <?php if (!empty($mensagens)): ?>
            <div class="msg">
                <?php foreach ($mensagens as $msg): ?>
                    <p style="margin: 5px 0;"><?= $msg ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="email">Informe seu e-mail cadastrado:</label>
            <input type="email" name="email" id="email" placeholder="seuemail@exemplo.com" required>
            <input type="submit" name="recuperar" value="Gerar Nova Senha">
        </form>

        <br>
        <a href="login.html" style="color: #7f8c8d; text-decoration: none; font-size: 0.85rem;">← Voltar para o Login</a>
    </div>

</body>
</html>