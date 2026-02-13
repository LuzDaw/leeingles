<?php
// logueo_seguridad/utilidades_email.php
// Utilidades para envío de emails


// Cargar configuración central para BASE_URL
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../recordatorio/email_templates.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../includes/external_services.php';

// Usar BASE_URL desde includes/config.php
$base_url = defined('BASE_URL') ? BASE_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);

/**
 * Envía un correo electrónico utilizando la función `sendEmail` del gestor de emails.
 *
 * Esta función actúa como un wrapper para la función de envío de emails principal,
 * asegurando que todos los emails de seguridad pasen por el mismo sistema.
 *
 * @param string $destinatarioEmail La dirección de correo electrónico del destinatario.
 * @param string $destinatarioNombre El nombre del destinatario.
 * @param string $subject El asunto del correo electrónico.
 * @param string $body El cuerpo del correo electrónico (puede contener HTML).
 * @return array El resultado de la función `sendEmail`.
 */
// Eliminada función enviarEmailConPHPMailer. Usar sendEmail() de includes/email_service.php directamente.

/**
 * Envía un correo electrónico de verificación de cuenta al usuario.
 *
 * Genera un enlace de verificación utilizando el token proporcionado y la URL base,
 * y luego envía un email con una plantilla predefinida.
 *
 * @param string $destinatarioEmail La dirección de correo electrónico del destinatario.
 * @param string $destinatarioNombre El nombre del destinatario.
 * @param string $token El token de verificación único para el usuario.
 * @return array El resultado de la función `enviarEmailPlantillaBase`.
 */
function enviarEmailVerificacion($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $verificationLink = $base_url . "/logueo_seguridad/verificar_email.php?token=" . $token;
    
    $subject = "Verifica tu cuenta en LeeIngles";
    $titulo = "¡Bienvenido a LeeIngles!";
    $mensaje = "Gracias por registrarte. Estamos encantados de tenerte con nosotros. Para empezar a mejorar tu inglés, por favor activa tu cuenta haciendo clic en el botón de abajo.<br><br>
                <small style='color: #94a3b8;'>Este enlace expirará en 24 horas. Si no te registraste, puedes ignorar este correo.</small>";
    
    return enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, "Activar mi cuenta", $verificationLink);
}

/**
 * Envía un correo electrónico para restablecer la contraseña del usuario.
 *
 * Genera un enlace de restablecimiento de contraseña utilizando el token y la URL base,
 * y luego envía un email con una plantilla predefinida.
 *
 * @param string $destinatarioEmail La dirección de correo electrónico del destinatario.
 * @param string $destinatarioNombre El nombre del destinatario.
 * @param string $token El token de restablecimiento de contraseña único.
 * @return array El resultado de la función `enviarEmailPlantillaBase`.
 */
function enviarEmailRestablecerContrasena($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $resetLink = $base_url . "/?token=" . $token;
    
    $subject = "Restablece tu contraseña en LeeIngles";
    $titulo = "Restablecer Contraseña";
    $mensaje = "Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo para establecer una nueva contraseña y volver a disfrutar de LeeIngles.<br><br>
                <small style='color: #94a3b8;'>Este enlace expirará en 1 hora. Si no solicitaste esto, puedes ignorar este correo con seguridad.</small>";
    
    return enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, "Restablecer mi contraseña", $resetLink);
}
