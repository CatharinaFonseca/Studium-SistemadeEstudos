<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$urlRegistroApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/registroDeEstudoAPI.php";
$urlMateriaApi  = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/materiaAPI.php";

$mensagem = "";
$msgCor = "";

if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    $dadosEnvio = json_encode(['id_registro_estudo' => $idExcluir]);

    $ch = curl_init($urlRegistroApi);
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

    header('Location: registroDeEstudo.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_registro_estudo   = trim($_POST['id_registro_estudo'] ?? '');
    $id_materia           = trim($_POST['id_materia'] ?? '');
    $minuto_dedicado      = trim($_POST['minuto_dedicado'] ?? '');
    $data_registro_estudo = trim($_POST['data_registro_estudo'] ?? '');
    $observacao           = trim($_POST['observacao'] ?? '');

    $metodo = !empty($id_registro_estudo) ? 'PUT' : 'POST';
    $dadosEnvio = json_encode([
        'id_registro_estudo'   => $id_registro_estudo ?: null,
        'id_materia'           => $id_materia,
        'minuto_dedicado'      => $minuto_dedicado,
        'data_registro_estudo' => $data_registro_estudo,
        'observacao'           => $observacao
    ]);

    $ch = curl_init($urlRegistroApi);
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
        $mensagem = $resultado['mensagem'] ?? "Registro salvo com sucesso!";
        $msgCor = "green";
    } else {
        $mensagem = "Erro: " . ($resultado['erro'] ?? "Falha ao salvar registro.");
        $msgCor = "red";
    }
}

$ch = curl_init($urlMateriaApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$respMateria = curl_exec($ch);
$httpCodeMat = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$materias = [];
$mapaMaterias = [];
if ($httpCodeMat === 200) {
    $materias = json_decode($respMateria, true) ?? [];
    foreach ($materias as $m) {
        $mapaMaterias[$m['id_materia']] = $m['nome_materia'];
    }
}

$ch = curl_init($urlRegistroApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$respReg = curl_exec($ch);
$httpCodeReg = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$registros = [];
if ($httpCodeReg === 200) {
    $registros = json_decode($respReg, true) ?? [];
}

$editId      = $_GET['editar'] ?? '';
$editMateria = $_GET['id_materia'] ?? '';
$editMinuto  = $_GET['minuto'] ?? '';
$editData    = $_GET['data'] ?? '';
$editObs     = $_GET['observacao'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Registros de Estudo</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h2 id="tituloForm"><?= !empty($editId) ? "Alterar Registro de Estudo" : "Cadastrar Registro de Estudo" ?></h2>

    <!-- Formulário para Cadastrar e Editar Registro de Estudo -->
    <form id="formRegistroEstudo" method="POST" action="registroDeEstudo.php">
        <input type="hidden" id="id_registro_estudo" name="id_registro_estudo" value="<?= htmlspecialchars($editId) ?>">

        <div class="form-group">
            <label for="id_materia">Matéria:*</label><br>
            <select id="id_materia" name="id_materia" required>
                <?php if (empty($materias)): ?>
                    <option value="">Erro ao carregar matérias</option>
                <?php else: ?>
                    <option value="">Selecione uma matéria</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= $m['id_materia'] ?>" <?= ($editMateria == $m['id_materia']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nome_materia']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div><br>

        <div class="form-group">
            <label for="minuto_dedicado">Minutos Dedicados:*</label><br>
            <input type="number" id="minuto_dedicado" name="minuto_dedicado" placeholder="Ex: 45" value="<?= htmlspecialchars($editMinuto) ?>" required>
        </div><br>

        <div class="form-group">
            <label for="data_registro_estudo">Data do Estudo:*</label><br>
            <input type="date" id="data_registro_estudo" name="data_registro_estudo" value="<?= htmlspecialchars($editData) ?>" required>
        </div><br>

        <div class="form-group">
            <label for="observacao">Observação:</label><br>
            <textarea id="observacao" name="observacao" rows="3" placeholder="Ex: Leitura dos capítulos 1 e 2"><?= htmlspecialchars($editObs) ?></textarea>
        </div><br>

        <button type="submit" id="btnSalvar"><?= !empty($editId) ? "Atualizar Registro" : "Salvar Registro" ?></button>
        <button type="button" id="btnCancelar" style="<?= !empty($editId) ? "display: inline-block;" : "display: none;" ?>" onclick="window.location.href='registroDeEstudo.php'">Cancelar Edição</button>
        <br><br>

        <button type="button" onclick="window.location.href='materia.php'">Gerenciar Matérias</button>
        <button type="button" onclick="window.location.href='meta.php'">Gerenciar Metas</button>
    </form>

    <div id="resultado" style="display: <?= !empty($mensagem) ? 'block' : 'none' ?>; padding: 10px; margin-top: 15px; font-weight: bold; color: <?= $msgCor ?>;"><?= htmlspecialchars($mensagem) ?></div>

    <hr style="margin: 20px 0;">

    <h3>Meus Registros de Estudo</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; max-width: 800px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Matéria</th>
                <th>Min. Dedicados</th>
                <th>Data</th>
                <th>Observação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaRegistros">
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="6">Nenhum registro encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $reg): ?>
                    <?php
                    $nomeMateria = $mapaMaterias[$reg['id_materia']] ?? $reg['nome_materia'] ?? $reg['id_materia'];
                    $dataFmt = !empty($reg['data_registro_estudo']) ? implode('/', array_reverse(explode('-', $reg['data_registro_estudo']))) : '-';
                    $obs = !empty($reg['observacao']) ? $reg['observacao'] : '-';
                    ?>
                    <tr>
                        <td><?= $reg['id_registro_estudo'] ?></td>
                        <td><?= htmlspecialchars($nomeMateria) ?></td>
                        <td><?= $reg['minuto_dedicado'] ?> min</td>
                        <td><?= $dataFmt ?></td>
                        <td><?= htmlspecialchars($obs) ?></td>
                        <td>
                            <button type="button" onclick="window.location.href='registroDeEstudo.php?editar=<?= $reg['id_registro_estudo'] ?>&id_materia=<?= $reg['id_materia'] ?>&minuto=<?= $reg['minuto_dedicado'] ?>&data=<?= $reg['data_registro_estudo'] ?>&observacao=<?= urlencode($obs) ?>'">Alterar</button>
                            <button type="button" onclick="if(confirm('Tem certeza que deseja apagar este registro de estudo?')) window.location.href='registroDeEstudo.php?excluir=<?= $reg['id_registro_estudo'] ?>'">Apagar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>