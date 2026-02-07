<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/auth_functions.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($conn->connect_error) {
        $errors[] = "Error de conexión a la base de datos: " . $conn->connect_error;
    } else {
        $login = trim($_POST['user'] ?? '');
        $password = $_POST['pass'] ?? '';

        // Usar función centralizada
        $result = authenticateUser($login, $password);
        
        if ($result['success']) {
            // Verificar que sea admin
            if ($result['is_admin'] == 1) {
                header("Location: ../textoPublic/admin_upload_category.php");
                exit();
            } else {
                $errors[] = "Solo los administradores pueden acceder aquí.";
            }
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
    <title>Login Admin</title>
    <link rel="stylesheet" href="login-styles.css">
</head>
<body>
    <h1>🔒 Acceso Administrador</h1>
    
    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Usuario o Email:</label>
            <input type="text" name="user" required>
        </div>

        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="pass" id="adminPassword" required>
            <span id="toggleAdminPassword" class="password-toggle-icon"></span>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <p><a href="../index.php">Volver al inicio</a></p>

    <script src="password_visibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordVisibilityToggle('adminPassword', 'toggleAdminPassword');
        });
    </script>
</body>
</html>
