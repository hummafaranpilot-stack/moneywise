<?php
session_start();

// CHANGE THIS before going live — this gates access to the Offers admin panel.
define('ADMIN_PASSWORD', 'moneywise2026admin');

/** For pages rendered in a browser — redirects to the login form. */
function require_login() {
    if (empty($_SESSION['mw_admin'])) {
        header('Location: /login.php');
        exit;
    }
}

/** For JSON API endpoints — returns a 401 instead of redirecting. */
function require_login_api() {
    if (empty($_SESSION['mw_admin'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not authenticated']);
        exit;
    }
}
