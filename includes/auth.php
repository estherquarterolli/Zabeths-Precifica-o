<?php
require_once __DIR__ . '/env.php';

// Autenticação simples por senha compartilhada, sem sessão de servidor
// (funciona em ambiente serverless/Vercel): um cookie assinado com HMAC.
function auth_cookie_expected(): string
{
    $secret = env('APP_SECRET', 'zabeths-troque-este-segredo');
    return hash_hmac('sha256', 'zabeths-autenticado', $secret);
}

function auth_is_logged_in(): bool
{
    $password = env('APP_PASSWORD', '');
    if ($password === '') {
        // Sem senha configurada: acesso liberado (defina APP_PASSWORD para proteger).
        return true;
    }
    $cookie = $_COOKIE['zabeths_auth'] ?? '';
    return hash_equals(auth_cookie_expected(), $cookie);
}

function auth_require(): void
{
    if (!auth_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function auth_login(string $password): bool
{
    $expected = env('APP_PASSWORD', '');
    if ($expected !== '' && hash_equals($expected, $password)) {
        setcookie('zabeths_auth', auth_cookie_expected(), [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return true;
    }
    return false;
}

function auth_logout(): void
{
    setcookie('zabeths_auth', '', ['expires' => time() - 3600, 'path' => '/']);
}
