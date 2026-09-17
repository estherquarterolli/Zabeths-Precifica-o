<?php
require_once __DIR__ . '/icons.php';

// Espera que $pageTitle e $activeNav estejam definidos antes do include.
$pageTitle = $pageTitle ?? 'Zabeths';
$activeNav = $activeNav ?? '';

function nav_class(string $key, string $active): string
{
    return 'nav-link' . ($key === $active ? ' active' : '');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> · Zabeths</title>
<link rel="icon" href="assets/img/logo.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-logo-wrap"><img src="assets/img/logo.png" alt="Zabeth's Gourmet" class="brand-logo"></span>
            <span class="brand-sub">Precificação</span>
        </div>
        <a class="<?= nav_class('dashboard', $activeNav) ?>" href="index.php"><?= icon('dashboard') ?> Dashboard</a>
        <a class="<?= nav_class('produtos', $activeNav) ?>" href="produtos.php"><?= icon('cupcake') ?> Produtos</a>
        <a class="<?= nav_class('ingredientes', $activeNav) ?>" href="ingredientes.php"><?= icon('basket') ?> Ingredientes</a>
        <a class="<?= nav_class('configuracoes', $activeNav) ?>" href="configuracoes.php"><?= icon('gear') ?> Configurações</a>
        <div class="sidebar-footer">
            <a href="logout.php"><?= icon('logout', 16) ?> Sair</a>
        </div>
    </aside>
    <main class="main">
