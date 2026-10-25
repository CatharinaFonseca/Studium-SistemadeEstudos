<?php
$mensagem = "";
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_usuario  = trim($_POST['nome_usuario'] ?? '');
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $senha_usuario = trim($_POST['senha_usuario'] ?? '');

    if (!empty($nome_usuario) && !empty($email_usuario) && !empty($senha_usuario)) {
        
        $dadosEnviar = json_encode([
            'nome_usuario'  => $nome_usuario,
            'email_usuario' => $email_usuario,
            'senha_usuario' => $senha_usuario
        ]);

        $urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/cadastroAPI.php";

        $ch = curl_init($urlApi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosEnviar);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dadosEnviar)
        ]);

        $respostaRaw = curl_exec($ch);
        $curlErro = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErro) {
            $mensagem = "Erro ao conectar com a API: " . $curlErro;
        } else {
            $dados = json_decode($respostaRaw, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                $mensagem = ($dados['mensagem'] ?? "Cadastro realizado com sucesso!") . " Redirecionando para o login...";
                $sucesso = true;
            } else {
                $mensagem = $dados['erro'] ?? "Erro ao realizar cadastro.";
            }
        }
    } else {
        $mensagem = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Studium</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <?php if ($sucesso): ?>
        <meta http-equiv="refresh" content="2;url=login.php">
    <?php endif; ?>
</head>
<body>

    <h2>Criar Conta</h2>

    <?php if (!empty($mensagem)): ?>
        <div id="mensagem" style="margin-top: 15px; color: <?= $sucesso ? 'green' : 'red'; ?>; font-weight: bold;">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form  id="formCadastro" action="" method="POST">
        <label for="nome_usuario">Nome:</label>
        <input type="text" id="nome_usuario" name="nome_usuario" placeholder="Seu Nome" required>
        <br><br>

        <label for="email_usuario">E-mail:</label>
        <input type="email" id="email_usuario" name="email_usuario" placeholder="seuemail@exemplo.com" required>
        <br><br>

        <label for="senha_usuario">Senha:</label>
        <input type="password" id="senha_usuario" name="senha_usuario" placeholder="Sua Senha" required>
        <br><br>

        <button type="submit">Cadastrar</button>
    </form> 

    <div id="mensagem" style="margin-top: 15px;"></div>

    <p>Já tem uma conta? <a href="login.php">Fazer Login</a></p>

</body>
</html>