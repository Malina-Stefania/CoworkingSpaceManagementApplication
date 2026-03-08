<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void {
    if (!isset($_SESSION['user_email'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['user_role'] ?? '') !== 'administrator') {
        http_response_code(403);
        echo "Acces interzis (necesar rol administrator).";
        exit;
    }
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function now_date(): string {
    return date('Y-m-d');
}

function now_datetime_local(): string {
    return date('Y-m-d\TH:i');
}
