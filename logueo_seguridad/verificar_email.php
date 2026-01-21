<?php
// logueo_seguridad/verificar_email.php
require_once '../db/connection.php';

header('Content-Type: text/html; charset=utf-8'); // Asegurar que la respuesta sea HTML

if (isset($_GET['token'])) {
    $token_claro = $_GET['token'];
    $token_hash = hash('sha256', $token_claro); // Usar SHA256 para consistencia

    // Buscar el token en la base de datos
    $stmt = $conn->prepare("SELECT id, id_usuario, expira_en FROM verificaciones_email WHERE token_hash = ? AND tipo = 'email_verification'");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $token_encontrado = $result->fetch_assoc();
    $stmt->close();

    if ($token_encontrado) {
        $id_token = $token_encontrado['id'];
        $id_usuario = $token_encontrado['id_usuario'];
        $expira_en = strtotime($token_encontrado['expira_en']);

        // Verificar si el token ha expirado
        if (time() > $expira_en) {
            // Eliminar token expirado
            $stmt_delete = $conn->prepare("DELETE FROM verificaciones_email WHERE id = ?");
            $stmt_delete->bind_param("i", $id_token);
            $stmt_delete->execute();
            $stmt_delete->close();

            header("Location: ../?mensaje=" . urlencode("El enlace de verificación ha expirado. Por favor, regístrate de nuevo o solicita un nuevo enlace."));
            exit();
        }

        // Actualizar el estado del usuario
        $stmt_update_user = $conn->prepare("UPDATE users SET estado = 'activo', email_verificado_en = NOW() WHERE id = ?");
        $stmt_update_user->bind_param("i", $id_usuario);

        if ($stmt_update_user->execute()) {
            // Eliminar el token de verificación usado
            $stmt_delete = $conn->prepare("DELETE FROM verificaciones_email WHERE id = ?");
            $stmt_delete->bind_param("i", $id_token);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Obtener el nombre de usuario para iniciar sesión
            $stmt_get_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt_get_username->bind_param("i", $id_usuario);
            $stmt_get_username->execute();
            $result_username = $stmt_get_username->get_result();
            $user = $result_username->fetch_assoc();
            $stmt_get_username->close();

            if ($user) {
                session_start(); // Asegurarse de que la sesión esté iniciada
                $_SESSION['user_id'] = $id_usuario;
                $_SESSION['username'] = $user['username'];
                header("Location: ../?mensaje=" . urlencode("cuenta activada"));
                exit();
            } else {
                header("Location: ../?mensaje=" . urlencode("Tu cuenta ha sido activada, pero no pudimos iniciar sesión automáticamente. Por favor, inicia sesión manualmente."));
                exit();
            }
        } else {
            header("Location: ../?mensaje=" . urlencode("Error al activar tu cuenta. Por favor, inténtalo de nuevo."));
            exit();
        }
        $stmt_update_user->close();
    } else {
        header("Location: ../?mensaje=" . urlencode("El token de verificación no es válido o ya ha sido utilizado."));
        exit();
    }
} else {
    header("Location: ../?mensaje=" . urlencode("Token de verificación no proporcionado."));
    exit();
}

$conn->close();
?>
