<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($conn->connect_error) {
    $response['message'] = "Error de conexión a la base de datos: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email y contraseña son requeridos.';
        echo json_encode($response);
        exit;
    }

    // Usar función centralizada (ahora recibe email)
    $result = authenticateUser($email, $password, $remember_me);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Login exitoso';
    } else {
        $response['message'] = $result['error'];
        
        // Verificar si está pendiente de verificación
        if (isset($result['pendingVerification']) && $result['pendingVerification']) {
            $response['pendingVerification'] = true;
            
            // Obtener email para mostrar en tooltip
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
