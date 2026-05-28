<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $obfuscated_id = trim($_POST['obfuscated_id']);
    $senha = $_POST['senha'];

    if (empty($obfuscated_id) || empty($senha)) {
        $error = 'Preencha todos os campos.';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE obfuscated_id = ? AND status = 'ativo'");
        $stmt->execute([$obfuscated_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['obfuscated_id'] = $user['obfuscated_id'];
            $_SESSION['nome_completo'] = $user['nome_completo'];
            $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
            $_SESSION['cargo'] = $user['cargo'];

            if (in_array($user['nivel_acesso'], ['admin', 'rh', 'coordenacao'])) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: employee/dashboard.php');
            }
            exit;
        } else {
            $error = 'ID ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão RH</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <h1>Sistema de Gestão RH</h1>
            <p>Acesse sua conta com seu ID e senha</p>
        </div>
        <div class="login-divider"></div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="obfuscated_id">ID do Colaborador</label>
                <input type="text" id="obfuscated_id" name="obfuscated_id" class="form-control" 
                       placeholder="Ex: EMP-A3B7M1" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" 
                       placeholder="Digite sua senha" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Acessar Sistema</button>
        </form>

        <div style="margin-top: 25px; text-align: center; font-size: 12px; color: #8B7D6B;">
            <p>Demo: ID <strong>ADM-X7K9P2</strong> | Senha <strong>password</strong></p>
            <p>Funcionário: ID <strong>EMP-A3B7M1</strong> | Senha <strong>password</strong></p>
        </div>
    </div>
</div>
</body>
</html>