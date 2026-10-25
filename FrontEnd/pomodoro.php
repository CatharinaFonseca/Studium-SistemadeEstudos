<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$urlMateriaApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/materiaAPI.php";
$urlEstudoApi  = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/registroDeEstudoAPI.php";

$mensagem = "";
$msgCor = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_materia = trim($_POST['id_materia'] ?? '');
    $minutos    = trim($_POST['minutos_estudados'] ?? '');
    $hoje       = date('Y-m-d');

    $dadosEnvio = json_encode([
        'id_materia'           => $id_materia,
        'minuto_dedicado'      => $minutos,
        'data_registro_estudo' => $hoje,
        'observacao'           => "Sessão concluída via Timer Personalizado"
    ]);

    $ch = curl_init($urlEstudoApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
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
        $mensagem = "✅ Sessão salva no histórico!";
        $msgCor = "green";
    } else {
        $mensagem = "Erro: " . ($resultado['erro'] ?? "Falha ao salvar");
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
if ($httpCodeMat === 200) {
    $materias = json_decode($respMateria, true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Personalizado - RepoStudium</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="pomodoro-card">
        <h2>⏱️ Timer Personalizado</h2>
        
        <div id="badgeModo" class="status-badge modo-foco" style="margin-top: 10px;">Modo Foco</div>
        
        <div id="timer" class="timer-display">25:00</div>

        <div class="config-tempo">
            <div class="form-grupo">
                <label for="tempoFoco">Foco (min):</label>
                <input type="number" id="tempoFoco" value="25" min="1" max="180" onchange="atualizarTempoConfigurado()">
            </div>
            <div class="form-grupo">
                <label for="tempoPausa">Descanso (min):</label>
                <input type="number" id="tempoPausa" value="5" min="1" max="60" onchange="atualizarTempoConfigurado()">
            </div>
        </div>

        <div class="controles">
            <button id="btnPlay" class="btn-iniciar" onclick="iniciarTimer()">Iniciar</button>
            <button id="btnPause" class="btn-pausar" onclick="pausarTimer()" style="display: none;">Pausar</button>
            <button class="btn-reset" onclick="resetarTimer()">Resetar</button>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <h3>💾 Registrar Sessão</h3>
        
        <form method="POST" action="">
            <div class="form-grupo" style="margin-top: 15px;">
                <label for="selectMateria">Matéria Estudada:*</label>
                <select id="selectMateria" name="id_materia" required>
                    <?php if (empty($materias)): ?>
                        <option value="">Erro ao carregar matérias</option>
                    <?php else: ?>
                        <option value="">Selecione a matéria</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= htmlspecialchars($m['nome_materia']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-grupo" style="margin-top: 10px;">
                <label for="minutosEstudados">Minutos a Registrar:*</label>
                <input type="number" id="minutosEstudados" name="minutos_estudados" value="25" min="1" required>
            </div>

            <button type="submit" class="btn-salvar">Salvar no Histórico</button>
        </form>

        <div id="mensagem" style="margin-top: 10px; font-size: 0.9rem; font-weight: bold; color: <?= $msgCor ?>;"><?= $mensagem ?></div>

        <a href="home.php" class="btn-voltar">← Voltar ao Home</a>
    </div>

    <script>
        let tempoRestante = 25 * 60;
        let timerInterval = null;
        let emFoco = true;

        const displayTimer = document.getElementById('timer');
        const badgeModo = document.getElementById('badgeModo');
        const btnPlay = document.getElementById('btnPlay');
        const btnPause = document.getElementById('btnPause');

        function atualizarDisplay() {
            const minutos = Math.floor(tempoRestante / 60);
            const segundos = tempoRestante % 60;
            displayTimer.innerText = `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
            document.title = `(${displayTimer.innerText}) Timer - RepoStudium`;
        }

        function atualizarTempoConfigurado() {
            if (timerInterval) return;

            const focoMin = parseInt(document.getElementById('tempoFoco').value) || 25;
            const descansoMin = parseInt(document.getElementById('tempoPausa').value) || 5;

            if (emFoco) {
                tempoRestante = focoMin * 60;
                document.getElementById('minutosEstudados').value = focoMin;
            } else {
                tempoRestante = descansoMin * 60;
            }
            atualizarDisplay();
        }

        function iniciarTimer() {
            if (timerInterval) return;

            document.getElementById('tempoFoco').disabled = true;
            document.getElementById('tempoPausa').disabled = true;

            btnPlay.style.display = 'none';
            btnPause.style.display = 'inline-block';

            timerInterval = setInterval(() => {
                if (tempoRestante > 0) {
                    tempoRestante--;
                    atualizarDisplay();
                } else {
                    clearInterval(timerInterval);
                    timerInterval = null;
                    alert(emFoco ? "🎉 Fim do tempo de Foco! Hora do descanso." : "🔔 Descanso finalizado! Hora de voltar ao trabalho.");
                    alternarModo();
                }
            }, 1000);
        }

        function pausarTimer() {
            clearInterval(timerInterval);
            timerInterval = null;
            btnPlay.style.display = 'inline-block';
            btnPause.style.display = 'none';
        }

        function resetarTimer() {
            pausarTimer();
            document.getElementById('tempoFoco').disabled = false;
            document.getElementById('tempoPausa').disabled = false;
            atualizarTempoConfigurado();
        }

        function alternarModo() {
            emFoco = !emFoco;
            const focoMin = parseInt(document.getElementById('tempoFoco').value) || 25;
            const descansoMin = parseInt(document.getElementById('tempoPausa').value) || 5;

            if (emFoco) {
                tempoRestante = focoMin * 60;
                badgeModo.innerText = `Modo Foco (${focoMin} min)`;
                badgeModo.className = "status-badge modo-foco";
            } else {
                tempoRestante = descansoMin * 60;
                badgeModo.innerText = `Pausa (${descansoMin} min)`;
                badgeModo.className = "status-badge modo-pausa";
            }

            atualizarDisplay();
            btnPlay.style.display = 'inline-block';
            btnPause.style.display = 'none';
        }

        window.onload = () => {
            atualizarTempoConfigurado();
        };
    </script>
</body>
</html>