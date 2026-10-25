<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$urlMetaApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/metaAPI.php";
$urlMateriaApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/materiaAPI.php";

$mensagem = "";
$msgCor = "";

if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    $dadosEnvio = json_encode(['id_meta' => $idExcluir]);

    $ch = curl_init($urlMetaApi);
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

    header('Location: meta.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_meta      = trim($_POST['id_meta'] ?? '');
    $id_materia   = trim($_POST['id_materia'] ?? '');
    $titulo_meta  = trim($_POST['titulo_meta'] ?? '');
    $minuto_alvo  = trim($_POST['minuto_alvo'] ?? '');
    $data_limite  = trim($_POST['data_limite'] ?? '');
    $status_meta  = trim($_POST['status_meta'] ?? 'Pendente');

    $metodo = !empty($id_meta) ? 'PUT' : 'POST';
    $dadosEnvio = json_encode([
        'id_meta'     => $id_meta ?: null,
        'id_materia'  => $id_materia,
        'titulo_meta' => $titulo_meta,
        'minuto_alvo' => $minuto_alvo,
        'data_limite' => $data_limite ?: null,
        'status_meta' => $status_meta
    ]);

    $ch = curl_init($urlMetaApi);
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
        $mensagem = "Erro: " . ($resultado['erro'] ?? "Falha ao processar meta.");
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

$ch = curl_init($urlMetaApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$respMeta = curl_exec($ch);
$httpCodeMeta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$metas = [];
if ($httpCodeMeta === 200) {
    $metas = json_decode($respMeta, true) ?? [];
}

$editId      = $_GET['editar'] ?? '';
$editMateria = $_GET['id_materia'] ?? '';
$editTitulo  = $_GET['titulo'] ?? '';
$editMinuto  = $_GET['minuto'] ?? '';
$editData    = $_GET['data'] ?? '';
$editStatus  = $_GET['status'] ?? 'Pendente';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Metas de Estudo</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2 id="tituloForm"><?= !empty($editId) ? "Alterar Meta" : "Nova Meta de Estudo" ?></h2>

    <!-- Formulário para Cadastrar e Editar Meta -->
    <form id="formMeta" method="POST" action="meta.php">
        <input type="hidden" id="id_meta" name="id_meta" value="<?= htmlspecialchars($editId) ?>">

        <label for="id_materia">Matéria:</label><br>
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
        <br><br>

        <label for="titulo_meta">Título da Meta:</label><br>
        <input type="text" id="titulo_meta" name="titulo_meta" placeholder="Ex: Estudar 5 capítulos" value="<?= htmlspecialchars($editTitulo) ?>" required><br><br>

        <label for="minuto_alvo">Minutos Alvo:</label><br>
        <input type="number" id="minuto_alvo" name="minuto_alvo" placeholder="Ex: 120" value="<?= htmlspecialchars($editMinuto) ?>" required><br><br>

        <label for="data_limite">Data Limite:</label><br>
        <input type="date" id="data_limite" name="data_limite" value="<?= htmlspecialchars($editData) ?>"><br><br>

        <label for="status_meta">Status:</label><br>
        <select id="status_meta" name="status_meta">
            <option value="Pendente" <?= ($editStatus === 'Pendente') ? 'selected' : '' ?>>Pendente</option>
            <option value="Em Andamento" <?= ($editStatus === 'Em Andamento') ? 'selected' : '' ?>>Em Andamento</option>
            <option value="Concluida" <?= ($editStatus === 'Concluida') ? 'selected' : '' ?>>Concluída</option>
        </select><br><br>

        <button type="submit" id="btnSalvar"><?= !empty($editId) ? "Atualizar Meta" : "Salvar Meta" ?></button>
        <button type="button" id="btnCancelar" style="<?= !empty($editId) ? "display: inline-block;" : "display: none;" ?>" onclick="window.location.href='meta.php'">Cancelar Edição</button>
        <br><br>

       <button type="button" onclick="window.location.href='home.php'">Home</button>
    </form>

    <div id="mensagem" style="margin-top: 15px; font-weight: bold; color: <?= $msgCor ?>;"><?= htmlspecialchars($mensagem) ?></div>

    <hr style="margin: 20px 0;">

    <h3>Minhas Metas</h3>
    <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; max-width: 800px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Matéria</th>
                <th>Título</th>
                <th>Min. Alvo</th>
                <th>Data Limite</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaMetas">
            <?php if (empty($metas)): ?>
                <tr>
                    <td colspan="7">Nenhuma meta cadastrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($metas as $meta): ?>
                    <?php
                    $nomeMateria = $mapaMaterias[$meta['id_materia']] ?? $meta['nome_materia'] ?? $meta['id_materia'];
                    $dataFmt = !empty($meta['data_limite']) ? implode('/', array_reverse(explode('-', $meta['data_limite']))) : '-';
                    ?>
                    <tr>
                        <td><?= $meta['id_meta'] ?></td>
                        <td><?= htmlspecialchars($nomeMateria) ?></td>
                        <td><?= htmlspecialchars($meta['titulo_meta']) ?></td>
                        <td><?= $meta['minuto_alvo'] ?> min</td>
                        <td><?= $dataFmt ?></td>
                        <td><?= htmlspecialchars($meta['status_meta']) ?></td>
                        <td>
                            <button type="button" onclick="window.location.href='meta.php?editar=<?= $meta['id_meta'] ?>&id_materia=<?= $meta['id_materia'] ?>&titulo=<?= urlencode($meta['titulo_meta']) ?>&minuto=<?= $meta['minuto_alvo'] ?>&data=<?= $meta['data_limite'] ?? '' ?>&status=<?= urlencode($meta['status_meta']) ?>'">Alterar</button>
                            <button type="button" onclick="if(confirm('Tem certeza que deseja apagar esta meta?')) window.location.href='meta.php?excluir=<?= $meta['id_meta'] ?>'">Apagar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>