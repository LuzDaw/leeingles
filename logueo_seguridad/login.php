<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Token de seguridad inválido.";
    } else {
        if ($conn->connect_error) {
            $errors[] = "Error de conexión a la base de datos: " . $conn->connect_error;
        } else {
            $email = trim($_POST['email']); // Ahora es solo email
            $password = $_POST['password'];
            $remember_me = isset($_POST['remember_me']);

            // Usar función centralizada (ahora recibe email)
            $result = authenticateUser($email, $password, $remember_me);
            
            if ($result['success']) {
                header("Location: ../");
                exit();
            } else {
                $errors[] = $result['error'];
            }
        }
    }
    $conn->close();
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="login-styles.css">
</head>
<body>
    <h2>Iniciar sesión</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" id="passwordLogin" required>
            <span id="togglePasswordLogin" class="password-toggle-icon"></span>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="remember_me">
                Mantener sesión abierta
            </label>
        </div>
        
        <button type="submit">Iniciar sesión</button>
    </form>

    <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    <script src="password_visibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordVisibilityToggle('passwordLogin', 'togglePasswordLogin');
        });
    </script>
</body>
</html>
