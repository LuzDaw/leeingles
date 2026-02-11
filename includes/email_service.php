<?php
// includes/email_service.php
// Servicio centralizado para envío de emails con PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../logueo_seguridad/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../logueo_seguridad/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../logueo_seguridad/PHPMailer/src/SMTP.php';

/**
 * Envía un correo electrónico utilizando PHPMailer.
 *
 * Parámetros y comportamiento compatible con la función previa `sendEmail`.
 */
function sendEmail($toEmail, $toName, $subject, $body) {
    try {
        $mail = new PHPMailer(true);

        // Configuración SMTP (leer de entorno si está disponible, fallback a valores actuales)
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'leeingles.com';
        $mail->Port = getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : 465;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'info@leeingles.com';
        $mail->Password = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'Holamundo25__';
        $mail->SMTPSecure = getenv('SMTP_SECURE') ? getenv('SMTP_SECURE') : 'ssl';
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = getenv('SMTP_TIMEOUT') ? (int)getenv('SMTP_TIMEOUT') : 10;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $fromEmail = getenv('SMTP_FROM_EMAIL') ? getenv('SMTP_FROM_EMAIL') : 'info@leeingles.com';
        $fromName = getenv('SMTP_FROM_NAME') ? getenv('SMTP_FROM_NAME') : 'Leer Inglés';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $logoPath = dirname(__DIR__) . '/img/Originals/Idoneoweb - Imagotipo.png';
        if (file_exists($logoPath)) {
            $mail->AddEmbeddedImage($logoPath, 'logo_idoneoweb', 'Idoneoweb - Imagotipo.png');
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email enviado correctamente.'];

    } catch (Exception $e) {
        error_log("Error en email_service.php (función sendEmail): " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error de PHPMailer: ' . $e->getMessage()
        ];
    }
}
