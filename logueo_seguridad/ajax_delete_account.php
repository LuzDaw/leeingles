<?php
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/../includes/user_functions.php';

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión.']);
    exit;
}

$user_id = getCurrentUserId();

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado.']);
    exit;
}

// Use centralized helper to delete account data
$res = delete_user_account($conn, $user_id);
if ($res['success']) {
    logoutUser();
    echo json_encode(['success' => true, 'message' => 'Cuenta y todos sus datos asociados eliminados correctamente.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la cuenta: ' . ($res['error'] ?? 'desconocido')]);
}

$conn->close();
