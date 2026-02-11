<?php
// includes/ajax_common.php
// Helpers comunes para endpoints AJAX: sesión, cabeceras y respuestas de error

function ensureSessionStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function noCacheHeaders() {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function requireUserOrExitJson() {
    ensureSessionStarted();
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No autorizado']);
        exit();
    }
}

function requireUserOrExitHtml() {
    ensureSessionStarted();
    if (!isset($_SESSION['user_id'])) {
        echo '<div style="text-align:center; padding:20px; color:#ff8a00;">Debes iniciar sesión</div>';
        exit();
    }
}
