<?php
// email_handler.php - Gestor de envío de emails con PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../logueo_seguridad/PHPMailer/src/Exception.php';
require_once '../logueo_seguridad/PHPMailer/src/PHPMailer.php';
require_once '../logueo_seguridad/PHPMailer/src/SMTP.php';

function sendEmail($toEmail, $toName, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        
        // Configuración SMTP para leeingles.com con TLS
        $mail->isSMTP();
        $mail->Host = 'leeingles.com';
        $mail->Port = 465;  // Puerto SSL según los datos proporcionados
        $mail->SMTPAuth = true;
        $mail->Username = 'info@leeingles.com';
        $mail->Password = 'Holamundo25__';
        $mail->SMTPSecure = 'ssl';  // Usar SSL según los datos proporcionados
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10;  // Timeout de 10 segundos

        // Opciones SSL/TLS
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Configurar remitente y destinatario
        $mail->setFrom('info@leeingles.com', 'Leer Inglés');
        $mail->addAddress($toEmail, $toName);
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        // Intentar agregar logo si existe
        $logoPath = dirname(__DIR__) . '/img/Originals/Idoneoweb - Imagotipo.png';
        if (file_exists($logoPath)) {
            $mail->AddEmbeddedImage($logoPath, 'logo_idoneoweb', 'Idoneoweb - Imagotipo.png');
        }

        $mail->send();
        
        return ['success' => true, 'message' => 'Email enviado correctamente.'];
        
    } catch (Exception $e) {
        error_log("Error en email_handler.php (función sendEmail): " . $e->getMessage());
        
        return [
            'success' => false, 
            'error' => 'Error de PHPMailer: ' . $e->getMessage()
        ];
    }
}
