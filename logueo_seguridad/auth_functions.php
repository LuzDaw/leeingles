<?php
/**
 * Funciones centralizadas de autenticación y validación
 * Elimina duplicación de código en login, register y AJAX
 */

// ==================== CSRF ====================
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ==================== VALIDACIONES ====================
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

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateUsername($username) {
    return !empty(trim($username)) && strlen(trim($username)) >= 3;
}

// ==================== AUTENTICACIÓN ====================
function authenticateUser($username, $password, $remember_me = false) {
    global $conn;
    
    // Asegurar que $username sea una cadena desde el principio y luego trim
    $username_input = (string)$username; 
    $username_input = trim($username_input); 
    $password_input = (string)$password; // Asegurar que $password sea una cadena

    if (empty($username_input) || empty($password_input)) {
        return ['success' => false, 'error' => 'Usuario y contraseña son requeridos.'];
    }
    
    $stmt = $conn->prepare("SELECT id, username, password, is_admin, estado FROM users WHERE username = ? OR email = ?");
    if ($stmt === false) {
        return ['success' => false, 'error' => 'Error en la base de datos.'];
    }
    
    // Asegurarse de que los parámetros para bind_param sean siempre cadenas no vacías
    // Usar los valores de input directamente, ya que la validación empty() ya se hizo
    $stmt->bind_param("ss", $username_input, $username_input); // Buscar por username o email con el mismo input
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $real_username, $hashed_password, $is_admin, $estado);
        $stmt->fetch();

        if (password_verify($password_input, $hashed_password)) { // Usar $password_input
            // Verificar si cuenta está activa
            if (isset($estado) && $estado === 'pendiente') {
                $stmt->close();
                return [
                    'success' => false, 
                    'error' => 'Tu cuenta no está activa. Por favor, verifica tu email.',
                    'pendingVerification' => true,
                    'user_id' => $user_id
                ];
            }
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $real_username;
            $_SESSION['is_admin'] = $is_admin;
            
            // Manejar "Mantener sesión"
            if ($remember_me) {
                ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
                session_set_cookie_params(30 * 24 * 60 * 60);
            }
            
            $stmt->close();
            return ['success' => true, 'user_id' => $user_id, 'username' => $real_username, 'is_admin' => $is_admin];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Has introducido mal el usuario o contraseña o usuario no existe'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'error' => 'Has introducido mal el usuario o contraseña o usuario no existe'];
    }
}

// ==================== REGISTRO ====================
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
    
    // Comprobar si el nombre de usuario ya existe
    $stmt_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($stmt_username === false) {
        return ['success' => false, 'error' => 'Error en la base de datos al verificar usuario.'];
    }
    $stmt_username->bind_param("s", $username);
    $stmt_username->execute();
    $stmt_username->store_result();
    if ($stmt_username->num_rows > 0) {
        $stmt_username->close();
        return ['success' => false, 'error' => 'El nombre de usuario ya existe.'];
    }
    $stmt_username->close();

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
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function logoutUser() {
    session_destroy();
    return true;
}
