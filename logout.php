<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';

auth_logout();
header('Location: login.php');
exit;
