<?php
require_once 'vendor/autoload.php';

include("autenticar.php");
include("conexao.php");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$chave_secreta = "Tudo tem o seu tempo determinado, e há tempo para todo propósito debaixo do céu.";

header('Content-Type: application/json; charset=utf-8');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado."]);
    exit;
}

try {
    $jwt = $matches[1];
    $dados_token = JWT::decode($jwt, new Key($chave_secreta, 'HS256'));
    $id_usuario = $dados_token->sub;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["erro" => "Sessão inválida ou expirada."]);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $sql = "SELECT id_usuario, nome_usuario, email_usuario FROM usuario WHERE id_usuario = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($usuario = $resultado->fetch_assoc()) {
                echo json_encode($usuario);
            } else {
                http_response_code(404);
                echo json_encode(["erro" => "Usuário não encontrado."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao buscar dados."]);
        }
        break;

    case 'PUT':
        $dados = json_decode(file_get_contents("php://input"), true);

        $nome = trim($dados['nome_usuario'] ?? '');
        $email = filter_var(trim($dados['email_usuario'] ?? ''), FILTER_VALIDATE_EMAIL);
        $senhaAtual = $dados['senha_atual'] ?? '';
        $novaSenha = $dados['nova_senha'] ?? '';

        if (empty($nome) || !$email) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome e e-mail válidos são obrigatórios."]);
            exit;
        }

        // Verifica se o e-mail já pertence a outro usuário
        $sqlEmail = "SELECT id_usuario FROM usuario WHERE email_usuario = ? AND id_usuario != ?";
        $stmtEmail = $mysqli->prepare($sqlEmail);
        $stmtEmail->bind_param("si", $email, $id_usuario);
        $stmtEmail->execute();
        if ($stmtEmail->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(["erro" => "Este e-mail já está em uso por outra conta."]);
            $stmtEmail->close();
            exit;
        }
        $stmtEmail->close();

        // Se o usuário deseja alterar a senha
        if (!empty($novaSenha)) {
            if (empty($senhaAtual)) {
                http_response_code(400);
                echo json_encode(["erro" => "Informe a senha atual para confirmar a alteração de senha."]);
                exit;
            }

            // Busca a senha atual salva no banco para conferência
            $sqlSenha = "SELECT senha_usuario FROM usuario WHERE id_usuario = ?";
            $stmtSenha = $mysqli->prepare($sqlSenha);
            $stmtSenha->bind_param("i", $id_usuario);
            $stmtSenha->execute();
            $resSenha = $stmtSenha->get_result()->fetch_assoc();
            $stmtSenha->close();

            if (!password_verify($senhaAtual, $resSenha['senha_usuario'])) {
                http_response_code(400);
                echo json_encode(["erro" => "Senha atual incorreta."]);
                exit;
            }

            // Gera o novo hash seguro
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $sqlUpdate = "UPDATE usuario SET nome_usuario = ?, email_usuario = ?, senha_usuario = ? WHERE id_usuario = ?";
            $stmtUpdate = $mysqli->prepare($sqlUpdate);
            $stmtUpdate->bind_param("sssi", $nome, $email, $novaSenhaHash, $id_usuario);
        } else {
            // Atualização apenas de Nome e E-mail
            $sqlUpdate = "UPDATE usuario SET nome_usuario = ?, email_usuario = ? WHERE id_usuario = ?";
            $stmtUpdate = $mysqli->prepare($sqlUpdate);
            $stmtUpdate->bind_param("ssi", $nome, $email, $id_usuario);
        }

        if ($stmtUpdate->execute()) {
            echo json_encode(["sucesso" => true, "mensagem" => "Perfil atualizado com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao atualizar dados no banco de dados."]);
        }
        $stmtUpdate->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido."]);
        break;
}
?>