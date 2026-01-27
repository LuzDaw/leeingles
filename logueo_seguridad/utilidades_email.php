<?php
// logueo_seguridad/utilidades_email.php
// Utilidades para envío de emails

require_once __DIR__ . '/../recordatorio/email_templates.php';

// Determinar la URL base (se usa para generar links en los emails)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

function enviarEmailConPHPMailer($destinatarioEmail, $destinatarioNombre, $subject, $body) {
    return sendEmail($destinatarioEmail, $destinatarioNombre, $subject, $body);
}

function enviarEmailVerificacion($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $verificationLink = $base_url . "/logueo_seguridad/verificar_email.php?token=" . $token;
    
    $subject = "Verifica tu cuenta en LeeIngles";
    $titulo = "¡Bienvenido a LeeIngles!";
    $mensaje = "Gracias por registrarte. Estamos encantados de tenerte con nosotros. Para empezar a mejorar tu inglés, por favor activa tu cuenta haciendo clic en el botón de abajo.<br><br>
                <small style='color: #94a3b8;'>Este enlace expirará en 24 horas. Si no te registraste, puedes ignorar este correo.</small>";
    
    return enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, "Activar mi cuenta", $verificationLink);
}

function enviarEmailRestablecerContrasena($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $resetLink = $base_url . "/?token=" . $token;
    
    $subject = "Restablece tu contraseña en LeeIngles";
    $titulo = "Restablecer Contraseña";
    $mensaje = "Has solicitado restablecer tu contraseña. Haz clic en el botón de abajo para establecer una nueva contraseña y volver a disfrutar de LeeIngles.<br><br>
                <small style='color: #94a3b8;'>Este enlace expirará en 1 hora. Si no solicitaste esto, puedes ignorar este correo con seguridad.</small>";
    
    return enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, "Restablecer mi contraseña", $resetLink);
}
