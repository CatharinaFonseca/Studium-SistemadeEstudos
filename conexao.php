<?php 

    $hostname = "127.0.0.1";
    $bancoDeDados = "studium";
    $usuario = "root";
    $senha = "";
    $porta = 3307;

    $mysqli = new mysqli($hostname, $usuario, $senha, $bancoDeDados, $porta);
    if ($mysqli->connect_errno) {
        echo "Falha ao conectar ao MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

?>