<?php
/**
 * recordatorio/email_templates.php
 * Sistema de plantillas de email reutilizables para LeeIngles
 */

require_once __DIR__ . '/../email_handler.php';

/**
 * Envía un email con un diseño profesional y personalizable.
 * 
 * @param string $destinatarioEmail Email del usuario
 * @param string $destinatarioNombre Nombre del usuario
 * @param string $subject Asunto del correo
 * @param string $titulo Título grande dentro del email (H2)
 * @param string $mensaje Cuerpo del mensaje (soporta HTML básico)
 * @param string $botonTexto Texto del botón de acción
 * @param string $botonUrl URL a la que apunta el botón
 * @return array Resultado del envío ['success' => true/false, ...]
 */
function enviarEmailPlantillaBase($destinatarioEmail, $destinatarioNombre, $subject, $titulo, $mensaje, $botonTexto = 'Ir a la App', $botonUrl = 'https://leeingles.com') {
    
    $body = "
<html>
<head>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; margin: 0;'>
    <div style='background-color: #ffffff; padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eef;'>
        
        <!-- Header / Logo -->
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='cid:logo_idoneoweb' alt='LeeIngles Logo' style='max-width: 60px;'>
            <h1 style='color: #1e40af; margin: 10px 0; font-size: 24px;'>LeeIngles</h1>
        </div>

        <!-- Contenido -->
        <div style='color: #334155; line-height: 1.6;'>
            <h2 style='color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;'>$titulo</h2>
            <p>Hola <strong>$destinatarioNombre</strong>,</p>
            <div style='margin: 20px 0;'>
                $mensaje
            </div>
        </div>

        <!-- Botón de Acción -->
        <div style='text-align: center; margin: 35px 0;'>
            <a href='$botonUrl' style='background-color: #2563eb; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);'>
                $botonTexto
            </a>
        </div>

        <!-- Footer -->
        <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; color: #94a3b8; font-size: 12px;'>
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
 * Ejemplo de función específica reutilizando la base: Recordatorio de Inactividad
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
