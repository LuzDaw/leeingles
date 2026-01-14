<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión.']);
    exit;
}

$user_id = getCurrentUserId();

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado.']);
    exit;
}

try {
    // Iniciar transacción para asegurar que todo se borre o nada
    $conn->begin_transaction();

    // Al borrar el usuario, las claves foráneas con ON DELETE CASCADE 
    // se encargarán de borrar textos, palabras guardadas, progreso, etc.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        
        // Cerrar la sesión después de borrar la cuenta
        logoutUser();
        
        echo json_encode(['success' => true, 'message' => 'Cuenta eliminada correctamente.']);
    } else {
        throw new Exception("Error al ejecutar la eliminación.");
    }
    
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la cuenta: ' . $e->getMessage()]);
}

$conn->close();
