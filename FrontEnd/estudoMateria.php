<?php
session_start();
$token = $_SESSION['token_jwt'] ?? '';

$urlApi = "http://" . $_SERVER['HTTP_HOST'] . "/RepoStudium/BackEnd/estudoMateriaAPI.php";
$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$respostaRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$jsonDados = ['labels' => [], 'datasets' => [], 'cores' => []];
if ($httpCode === 200) {
    $dadosApi = json_decode($respostaRaw, true);
    if (is_array($dadosApi)) {
        $jsonDados = $dadosApi;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico - Tempo por Matéria</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>

    <main class="pagina-grafico">
        <h2>⏱️ Tempo Dedicado por Matéria</h2>
        <p>Distribuição acumulada de minutos estudados por disciplina.</p>

        <div class="chart-container">
            <canvas id="canvasMaterias"></canvas>
        </div>

        <br>
        <nav class="botoes-grafico" aria-label="Navegação dos gráficos">
            <button type="button" onclick="window.location.href='evolucao.php'">Evolução</button>
            <button type="button" onclick="window.location.href='registroDeEstudo.php'">Registro de Estudo</button>
            <button type="button" onclick="window.location.href='home.php'">Home</button></button>
        </nav>
    </main>

    <script>
        // Recebe os dados carregados pelo PHP no servidor
        const json = <?php echo json_encode($jsonDados); ?>;

        if (!json.labels || json.labels.length === 0) {
            document.querySelector('.chart-container').innerHTML = '<p>Nenhum registro de estudo encontrado.</p>';
        } else {
            const ctx = document.getElementById('canvasMaterias').getContext('2d');
            new Chart(ctx, {
                type: 'pie', // Pode ser 'pie' (pizza) ou 'doughnut' (rosca)
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: 'Minutos Estudados',
                        data: json.datasets,
                        backgroundColor: json.cores // Utiliza as cores vindas do banco de dados
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    </script>
</body>
</html>