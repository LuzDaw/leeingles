<?php
/**
 * Test Email Configuration
 * 
 * Este script prueba la configuración de PHPMailer y conectividad SMTP
 * Se puede acceder en: http://localhost/test_email_config.php
 */

// Configurar para mostrar errores (solo en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/email_handler.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Configuración de Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #3B82F6;
            border-radius: 4px;
        }
        .section h2 {
            margin-top: 0;
            color: #3B82F6;
            font-size: 18px;
        }
        .config-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
            color: #333;
        }
        .value {
            color: #666;
            margin-left: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .button {
            background-color: #3B82F6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #2d68cc;
        }
        .log-section {
            background-color: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin: 15px 0;
        }
        .log-entry {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test de Configuración de Email</h1>

        <div class="section">
            <h2>Configuración Actual del Servidor SMTP</h2>
            <div class="config-item">
                <span class="label">Host:</span>
                <span class="value">leeingles.com</span>
            </div>
            <div class="config-item">
                <span class="label">Puerto:</span>
                <span class="value">587</span>
            </div>
            <div class="config-item">
                <span class="label">Método de Cifrado:</span>
                <span class="value">TLS (Transport Layer Security)</span>
            </div>
            <div class="config-item">
                <span class="label">Usuario:</span>
                <span class="value">info@leeingles.com</span>
            </div>
            <div class="config-item">
                <span class="label">Autenticación:</span>
                <span class="value">Activada (SMTP AUTH)</span>
            </div>
        </div>

        <div class="section">
            <h2>Información del Sistema</h2>
            <div class="config-item">
                <span class="label">PHP Version:</span>
                <span class="value"><?php echo phpversion(); ?></span>
            </div>
            <div class="config-item">
                <span class="label">OpenSSL:</span>
                <span class="value"><?php echo extension_loaded('openssl') ? 'Instalado ✓' : 'NO INSTALADO ✗'; ?></span>
            </div>
            <div class="config-item">
                <span class="label">cURL:</span>
                <span class="value"><?php echo extension_loaded('curl') ? 'Instalado ✓' : 'NO INSTALADO ✗'; ?></span>
            </div>
            <div class="config-item">
                <span class="label">Directorio de Logs:</span>
                <span class="value"><?php echo __DIR__ . '/logs/'; ?></span>
            </div>
        </div>

        <div class="section">
            <h2>Prueba de Envío de Email</h2>
            <p>Introduce un email de prueba para verificar que el sistema puede enviar correos:</p>
            <form id="testForm" method="POST">
                <div style="margin: 10px 0;">
                    <label for="testEmail">Email de prueba:</label><br>
                    <input type="email" id="testEmail" name="email" placeholder="tu@email.com" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                <button type="submit" class="button">Enviar Email de Prueba</button>
            </form>
            <div id="result"></div>
        </div>

        <div class="section">
            <h2>Archivo de Log de Depuración</h2>
            <p>Los eventos de SMTP se registran en: <code><?php echo __DIR__ . '/logs/email_debug.log'; ?></code></p>
            <button class="button" onclick="reloadLog()">Recargar Log</button>
            <div class="log-section" id="logContent">
                <div class="log-entry">Esperando actividad...</div>
            </div>
        </div>

        <div class="section">
            <h2>Solución de Problemas</h2>
            <ul>
                <li><strong>Conexión rechazada:</strong> Verifica que el puerto 587 esté abierto en el servidor de hosting</li>
                <li><strong>Autenticación fallida:</strong> Comprueba que el usuario y contraseña son correctos</li>
                <li><strong>Certificado no válido:</strong> El script está configurado para ignorar errores de certificado (no recomendado en producción)</li>
                <li><strong>Timeout:</strong> Aumenta el timeout en email_handler.php si el servidor responde lentamente</li>
                <li><strong>Email en spam:</strong> Es normal que los primeros emails de prueba vayan a spam</li>
            </ul>
        </div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('testEmail').value;
            const resultDiv = document.getElementById('result');
            
            resultDiv.innerHTML = '<div class="warning">Enviando email de prueba...</div>';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'send_test=1&test_email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="success"><strong>✓ Email enviado correctamente!</strong><br>' + data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="error"><strong>✗ Error al enviar email:</strong><br>' + data.message + '</div>';
                }
                setTimeout(reloadLog, 2000);
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error"><strong>✗ Error de red:</strong><br>' + error.message + '</div>';
            });
        });

        function reloadLog() {
            fetch(window.location.href + '?get_log=1')
                .then(response => response.text())
                .then(data => {
                    const logDiv = document.getElementById('logContent');
                    if (data.trim() === '') {
                        logDiv.innerHTML = '<div class="log-entry">Sin entradas de log aún</div>';
                    } else {
                        logDiv.innerHTML = data.split('\n').map(line => 
                            '<div class="log-entry">' + (line || '&nbsp;') + '</div>'
                        ).join('');
                        logDiv.scrollTop = logDiv.scrollHeight;
                    }
                });
        }

        // Recargar log cada 3 segundos
        setInterval(reloadLog, 3000);
        reloadLog();
    </script>
</body>
</html>

<?php
// Manejo de peticiones AJAX para pruebas

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    header('Content-Type: application/json');
    
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    $result = sendEmail(
        $testEmail,
        'Prueba',
        'Test de Configuración - Email de Prueba',
        '<h1>Email de Prueba</h1><p>Este es un email de prueba para verificar que la configuración SMTP funciona correctamente.</p><p>Si recibes este email, ¡la configuración está funcionando!</p>'
    );
    
    echo json_encode($result);
    exit;
}

if (isset($_GET['get_log'])) {
    header('Content-Type: text/plain');
    $logFile = __DIR__ . '/logs/email_debug.log';
    
    if (file_exists($logFile)) {
        $log = file_get_contents($logFile);
        // Mostrar las últimas 50 líneas
        $lines = array_slice(explode("\n", $log), -50);
        echo implode("\n", $lines);
    } else {
        echo 'Log file not created yet';
    }
    exit;
}
?>
