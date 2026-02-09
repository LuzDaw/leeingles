<?php
session_start();
require_once '../db/connection.php';
require_once 'auth_functions.php';

// Si el usuario ya está logueado, redirigir a la página principal
if (isAuthenticated()) {
    header("Location: ../index.php");
    exit();
}

$csrf_token = generateCSRFToken();

// Mensajes de error o éxito (si vienen de una redirección)
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message_type']);

$pending_verification_user_id = $_SESSION['pending_verification_user_id'] ?? null;
unset($_SESSION['pending_verification_user_id']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - LeeIngles</title>
    <link rel="stylesheet" href="../css/common-styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <link rel="stylesheet" href="../css/modal-styles.css">
</head>
<body>
    <div class="login-container">
        <img src="../img/aprender_ingles.png" alt="Logo LeeIngles" class="login-logo">
        <h2>Iniciar Sesión</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="ajax_login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
                <span class="password-toggle" onclick="togglePasswordVisibility('password')">👁️</span>
            </div>
            <div class="input-group remember-me">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Recordarme</label>
            </div>
            <button type="submit" class="login-button">Iniciar Sesión</button>
        </form>
        <div class="links">
            <a href="solicitar_restablecimiento_contrasena.php">¿Olvidaste tu contraseña?</a>
            <span>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></span>
        </div>
    </div>

    <?php if ($pending_verification_user_id): ?>
    <div id="verificationModal" class="modal active">
        <div class="modal-content">
            <h3>Verificación de Email Pendiente</h3>
            <p>Tu cuenta no está activa. Por favor, verifica tu email para activar tu cuenta.</p>
            <p>¿No recibiste el email de verificación?</p>
            <button id="resendVerificationBtn" class="button">Reenviar Email de Verificación</button>
            <button class="button close-modal" onclick="closeModal('verificationModal')">Cerrar</button>
        </div>
    </div>
    <?php endif; ?>

    <script src="../js/common-functions.js"></script>
    <script src="password_visibility.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevenir el envío tradicional del formulario

            const form = event.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.querySelector('.message');
                if (messageDiv) {
                    messageDiv.remove(); // Limpiar mensajes anteriores
                }

                const newMessageDiv = document.createElement('div');
                newMessageDiv.classList.add('message');
                if (data.success) {
                    newMessageDiv.classList.add('success');
                    newMessageDiv.textContent = data.message;
                    window.location.href = data.redirect; // Redirigir en caso de éxito
                } else {
                    newMessageDiv.classList.add('error');
                    newMessageDiv.textContent = data.message;
                    if (data.pendingVerification) {
                        // Mostrar modal de verificación pendiente
                        document.getElementById('verificationModal').classList.add('active');
                        // Almacenar user_id para reenviar verificación
                        document.getElementById('resendVerificationBtn').dataset.userId = data.user_id;
                    }
                }
                form.prepend(newMessageDiv); // Mostrar el nuevo mensaje
            })
            .catch(error => {
                console.error('Error:', error);
                const newMessageDiv = document.createElement('div');
                newMessageDiv.classList.add('message', 'error');
                newMessageDiv.textContent = 'Error del servidor. Por favor, inténtalo de nuevo.';
                form.prepend(newMessageDiv);
            });
        });

        // Lógica para reenviar email de verificación
        document.getElementById('resendVerificationBtn').addEventListener('click', function() {
            const userId = this.dataset.userId;
            if (userId) {
                fetch('ajax_resend_verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>`
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeModal('verificationModal');
                    }
                })
                .catch(error => {
                    console.error('Error al reenviar verificación:', error);
                    alert('Error al reenviar el email de verificación.');
                });
            }
        });

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
