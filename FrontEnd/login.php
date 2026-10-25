<?php
$caminhoVendor = __DIR__ . '/vendor/autoload.php';
if (!file_exists($caminhoVendor)) {
    $caminhoVendor = __DIR__ . '/../vendor/autoload.php';
}

if (file_exists($caminhoVendor)) {
    require_once $caminhoVendor;
}

session_start();

$mensagemErro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $senha_usuario = trim($_POST['senha_usuario'] ?? '');

    if (!empty($email_usuario) && !empty($senha_usuario)) {
        
        $dadosEnviar = json_encode([
            'email_usuario' => $email_usuario,
            'senha_usuario' => $senha_usuario
        ]);

       $urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/loginAPI.php";

        $ch = curl_init($urlApi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosEnviar);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dadosEnviar)
        ]);

        $respostaRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $dados = json_decode($respostaRaw, true);

        if ($httpCode === 200 && is_array($dados)) {
            $tokenGerado = $dados['token'] ?? $dados['jwt'] ?? null;

            if ($tokenGerado) {
                $_SESSION['token_jwt'] = $tokenGerado;

                header('Location: home.php');
                exit;
            } else {
                $mensagemErro = "Erro: A resposta do servidor não continha um Token válido.";
            }
        } else {
            $mensagemErro = $dados['mensagem'] ?? $dados['erro'] ?? "Erro ao fazer login.";
        }
    } else {
        $mensagemErro = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RepoStudium</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form id="formLogin" action="login.php" method="POST">
        <h2>Acessar RepoStudium</h2>

        <?php if (!empty($mensagemErro)): ?>
            <div id="mensagem" style="color: red; margin-bottom: 15px;">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <div>
            <label for="email_usuario">E-mail:</label>
            <input type="email" id="email_usuario" name="email_usuario" required>
        </div>

        <br>

        <div>
            <label for="senha_usuario">Senha:</label>
            <input type="password" id="senha_usuario" name="senha_usuario" required>
        </div>

        <br>

        <button type="submit">Entrar</button>
    </form>

</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Studium</title>
</head>
<body>

    <h2>Login - Studium</h2>

    <form id="formLogin">
        <label for="email_usuario">E-mail:</label>
        <input type="email" id="email_usuario" required>
        
        <br><br>

        <label for="senha_usuario">Senha:</label>
        <input type="password" id="senha_usuario" required>

        <br><br>

        <button type="submit">Entrar</button>
        <button type="button" onclick="window.location.href='../BackEnd/recupSenha.php'">Esqueceu a senha?</button>
    </form>

    <div id="mensagem" style="margin-top: 15px;"></div>

</body>
</html>