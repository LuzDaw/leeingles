<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
session_start();

require_once __DIR__ . '/../includes/ajax_helpers.php';

// Si hay un error de conexión a la base de datos, devolver JSON estandarizado.
if (!empty($GLOBALS['db_connection_error'])) {
    error_log('[leeingles] ajax_login detected DB connection error: ' . $GLOBALS['db_connection_error']);
    ajax_error('Error del servidor. Intenta más tarde.', 500, $GLOBALS['db_connection_error']);
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email y contraseña son requeridos.';
        echo json_encode($response);
        exit;
    }

    $result = authenticateUser($email, $password, $remember_me);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Login exitoso';
    } else {
        $response['message'] = $result['error'];
        
        if (isset($result['pendingVerification']) && $result['pendingVerification']) {
            $response['pendingVerification'] = true;
            
            if (isset($result['user_id'])) {
                $email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                $email_stmt->bind_param("i", $result['user_id']);
                $email_stmt->execute();
                $email_stmt->bind_result($user_email);
                $email_stmt->fetch();
                $email_stmt->close();
                $response['email'] = $user_email ?? '';
            }
        }
    }
    
    $conn->close();
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
?>
