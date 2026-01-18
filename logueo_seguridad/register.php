<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($conn->connect_error) {
        $errors[] = "Error de conexión a la base de datos: " . $conn->connect_error;
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Usar función centralizada de registro, indicando que se debe enviar verificación
        $result = registerUser($username, $email, $password, $send_verification = true);
        
        if ($result['success']) {
            header("Location: ../");
            exit();
        } else {
            $errors[] = $result['error'];
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="login-styles.css">
</head>
<body>
    <h2>Registro de usuario</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="register.php" method="post" id="registerForm">
        <div class="form-group">
            <label>Usuario:</label>
            <input type="text" name="username" id="username" required minlength="3">
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" id="password" required>
            <span id="togglePasswordRegister" class="password-toggle-icon"></span>
            <div id="passwordReqs" style="font-size: 0.85em; margin-top: 8px;">
                <p>La contraseña debe tener:</p>
                <ul>
                    <li id="req-length">✗ Al menos 8 caracteres</li>
                    <li id="req-uppercase">✗ Una letra mayúscula</li>
                    <li id="req-lowercase">✗ Una letra minúscula</li>
                    <li id="req-number">✗ Un número</li>
                    <li id="req-special">✗ Un carácter especial</li>
                </ul>
            </div>
        </div>

        <button type="submit">Registrarse</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>

    <script src="password_visibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordVisibilityToggle('password', 'togglePasswordRegister');
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
