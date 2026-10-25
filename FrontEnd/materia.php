<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/materiaAPI.php";
$mensagem = "";
$msgCor = "";

if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    $dadosEnvio = json_encode(['id_materia' => $idExcluir]);

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosEnvio);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dadosEnvio)
    ]);
    curl_exec($ch);
    curl_close($ch);

    header('Location: materia.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_materia   = trim($_POST['id_materia'] ?? '');
    $nome_materia = trim($_POST['nome_materia'] ?? '');
    $cor_materia  = trim($_POST['cor_materia'] ?? '#706f6f');

    $metodo = !empty($id_materia) ? 'PUT' : 'POST';
    $dadosEnvio = json_encode([
        'id_materia'   => $id_materia ?: null,
        'nome_materia' => $nome_materia,
        'cor_materia'  => $cor_materia
    ]);

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosEnvio);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dadosEnvio)
    ]);
    $respostaRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resultado = json_decode($respostaRaw, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        $mensagem = $resultado['mensagem'] ?? "Operação realizada com sucesso!";
        $msgCor = "green";
    } else {
        $mensagem = "Erro: " . ($resultado['erro'] ?? "Erro desconhecido");
        $msgCor = "red";
    }
}

$editId   = $_GET['editar'] ?? '';
$editNome = $_GET['nome'] ?? '';
$editCor  = $_GET['cor'] ?? '#706f6f';

$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$respostaRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$materias = [];
if ($httpCode === 200) {
    $materias = json_decode($respostaRaw, true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Matérias</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h2 id="tituloForm"><?= !empty($editId) ? "Alterar Matéria" : "Cadastrar Nova Matéria" ?></h2>

    <form id="formMateria" method="POST" action="materia.php">
        <!-- Campo oculto para armazenar o ID durante a edição -->
        <input type="hidden" id="id_materia" name="id_materia" value="<?= htmlspecialchars($editId) ?>">

        <label for="nome_materia">Nome da Matéria:</label><br>
        <input type="text" id="nome_materia" name="nome_materia" placeholder="Ex: Matemática, História..." value="<?= htmlspecialchars($editNome) ?>" required>
        <br><br>

        <label for="cor_materia">Cor de Identificação:</label><br>
        <input type="color" id="cor_materia" name="cor_materia" value="<?= htmlspecialchars($editCor) ?>">
        <br><br>

        <button type="submit" id="btnSalvar"><?= !empty($editId) ? "Atualizar Matéria" : "Salvar Matéria" ?></button>
        <button type="button" id="btnCancelar" style="<?= !empty($editId) ? "display: inline-block;" : "display: none;" ?>" onclick="window.location.href='materia.php'">Cancelar Edição</button>
        <br><br>
        
        <button type="button" onclick="window.location.href='home.php'">Home</button>
    </form>

    <div id="mensagem" style="margin-top: 15px; font-weight: bold; color: <?= $msgCor ?>;"><?= htmlspecialchars($mensagem) ?></div>

    <hr style="margin: 20px 0;">

    <h3>Minhas Matérias</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; max-width: 600px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cor</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaMaterias">
            <?php if (empty($materias)): ?>
                <tr>
                    <td colspan="4">Nenhuma matéria cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($materias as $materia): ?>
                    <tr>
                        <td><?= $materia['id_materia'] ?></td>
                        <td><?= htmlspecialchars($materia['nome_materia']) ?></td>
                        <td style="background-color: <?= htmlspecialchars($materia['cor_materia']) ?>;"></td>
                        <td>
                            <button type="button" onclick="window.location.href='materia.php?editar=<?= $materia['id_materia'] ?>&nome=<?= urlencode($materia['nome_materia']) ?>&cor=<?= urlencode($materia['cor_materia']) ?>'">Alterar</button>
                            <button type="button" onclick="if(confirm('Tem certeza que deseja apagar esta matéria?')) window.location.href='materia.php?excluir=<?= $materia['id_materia'] ?>'">Apagar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>