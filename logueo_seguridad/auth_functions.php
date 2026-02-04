<?php
/**
 * Funciones centralizadas de autenticación y validación
 * Elimina duplicación de código en login, register y AJAX
 */

// ==================== CSRF ====================
/**
 * Genera y almacena un token CSRF en la sesión del usuario.
 *
 * Si ya existe un token CSRF en la sesión, se devuelve el existente.
 * De lo contrario, se genera uno nuevo utilizando `random_bytes` y se almacena.
 *
 * @return string El token CSRF actual o recién generado.
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si un token CSRF proporcionado coincide con el almacenado en la sesión.
 *
 * Utiliza `hash_equals` para una comparación segura contra ataques de temporización.
 *
 * @param string $token El token CSRF a verificar.
 * @return bool `true` si el token es válido, `false` en caso contrario.
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ==================== VALIDACIONES ====================
/**
 * Valida una contraseña según un conjunto de criterios de seguridad.
 *
 * Los criterios incluyen longitud mínima (8 caracteres), al menos una mayúscula,
 * una minúscula, un número y un carácter especial.
 *
 * @param string $password La contraseña a validar.
 * @return array Un array asociativo con 'valid' (booleano) y 'errors' (array de strings) si no es válida.
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos un número.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'La contraseña debe contener al menos un carácter especial.';
    }
    
    return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
}

/**
 * Valida el formato de una dirección de correo electrónico.
 *
 * Utiliza `filter_var` con `FILTER_VALIDATE_EMAIL`.
 *
 * @param string $email La dirección de correo electrónico a validar.
 * @return string|false La dirección de correo electrónico validada si es válida, o `false` en caso contrario.
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valida un nombre de usuario.
 *
 * Verifica que el nombre de usuario no esté vacío y tenga al menos 3 caracteres después de recortar espacios.
 *
 * @param string $username El nombre de usuario a validar.
 * @return bool `true` si el nombre de usuario es válido, `false` en caso contrario.
 */
function validateUsername($username) {
    return !empty(trim($username)) && strlen(trim($username)) >= 3;
}

// ==================== AUTENTICACIÓN ====================
/**
 * Autentica a un usuario.
 *
 * Verifica las credenciales de email y contraseña contra la base de datos.
 * Si las credenciales son correctas, inicia una sesión, actualiza la última conexión
 * del usuario y opcionalmente configura la sesión para "recordarme".
 * También maneja el estado de verificación pendiente del email.
 *
 * @param string $email La dirección de correo electrónico del usuario.
 * @param string $password La contraseña del usuario.
 * @param bool $remember_me (Opcional) Indica si se debe mantener la sesión iniciada. Por defecto es false.
 * @return array Un array asociativo con 'success' (booleano) y 'error' (string) si falla,
 *               o 'user_id', 'username' e 'is_admin' si la autenticación es exitosa.
 */
function authenticateUser($email, $password, $remember_me = false) {
    global $conn;
    
    $email_input = trim((string)$email); 
    $password_input = (string)$password;

    if (empty($email_input) || empty($password_input)) {
        return ['success' => false, 'error' => 'Email y contraseña son requeridos.'];
    }
    
    $stmt = $conn->prepare("SELECT id, username, password, is_admin, estado FROM users WHERE email = ?");
    if ($stmt === false) {
        return ['success' => false, 'error' => 'Error en la base de datos.'];
    }
    
    $stmt->bind_param("s", $email_input);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $real_username, $hashed_password, $is_admin, $estado);
        $stmt->fetch();

        if (password_verify($password_input, $hashed_password)) {
            if (isset($estado) && $estado === 'pendiente') {
                $stmt->close();
                return [
                    'success' => false, 
                    'error' => 'Tu cuenta no está activa. Por favor, verifica tu email.',
                    'pendingVerification' => true,
                    'user_id' => $user_id
                ];
            }

            // Actualizar última conexión
            $stmt_update = $conn->prepare("UPDATE users SET ultima_conexion = NOW() WHERE id = ?");
            $stmt_update->bind_param("i", $user_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = (string)($real_username ?? ''); // La conversión a string maneja el caso null, el linter puede ser estricto.
            $_SESSION['is_admin'] = (int)($is_admin ?? 0);
            
            if ($remember_me) {
                ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
                session_set_cookie_params(30 * 24 * 60 * 60);
            }
            
            $stmt->close();
            return ['success' => true, 'user_id' => $user_id, 'username' => $real_username, 'is_admin' => $is_admin];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'La contraseña introducida es incorrecta.'];
        }
    }
    
    $stmt->close();
    return ['success' => false, 'error' => 'El email introducido no está registrado.'];
}

// ==================== REGISTRO ====================
/**
 * Registra un nuevo usuario en la aplicación.
 *
 * Realiza validaciones de nombre de usuario, email y contraseña.
 * Comprueba si el email ya está registrado. Hashea la contraseña
 * y la almacena en la base de datos. Opcionalmente, puede marcar
 * la cuenta como 'pendiente' de verificación de email.
 *
 * @param string $username El nombre de usuario.
 * @param string $email La dirección de correo electrónico.
 * @param string $password La contraseña.
 * @param bool $send_verification (Opcional) Indica si se requiere verificación de email. Por defecto es false.
 * @return array Un array asociativo con 'success' (booleano) y 'error' (string) si falla,
 *               o 'user_id' y 'username' si el registro es exitoso.
 */
function registerUser($username, $email, $password, $send_verification = false) {
    global $conn;
    
    $username = trim($username);
    $email = trim($email);
    
    // Validaciones
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
    }
    
    if (!validateUsername($username)) {
        return ['success' => false, 'error' => 'El nombre de usuario debe tener al menos 3 caracteres.'];
    }
    
    if (!validateEmail($email)) {
        return ['success' => false, 'error' => 'El formato del email no es válido.'];
    }
    
    $pwd_validation = validatePassword($password);
    if (!$pwd_validation['valid']) {
        return ['success' => false, 'error' => $pwd_validation['errors'][0]];
    }
    
    // Nota: Ya no comprobamos si el username existe porque ya no es obligatorio que sea único.
    // El login se hace por email, que sí debe ser único.

    // Comprobar si el email ya existe
    $stmt_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt_email === false) {
        return ['success' => false, 'error' => 'Error en la base de datos al verificar email.'];
    }
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    $stmt_email->store_result();
    if ($stmt_email->num_rows > 0) {
        $stmt_email->close();
        return ['success' => false, 'error' => 'El email ya existe.'];
    }
    $stmt_email->close();
    
    // Insertar nuevo usuario
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $estado = $send_verification ? 'pendiente' : 'activo';
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, estado) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        return ['success' => false, 'error' => 'Error en la base de datos.'];
    }
    
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $estado);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        // Si no requiere verificación de email, iniciar sesión automáticamente
        if (!$send_verification) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = 0;
        }
        
        return ['success' => true, 'user_id' => $user_id, 'username' => $username];
    } else {
        $stmt->close();
        return ['success' => false, 'error' => 'Error al registrar el usuario'];
    }
}

// ==================== SESIÓN ====================
/**
 * Verifica si el usuario actual está autenticado (sesión iniciada).
 *
 * @return bool `true` si el usuario está autenticado, `false` en caso contrario.
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtiene el ID del usuario actualmente autenticado.
 *
 * @return int|null El ID del usuario si está autenticado, o null en caso contrario.
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtiene el nombre de usuario del usuario actualmente autenticado.
 *
 * @return string|null El nombre de usuario si está autenticado, o null en caso contrario.
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Verifica si el usuario actualmente autenticado tiene privilegios de administrador.
 *
 * @return bool `true` si el usuario es administrador, `false` en caso contrario.
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Cierra la sesión del usuario actual.
 *
 * Destruye la sesión PHP, eliminando todos los datos de sesión.
 *
 * @return bool Siempre devuelve `true`.
 */
function logoutUser() {
    session_destroy();
    return true;
}
