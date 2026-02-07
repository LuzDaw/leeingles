<?php
ob_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/utilidades_email.php';
session_start();

ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'error' => 'Error de conexión']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Email no proporcionado']);
            exit;
        }

        // Verificar si el usuario existe y está pendiente
        $stmt = $conn->prepare("SELECT id, username, estado FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            exit;
        }

        if ($user['estado'] !== 'pendiente') {
            echo json_encode(['success' => false, 'error' => 'La cuenta ya está activa o no está pendiente de verificación']);
            exit;
        }

        $user_id = $user['id'];
        $username = $user['username'];

        // Eliminar tokens anteriores de verificación para este usuario
        $stmt_del = $conn->prepare("DELETE FROM verificaciones_email WHERE id_usuario = ? AND tipo = 'email_verification'");
        $stmt_del->bind_param("i", $user_id);
        $stmt_del->execute();
        $stmt_del->close();

        // Generar nuevo token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expira_en = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt_token = $conn->prepare("INSERT INTO verificaciones_email (id_usuario, token_hash, expira_en, tipo) VALUES (?, ?, ?, 'email_verification')");
        $stmt_token->bind_param("iss", $user_id, $token_hash, $expira_en);

        if ($stmt_token->execute()) {
            enviarEmailVerificacion($email, $username, $token);

            echo json_encode([
                'success' => true,
                'message' => 'Se ha enviado un nuevo email de verificación a ' . $email
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar el nuevo token']);
        }
        $stmt_token->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log("Excepción en ajax_resend_verification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}

if (isset($conn)) {
    $conn->close();
}
