<?php
// Configurar para evitar salidas inesperadas 
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/utilidades_email.php';
session_start();

// Limpiar cualquier salida anterior  
ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
        exit;
    }

    if ($conn->connect_error) {
        error_log("Error de conexión a BD en solicitar_restablecimiento_contrasena.php: " . $conn->connect_error);
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos. Por favor, inténtalo de nuevo más tarde.']);
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $email = strtolower($email); // Convertir a minúsculas para comparación

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, introduce un email válido.']);
        exit;
    }

    // Verificar si el email existe en la base de datos
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE LOWER(email) = LOWER(?)");
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Error en execute: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Informar que el email no existe (sin revelar si está registrado por seguridad)
        error_log("Intento de restablecimiento de contraseña para email no registrado: $email");
        echo json_encode(['success' => false, 'message' => 'Si el email existe en nuestro sistema, recibirás un enlace de restablecimiento.']);
        exit();
    }

    $user_id = (int)$user['id'];
    $username = $user['username'];

    // Generar un token único y seguro
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token); // Usar SHA256 para consistencia con tokens
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

    error_log("Generando token de restablecimiento para usuario ID: $user_id, Email: $email");

    // Eliminar tokens antiguos para este usuario (buena práctica)
    $stmt = $conn->prepare("DELETE FROM verificaciones_email WHERE id_usuario = ? AND tipo = 'password_reset'");
    if (!$stmt) {
        throw new Exception("Error en prepare (DELETE): " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error en execute (DELETE): " . $stmt->error);
    }
    $stmt->close();

    // Guardar el nuevo token en la tabla verificaciones_email
    $stmt = $conn->prepare("INSERT INTO verificaciones_email (id_usuario, token_hash, expira_en, tipo) VALUES (?, ?, ?, 'password_reset')");
    if (!$stmt) {
        throw new Exception("Error en prepare (INSERT): " . $conn->error);
    }
    $stmt->bind_param("iss", $user_id, $token_hash, $expires_at);
    
    if (!$stmt->execute()) {
        throw new Exception("Error en execute (INSERT): " . $stmt->error);
    }
    $stmt->close();

    error_log("Token guardado en BD. Intentando enviar email a: $email");

    // Enviar el email de restablecimiento
    $emailResult = enviarEmailRestablecerContrasena($email, $username, $token);
    
    if ($emailResult['success']) {
        error_log("Email de restablecimiento enviado exitosamente a: $email");
        echo json_encode([
            'success' => true, 
            'message' => 'Se ha enviado un enlace de restablecimiento a tu email. Por favor, revisa tu bandeja de entrada (incluyendo spam).'
        ]);
    } else {
        error_log("Fallo al enviar email de restablecimiento a $email. Error: " . ($emailResult['error'] ?? 'Desconocido'));
        
        // Mostrar mensaje de error descriptivo
        $errorMsg = $emailResult['error'] ?? 'Error desconocido al enviar el email';
        $userMessage = 'No pudimos enviar el email de restablecimiento. Error: ' . $errorMsg;
        
        echo json_encode([
            'success' => false, 
            'message' => $userMessage
        ]);
    }
    
    exit();

} catch (Exception $e) {
    error_log("Excepción en solicitar_restablecimiento_contrasena.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
        'debug' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
