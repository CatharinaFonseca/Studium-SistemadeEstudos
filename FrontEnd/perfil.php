<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/perfilAPI.php";
$mensagem = "";
$ehSucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senhaAtual = trim($_POST['senhaAtual'] ?? '');
    $novaSenha = trim($_POST['novaSenha'] ?? '');

    if (!empty($novaSenha) && empty($senhaAtual)) {
        $mensagem = "Informe sua senha atual para definir uma nova senha.";
        $ehSucesso = false;
    } else {
        $corpo = json_encode([
            'nome_usuario' => $nome,
            'email_usuario' => $email,
            'senha_atual' => $senhaAtual,
            'nova_senha' => $novaSenha
        ]);

        $ch = curl_init($urlApi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($corpo)
        ]);
        $respostaRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resultado = json_decode($respostaRaw, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $mensagem = $resultado['mensagem'] ?? "Perfil atualizado com sucesso!";
            $ehSucesso = true;
        } else {
            $mensagem = $resultado['erro'] ?? "Erro ao atualizar o perfil.";
            $ehSucesso = false;
        }
    }
}

$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$respostaRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$dadosPerfil = [];
if ($httpCode === 200) {
    $dadosPerfil = json_decode($respostaRaw, true) ?? [];
}

$nomeUsuario = $dadosPerfil['nome_usuario'] ?? '';
$emailUsuario = $dadosPerfil['email_usuario'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - RepoStudium</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="perfil-card">
        <h2>👤 Meu Perfil</h2>

        <form id="formPerfil" method="POST" action="">
            
            <div class="form-grupo">
                <label for="nome">Nome Completo:*</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nomeUsuario) ?>" required>
            </div>

            <div class="form-grupo">
                <label for="email">E-mail:*</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($emailUsuario) ?>" required>
            </div>

            <hr class="divisor">
            <h3 class="subtitulo">🔒 Alterar Senha (Opcional)</h3>

            <div class="form-grupo">
                <label for="senhaAtual">Senha Atual:</label>
                <input type="password" id="senhaAtual" name="senhaAtual" placeholder="Preencha apenas se for alterar a senha">
            </div>

            <div class="form-grupo">
                <label for="novaSenha">Nova Senha:</label>
                <input type="password" id="novaSenha" name="novaSenha" placeholder="Mínimo de 6 caracteres">
            </div>

            <button type="submit" class="btn-salvar">Salvar Alterações</button>
        </form>

        <div id="mensagem" style="display: <?= !empty($mensagem) ? 'block' : 'none' ?>;" class="<?= $ehSucesso ? 'msg-sucesso' : 'msg-erro' ?>"><?= htmlspecialchars($mensagem) ?></div>

        <a href="home.html" class="btn-voltar">← Home</a>
    </div>

</body>
</html>