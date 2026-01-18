<?php
// logueo_seguridad/utilidades_email.php
// Utilidades para envío de emails

require_once __DIR__ . '/../actions/email_handler.php';

// Determinar la URL base (se usa para generar links en los emails)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

function enviarEmailConPHPMailer($destinatarioEmail, $destinatarioNombre, $subject, $body) {
    return sendEmail($destinatarioEmail, $destinatarioNombre, $subject, $body);
}

function enviarEmailVerificacion($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $verificationLink = $base_url . "/?token=" . $token;
    
    $subject = "Verifica tu cuenta en Traductor";
    $body = "
<html>
<head>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;'>
    <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <img src='cid:logo_idoneoweb' alt='Idoneoweb Logo' style='max-width: 50px;'> 
        </div>
        <h2 style='color: #333;'>Bienvenido a Traductor</h2>
        <p>Hola $destinatarioNombre,</p>
        <p>Gracias por registrarte. Por favor, haz clic en el siguiente enlace para activar tu cuenta:</p>
        <p>
            <a href='$verificationLink' style='background:#3B82F6; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>
                Activar mi cuenta
            </a>
        </p>
        <p>O copia y pega este enlace:</p>
        <p style='word-break: break-all; color: #666;'>$verificationLink</p>
        <p style='color: #999; font-size: 12px;'>Este enlace expirará en 24 horas.</p>
        <p style='color: #999; font-size: 12px;'>Si no te registraste, ignora este correo.</p>
    </div>
</body>
</html>
    ";
    
    return enviarEmailConPHPMailer($destinatarioEmail, $destinatarioNombre, $subject, $body);
}

function enviarEmailRestablecerContrasena($destinatarioEmail, $destinatarioNombre, $token) {
    global $base_url;
    
    $resetLink = $base_url . "/?token=" . $token;
    
    $subject = "Restablece tu contraseña en Traductor";
    $body = "
<html>
<head>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;'>
    <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <img src='cid:logo_idoneoweb' alt='Idoneoweb Logo' style='max-width: 150px;'>
        </div>
        <h2 style='color: #333;'>Restablecer Contraseña</h2>
        <p>Hola $destinatarioNombre,</p>
        <p>Has solicitado restablecer tu contraseña. Por favor, haz clic en el siguiente enlace para establecer una nueva contraseña:</p>
        <p>
            <a href='$resetLink' style='background:#3B82F6; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>
                Restablecer mi contraseña
            </a>
        </p>
        <p>O copia y pega este enlace:</p>
        <p style='word-break: break-all; color: #666;'>$resetLink</p>
        <p style='color: #999; font-size: 12px;'>Este enlace expirará en 1 hora.</p>
        <p style='color: #999; font-size: 12px;'>Si no solicitaste esto, ignora este correo.</p>
    </div>
</body>
</html>
    ";
    
    return enviarEmailConPHPMailer($destinatarioEmail, $destinatarioNombre, $subject, $body);
}
