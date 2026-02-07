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

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = registerUser($username, $email, $password, $send_verification = true);

        if ($result['success']) {
            $user_id = $result['user_id'];
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expira_en = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt_token = $conn->prepare("INSERT INTO verificaciones_email (id_usuario, token_hash, expira_en, tipo) VALUES (?, ?, ?, 'email_verification')");
            $stmt_token->bind_param("iss", $user_id, $token_hash, $expira_en);

            if ($stmt_token->execute()) {
                enviarEmailVerificacion($email, $username, $token);

                echo json_encode([
                    'success' => true,
                    'message' => 'Registro exitoso. Por favor, verifica tu email para activar tu cuenta.',
                    'email' => $email,
                    'username' => $username
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al guardar token']);
            }
            $stmt_token->close();
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    }
} catch (Exception $e) {
    error_log("Excepción en ajax_register.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}

if (isset($conn)) {
    $conn->close();
}
