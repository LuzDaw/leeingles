<?php
/**
 * recordatorio/email_templates.php
 * Sistema de plantillas de email reutilizables para LeeIngles
 */

require_once __DIR__ . '/../actions/email_handler.php';

// Paleta básica de colores para emails, alineada con css/color-theme.css
$EMAIL_COLORS = [
    'background'  => '#F5F7FA', // var(--background-color)
    'cardBorder'  => '#E8F1F5', // var(--success-light)
    'primary'     => '#1E3A8A', // var(--primary-color)
    'secondary'   => '#3B82F6', // var(--secondary-color)
    'accent'      => '#FF8A00', // var(--accent-color)
    'text'        => '#111827', // var(--text-color)
    'textMuted'   => '#6B7280', // var(--text-muted)
    'divider'     => '#E5E7EB',
];

/**
 * Envía un email con un diseño profesional y personalizable utilizando una plantilla base.
 *
 * Esta función construye el cuerpo HTML de un correo electrónico con un diseño predefinido,
 * incluyendo un encabezado con logo, un título, un mensaje personalizable y un botón de acción.
 * Luego, utiliza la función `sendEmail` para enviar el correo.
 *
 * @param string $destinatarioEmail La dirección de correo electrónico del destinatario.
 * @param string $destinatarioNombre El nombre del destinatario.
 * @param string $subject El asunto del correo electrónico.
 * @param string $titulo El título principal que se mostrará dentro del cuerpo del email.
 * @param string $mensaje El contenido principal del mensaje (puede contener HTML básico).
 * @param string $botonTexto (Opcional) El texto que se mostrará en el botón de acción. Por defecto es 'Ir a la App'.
 * @param string $botonUrl (Opcional) La URL a la que dirigirá el botón de acción. Por defecto es 'https://leeingles.com'.
 * @return array El resultado del envío del correo, devuelto por `sendEmail`.
 */
function enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, $botonTexto = 'Ir a la App', $botonUrl = 'https://leeingles.com') {
    global $EMAIL_COLORS;

    $body = "
<html>
<head>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; background-color: {$EMAIL_COLORS['background']}; padding: 20px; margin: 0;'>
    <div style='background-color: #ffffff; padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid {$EMAIL_COLORS['cardBorder']};'>
        
        <!-- Header / Logo -->
        <div style='text-align: center; margin-bottom: 25px;'>
            <div style='display: inline-block; vertical-align: middle;'>
                <img src='cid:logo_idoneoweb' alt='LeeIngles Logo' width='60' style='display:inline-block; vertical-align: middle; max-width:100%; height:auto;'>
                <span style='color: {$EMAIL_COLORS['primary']}; font-size: 28px; font-weight: bold; vertical-align: middle; margin-left: 10px; font-family: Arial, sans-serif;'>LeeInglés</span>
            </div>
        </div>

        <!-- Contenido -->
        <div style='color: {$EMAIL_COLORS['text']}; line-height: 1.6;'>
            <h2 style='color: {$EMAIL_COLORS['text']}; border-bottom: 2px solid {$EMAIL_COLORS['divider']}; padding-bottom: 10px;'>$titulo</h2>
            <p>Hola <strong>$destinatarioNombre</strong>,</p>
            <div style='margin: 20px 0;'>
                $mensaje
            </div>
        </div>

        <!-- Botón de Acción -->
        <div style='text-align: center; margin: 35px 0;'>
            <a href='$botonUrl' style='background-color: {$EMAIL_COLORS['secondary']}; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);'>
                $botonTexto
            </a>
        </div>

        <!-- Footer -->
        <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid {$EMAIL_COLORS['divider']}; text-align: center; color: {$EMAIL_COLORS['textMuted']}; font-size: 12px;'>
            <p>Has recibido este correo porque eres usuario de LeeIngles.com</p>
            <p>&copy; " . date('Y') . " LeeIngles - Aprende inglés leyendo lo que te gusta.</p>
        </div>
    </div>
</body>
</html>
    ";
    
    return sendEmail($destinatarioEmail, $destinatarioNombre, $subject, $body);
}

/**
 * Envía un correo electrónico de recordatorio de inactividad a un usuario.
 *
 * Utiliza la plantilla base de email para enviar un mensaje personalizado
 * animando al usuario a volver a la aplicación para continuar practicando inglés.
 *
 * @param string $email La dirección de correo electrónico del usuario inactivo.
 * @param string $nombre El nombre del usuario inactivo.
 * @return array El resultado del envío del correo, devuelto por `enviarEmailPlantillaBase`.
 */
function enviarRecordatorioInactividad($email, $nombre) {
    $asunto = "¡Te echamos de menos en LeeIngles!";
    $titulo = "Vuelve a practicar tu inglés";
    $mensaje = "Hemos notado que llevas unos días sin entrar a la aplicación. <br><br>
                Recuerda que la constancia es la clave para dominar un nuevo idioma. Tenemos nuevos textos esperándote para que sigas mejorando tu vocabulario.";
    $botonTexto = "Continuar aprendiendo";
    $botonUrl = "https://leeingles.com/";

    return enviarEmailPlantillaBase($email, $nombre, $asunto, $titulo, $mensaje, $botonTexto, $botonUrl);
}
?>
