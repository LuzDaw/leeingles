<?php
// email_handler.php - Gestor de envío de emails con PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'logueo_seguridad/PHPMailer/src/Exception.php';
require_once 'logueo_seguridad/PHPMailer/src/PHPMailer.php';
require_once 'logueo_seguridad/PHPMailer/src/SMTP.php';

function sendEmail($toEmail, $toName, $subject, $body) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    // Log de depuración
    $logFile = __DIR__ . '/logs/email_debug.log';
    if (!is_dir(__DIR__ . '/logs')) {
        @mkdir(__DIR__ . '/logs', 0755, true);
    }

    // Función auxiliar para logging
    $log = function($message) use ($logFile) {
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    };

    try {
        $log("Iniciando envío de email a: $toEmail");
        
        $mail = new PHPMailer(true);
        
        // Configuración SMTP para leeingles.com con TLS
        $mail->isSMTP();
        $mail->Host = 'leeingles.com';
        $mail->Port = 587;  // Puerto TLS (alternativa a 465 SSL)
        $mail->SMTPAuth = true;
        $mail->Username = 'info@leeingles.com';
        $mail->Password = 'Holamundo25__';
        $mail->SMTPSecure = 'tls';  // Usar TLS en lugar de SSL
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

        // Habilitar depuración de PHPMailer (solo a archivo log)
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) use ($log) {
            $log("[SMTP DEBUG] " . $str);
        };

        $log("Configuración SMTP establecida (Host: leeingles.com, Puerto: 587, Método: TLS)");

        // Configurar remitente y destinatario
        $mail->setFrom('info@leeingles.com', 'Traductor App');
        $mail->addAddress($toEmail, $toName);
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        // Intentar agregar logo si existe
        $logoPath = __DIR__ . '/img/Originals/Idoneoweb - Imagotipo.png';
        if (file_exists($logoPath)) {
            $mail->AddEmbeddedImage($logoPath, 'logo_idoneoweb', 'Idoneoweb - Imagotipo.png');
            $log("Logo embebido agregado");
        } else {
            $log("Advertencia: Logo no encontrado en $logoPath");
        }

        $log("Intentando enviar email...");
        $mail->send();
        
        $log("Email enviado exitosamente a $toEmail");
        return ['success' => true, 'message' => 'Email enviado correctamente.'];
        
    } catch (Exception $e) {
        $errorMsg = "Error en email_handler.php (función sendEmail): " . $e->getMessage();
        $log($errorMsg);
        error_log($errorMsg);
        
        return [
            'success' => false, 
            'error' => 'Error al enviar email: ' . $e->getMessage(),
            'details' => 'Por favor, verifica la configuración SMTP y la conectividad del servidor.'
        ];
    }
}

// Si el script es accedido directamente como proxy HTTP
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ob_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    try {
        if (!isset($_POST['email']) || !isset($_POST['subject']) || !isset($_POST['body'])) {
            throw new Exception('Parámetros faltantes (email, subject, body requeridos)');
        }
        $result = sendEmail($_POST['email'], $_POST['name'] ?? 'Usuario', $_POST['subject'], $_POST['body']);
        ob_end_clean();
        echo json_encode($result);
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        error_log("Error en email_handler.php (acceso directo): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error en el proxy de email: ' . $e->getMessage()]);
        exit();
    }
}
