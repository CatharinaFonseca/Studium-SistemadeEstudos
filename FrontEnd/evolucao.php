<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

$urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/evolucaoAPI.php";
$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$respostaRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = ['labels' => [], 'datasets' => []];
if ($httpCode === 200) {
    $dadosApi = json_decode($respostaRaw, true);
    if (is_array($dadosApi)) {
        $json = $dadosApi;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico - Evolução Diária</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
<body>

 <main class="pagina-grafico">
    <h2>📈 Evolução Diária dos Estudos</h2>
    <p>Minutos estudados por dia nos últimos 14 dias.</p>

    <div class="chart-container">
        <canvas id="canvasEvolucao"></canvas>
    </div>

    <br>
    <nav class="botoes-grafico" aria-label="Navegação dos gráficos">
        <button type="button" onclick="window.location.href='estudoMateria.php'">Estudo por Materia</button>
        <button type="button" onclick="window.location.href='registroDeEstudo.php'">Registro de Estudo</button>
        <button type="button" onclick="window.location.href='home.html'">Home</button></button>
    </nav>

    <script>
        const json = <?php echo json_encode($json); ?>;

        if (!json.labels || json.labels.length === 0) {
            document.querySelector('.chart-container').innerHTML = '<p>Nenhum registro recente encontrado nos últimos 14 dias.</p>';
        } else {
            const ctx = document.getElementById('canvasEvolucao').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: 'Minutos Dedicados',
                        data: json.datasets,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Minutos' }
                        },
                        x: {
                            title: { display: true, text: 'Data' }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>