<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (auth_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if (auth_login($senha)) {
        header('Location: index.php');
        exit;
    }
    $erro = 'Senha incorreta. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar · Zabeths</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="brand">🎂 Zabeths</div>
        <p>Sistema de Precificação</p>
        <?php if ($erro): ?>
            <div class="alert error"><?= h($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="field" style="text-align:left">
                <label for="senha">Senha de acesso</label>
                <input type="password" id="senha" name="senha" required autofocus>
            </div>
            <button class="btn" type="submit" style="width:100%; justify-content:center">Entrar</button>
        </form>
    </div>
</div>
</body>
</html>
