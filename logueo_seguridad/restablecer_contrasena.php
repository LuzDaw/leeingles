<?php
session_start();
require_once '../db/connection.php';
require_once 'auth_functions.php'; // Para funciones como hashPassword

$message = '';
$message_type = ''; // 'success' or 'error'  
$show_form = false;
$token_valid = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash('sha256', $token);
    $current_time = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT id_usuario, expira_en FROM verificaciones_email WHERE token_hash = ? AND tipo = 'password_reset'");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $verification = $result->fetch_assoc();
    $stmt->close();

    if ($verification) {
        if ($current_time < $verification['expira_en']) {
            $show_form = true;
            $token_valid = true;
            $user_id = $verification['id_usuario'];
        } else {
            $message = 'El enlace para restablecer la contraseña ha caducado. Por favor, solicita uno nuevo.';
            $message_type = 'error';
        }
    } else {
        $message = 'El enlace para restablecer la contraseña no es válido.';
        $message_type = 'error';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Este bloque ahora manejará la solicitud AJAX del formulario del modal
    header('Content-Type: application/json'); // Asegurar respuesta JSON para AJAX

    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit();
    } elseif ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
        exit();
    } elseif (strlen($new_password) < 6) { // Ejemplo de validación de longitud
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit();
    } else {
        $token_hash = hash('sha256', $token);
        $current_time = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("SELECT id_usuario, expira_en FROM verificaciones_email WHERE token_hash = ? AND tipo = 'password_reset'");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification = $result->fetch_assoc();
        $stmt->close();

        if ($verification && $current_time < $verification['expira_en']) {
            $user_id = $verification['id_usuario'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Obtener el username del usuario
            $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();

            if (!$user_data) {
                error_log("Error: Usuario $user_id no encontrado después de restablecer contraseña.");
                echo json_encode(['success' => false, 'message' => 'Error interno: Usuario no encontrado.']);
                exit();
            }
            $username = $user_data['username'];

            // Actualizar la contraseña del usuario
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                // Eliminar el token de restablecimiento usado
                $stmt = $conn->prepare("DELETE FROM verificaciones_email WHERE token_hash = ? AND tipo = 'password_reset'");
                $stmt->bind_param("s", $token_hash);
                $stmt->execute();
                $stmt->close();

                // Iniciar sesión del usuario
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['loggedin'] = true;

                // Devolver éxito en JSON para que el modal lo maneje
                echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente. Iniciando sesión...']);
                exit();
            } else {
                error_log("Error al actualizar la contraseña para el usuario $user_id: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña. Por favor, inténtalo de nuevo.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'El enlace para restablecer la contraseña no es válido o ha caducado.']);
            exit();
        }
    }
} else {
    // Si se accede directamente sin POST, se muestra el formulario o mensaje
    // No se envía JSON aquí, ya que es una carga de página inicial
}

$conn->close();
?>
<div class="reset-container">
    <h2 id="retatablecer">Restablecer Contraseña</h2>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php if ($message_type === 'success'): ?>
            <a href="login.php" class="login-link">Ir a Iniciar Sesión</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($show_form && $token_valid): ?>
    <form id="reset-password-form" action="/logueo_seguridad/restablecer_contrasena.php" method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <div class="form-group">
    <label for="new_password">Nueva Contraseña:</label>
    <div class="password-input-wrapper">
    <input type="password" id="new_password" name="new_password" required style="width: 85%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
    <span id="toggleNewPassword" class="password-toggle-icon" style="cursor: pointer;    font-size: x-large; margin-left: 8px;">👁️</span>
    </div>
    </div>
    <div class="form-group" style="margin-top: 12px;">
    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
    <div class="password-input-wrapper">
    <input type="password" id="confirm_password" name="confirm_password" required style="width: 85%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
    <span id="toggleConfirmPassword" class="password-toggle-icon" style="cursor: pointer;    font-size: x-large; margin-left: 8px;">👁️</span>
    </div>
    </div>
    <button type="submit" class="btn-submit" style="width: 100%; padding: 10px; background: #3B82F6; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 12px;">Restablecer Contraseña</button>
    </form>
    <?php endif; ?>
    </div>
    
    <script src="/logueo_seguridad/password_visibility.js"></script>
<script>
    (function() {
        // Función para inicializar el formulario (se ejecuta inmediatamente al inyectarse)
        function initResetForm() {
            if (typeof setupPasswordVisibilityToggle === 'function') {
                setupPasswordVisibilityToggle('new_password', 'toggleNewPassword');
                setupPasswordVisibilityToggle('confirm_password', 'toggleConfirmPassword');
            }

            const resetPasswordForm = document.getElementById('reset-password-form');
            if (resetPasswordForm) {
                resetPasswordForm.onsubmit = async function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch(this.action, {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        const container = document.querySelector('.reset-container');
                        const existingMessage = container.querySelector('.message');
                        if (existingMessage) existingMessage.remove();

                        const newMessageDiv = document.createElement('div');
                        newMessageDiv.classList.add('message');
                        
                        if (data.success) {
                            newMessageDiv.classList.add('success');
                            newMessageDiv.style.color = '#e48415e5';
                            newMessageDiv.style.background = '#d4edda';
                            newMessageDiv.style.padding = '10px';
                            newMessageDiv.style.borderRadius = '4px';
                            newMessageDiv.style.marginBottom = '15px';
                            newMessageDiv.innerHTML = data.message;
                            resetPasswordForm.style.display = 'none';
                            
                            // Redirigir después de un momento para que vean el éxito
                            setTimeout(() => {
                                window.location.href = './';
                            }, 2000);
                        } else {
                            newMessageDiv.classList.add('error');
                            newMessageDiv.style.color = '#e48415e5';
                            newMessageDiv.style.background = '#f8d7da';
                            newMessageDiv.style.padding = '10px';
                            newMessageDiv.style.borderRadius = '4px';
                            newMessageDiv.style.marginBottom = '15px';
                            newMessageDiv.textContent = data.message;
                        }
                        container.insertBefore(newMessageDiv, container.querySelector('h2').nextSibling);

                    } catch (error) {
                        const container = document.querySelector('.reset-container');
                        const newMessageDiv = document.createElement('div');
                        newMessageDiv.classList.add('message', 'error');
                        newMessageDiv.style.color = '#e48415e5';
                        newMessageDiv.style.background = '#f8d7da';
                        newMessageDiv.style.padding = '10px';
                        newMessageDiv.style.borderRadius = '4px';
                        newMessageDiv.textContent = 'Error del servidor al restablecer la contraseña.';
                        container.insertBefore(newMessageDiv, container.querySelector('h2').nextSibling);
                    }
                };
            }
        }

        // Ejecutar inmediatamente y también en DOMContentLoaded por si acaso
        initResetForm();
        document.addEventListener('DOMContentLoaded', initResetForm);
    })();
</script>
