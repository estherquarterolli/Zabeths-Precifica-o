<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['linha'])) {
    try {
        repo_excluir_ingrediente((int) $_POST['linha']);
        header('Location: ingredientes.php?excluido=1');
        exit;
    } catch (SheetsException $e) {
        header('Location: ingredientes.php?erro=' . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: ingredientes.php');
exit;
