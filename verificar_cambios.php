<?php
/**
 * Verificador de Cambios Realizados
 * Valida que los cambios se hayan aplicado correctamente
 */

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Verificación de Cambios</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }";
echo ".check { margin: 15px 0; padding: 15px; border-radius: 4px; }";
echo ".ok { background-color: #d4edda; color: #155724; border-left: 4px solid #28a745; }";
echo ".error { background-color: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }";
echo ".warning { background-color: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }";
echo "h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }";
echo "h2 { color: #007bff; margin-top: 30px; }";
echo "table { width: 100%; border-collapse: collapse; margin: 15px 0; }";
echo "th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }";
echo "th { background-color: #007bff; color: white; }";
echo ".code { font-family: monospace; background-color: #f4f4f4; padding: 3px 6px; border-radius: 3px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>Verificación de Cambios - Sistema de Email</h1>";

$checks = [];
$errors = [];
$warnings = [];

// ============================================
// 1. Verificar archivo email_handler.php
// ============================================
echo "<h2>1. Verificación de email_handler.php</h2>";

if (file_exists('email_handler.php')) {
    $content = file_get_contents('email_handler.php');
    
    // Verificar puerto 587
    if (strpos($content, "Port = 587") !== false || strpos($content, "'Port' => 587") !== false || strpos($content, '$mail->Port = 587') !== false) {
        echo "<div class='check ok'>✓ Puerto 587 configurado correctamente</div>";
        $checks[] = ['Archivo', 'email_handler.php', 'Puerto 587', 'OK'];
    } else {
        echo "<div class='check error'>✗ Puerto 587 NO encontrado (todavía está en 465?)</div>";
        $errors[] = 'email_handler.php no tiene puerto 587';
        $checks[] = ['Archivo', 'email_handler.php', 'Puerto 587', 'ERROR'];
    }
    
    // Verificar TLS
    if (strpos($content, "SMTPSecure = 'tls'") !== false || strpos($content, '"tls"') !== false) {
        echo "<div class='check ok'>✓ Cifrado TLS configurado correctamente</div>";
        $checks[] = ['Archivo', 'email_handler.php', 'Cifrado TLS', 'OK'];
    } else {
        echo "<div class='check error'>✗ Cifrado TLS NO encontrado (todavía está 'ssl'?)</div>";
        $errors[] = 'email_handler.php no tiene TLS configurado';
        $checks[] = ['Archivo', 'email_handler.php', 'Cifrado TLS', 'ERROR'];
    }
    
    // Verificar función sendEmail
    if (strpos($content, 'function sendEmail') !== false) {
        echo "<div class='check ok'>✓ Función sendEmail() definida</div>";
        $checks[] = ['Función', 'sendEmail()', 'Definición', 'OK'];
    } else {
        echo "<div class='check error'>✗ Función sendEmail() NO encontrada</div>";
        $errors[] = 'Función sendEmail() no definida';
        $checks[] = ['Función', 'sendEmail()', 'Definición', 'ERROR'];
    }
    
    // Verificar sistema de logging
    if (strpos($content, 'email_debug.log') !== false) {
        echo "<div class='check ok'>✓ Sistema de logging configurado</div>";
        $checks[] = ['Logging', 'email_debug.log', 'Configuración', 'OK'];
    } else {
        echo "<div class='check warning'>⚠ Sistema de logging no encontrado explícitamente</div>";
        $warnings[] = 'Logging podría no estar completamente configurado';
        $checks[] = ['Logging', 'email_debug.log', 'Configuración', 'WARNING'];
    }
    
    // Verificar SMTPDebug
    if (strpos($content, 'SMTPDebug') !== false) {
        echo "<div class='check ok'>✓ Depuración SMTP configurada</div>";
        $checks[] = ['Debug', 'SMTPDebug', 'Habilitado', 'OK'];
    } else {
        echo "<div class='check warning'>⚠ Depuración SMTP no está explícita</div>";
        $warnings[] = 'SMTPDebug podría no estar habilitado';
        $checks[] = ['Debug', 'SMTPDebug', 'Habilitado', 'WARNING'];
    }
    
} else {
    echo "<div class='check error'>✗ Archivo email_handler.php NO encontrado</div>";
    $errors[] = 'email_handler.php no existe';
    $checks[] = ['Archivo', 'email_handler.php', 'Existencia', 'ERROR'];
}

// ============================================
// 2. Verificar archivo utilidades_email.php
// ============================================
echo "<h2>2. Verificación de logueo_seguridad/utilidades_email.php</h2>";

if (file_exists('logueo_seguridad/utilidades_email.php')) {
    $content = file_get_contents('logueo_seguridad/utilidades_email.php');
    
    // Verificar que incluya email_handler.php
    if (strpos($content, '__DIR__ . \'/../email_handler.php\'') !== false || strpos($content, '__DIR__ . "/../email_handler.php"') !== false) {
        echo "<div class='check ok'>✓ Incluye email_handler.php directamente</div>";
        $checks[] = ['Inclusión', 'email_handler.php', 'Directa', 'OK'];
    } else {
        echo "<div class='check error'>✗ NO incluye email_handler.php directamente</div>";
        $errors[] = 'utilidades_email.php no incluye email_handler.php';
        $checks[] = ['Inclusión', 'email_handler.php', 'Directa', 'ERROR'];
    }
    
    // Verificar función enviarEmailConPHPMailer
    if (strpos($content, 'function enviarEmailConPHPMailer') !== false) {
        echo "<div class='check ok'>✓ Función enviarEmailConPHPMailer() definida</div>";
        $checks[] = ['Función', 'enviarEmailConPHPMailer()', 'Definición', 'OK'];
    } else {
        echo "<div class='check error'>✗ Función enviarEmailConPHPMailer() NO encontrada</div>";
        $errors[] = 'Función enviarEmailConPHPMailer() no definida';
        $checks[] = ['Función', 'enviarEmailConPHPMailer()', 'Definición', 'ERROR'];
    }
    
    // Verificar llamada a sendEmail()
    if (strpos($content, 'sendEmail(') !== false) {
        echo "<div class='check ok'>✓ Llama a sendEmail() directamente</div>";
        $checks[] = ['Llamada', 'sendEmail()', 'Directa', 'OK'];
    } else {
        echo "<div class='check error'>✗ NO llama a sendEmail()</div>";
        $errors[] = 'No hay llamada a sendEmail()';
        $checks[] = ['Llamada', 'sendEmail()', 'Directa', 'ERROR'];
    }
    
    // Verificar validación de email
    if (strpos($content, 'filter_var') !== false || strpos($content, 'FILTER_VALIDATE_EMAIL') !== false) {
        echo "<div class='check ok'>✓ Validación de email implementada</div>";
        $checks[] = ['Validación', 'Email', 'Implementada', 'OK'];
    } else {
        echo "<div class='check warning'>⚠ Validación de email no explícita</div>";
        $warnings[] = 'Validación de email podría mejorar';
        $checks[] = ['Validación', 'Email', 'Implementada', 'WARNING'];
    }
    
} else {
    echo "<div class='check error'>✗ Archivo utilidades_email.php NO encontrado</div>";
    $errors[] = 'utilidades_email.php no existe';
    $checks[] = ['Archivo', 'utilidades_email.php', 'Existencia', 'ERROR'];
}

// ============================================
// 3. Verificar solicitar_restablecimiento_contrasena.php
// ============================================
echo "<h2>3. Verificación de solicitar_restablecimiento_contrasena.php</h2>";

if (file_exists('logueo_seguridad/solicitar_restablecimiento_contrasena.php')) {
    $content = file_get_contents('logueo_seguridad/solicitar_restablecimiento_contrasena.php');
    
    // Verificar que incluya utilidades_email.php
    if (strpos($content, 'utilidades_email.php') !== false) {
        echo "<div class='check ok'>✓ Incluye utilidades_email.php</div>";
        $checks[] = ['Inclusión', 'utilidades_email.php', 'Presente', 'OK'];
    } else {
        echo "<div class='check error'>✗ NO incluye utilidades_email.php</div>";
        $errors[] = 'solicitar_restablecimiento_contrasena.php no incluye utilidades_email.php';
        $checks[] = ['Inclusión', 'utilidades_email.php', 'Presente', 'ERROR'];
    }
    
    // Verificar error_log
    if (strpos($content, 'error_log') !== false) {
        echo "<div class='check ok'>✓ Logging de errores implementado</div>";
        $checks[] = ['Logging', 'error_log', 'Implementado', 'OK'];
    } else {
        echo "<div class='check warning'>⚠ error_log no encontrado</div>";
        $warnings[] = 'Logging de errores podría no estar activo';
        $checks[] = ['Logging', 'error_log', 'Implementado', 'WARNING'];
    }
    
    // Verificar validación de POST
    if (strpos($content, 'REQUEST_METHOD') !== false && strpos($content, 'POST') !== false) {
        echo "<div class='check ok'>✓ Validación de método POST presente</div>";
        $checks[] = ['Validación', 'POST', 'Presente', 'OK'];
    } else {
        echo "<div class='check warning'>⚠ Validación de POST no explícita</div>";
        $warnings[] = 'Validación de método POST podría mejorarse';
        $checks[] = ['Validación', 'POST', 'Presente', 'WARNING'];
    }
    
} else {
    echo "<div class='check error'>✗ Archivo solicitar_restablecimiento_contrasena.php NO encontrado</div>";
    $errors[] = 'solicitar_restablecimiento_contrasena.php no existe';
    $checks[] = ['Archivo', 'solicitar_restablecimiento_contrasena.php', 'Existencia', 'ERROR'];
}

// ============================================
// 4. Verificar test_email_config.php
// ============================================
echo "<h2>4. Verificación de test_email_config.php</h2>";

if (file_exists('test_email_config.php')) {
    echo "<div class='check ok'>✓ Archivo test_email_config.php existe (herramienta de diagnóstico)</div>";
    $checks[] = ['Archivo', 'test_email_config.php', 'Existencia', 'OK'];
    echo "<div style='margin: 10px 0; padding: 10px; background-color: #e7f3ff; border-left: 4px solid #2196F3;'>";
    echo "  <strong>Para probar la configuración de email, accede a:</strong><br>";
    echo "  <a href='test_email_config.php' target='_blank'>http://localhost/traductor/test_email_config.php</a>";
    echo "</div>";
} else {
    echo "<div class='check warning'>⚠ Archivo test_email_config.php NO encontrado</div>";
    $warnings[] = 'test_email_config.php no existe (útil para diagnóstico)';
    $checks[] = ['Archivo', 'test_email_config.php', 'Existencia', 'WARNING'];
}

// ============================================
// 5. Resumen General
// ============================================
echo "<h2>5. Resumen de Cambios</h2>";

echo "<table>";
echo "<tr><th>Componente</th><th>Verificación</th><th>Estado</th></tr>";

foreach ($checks as $check) {
    $statusColor = $check[3] === 'OK' ? '#28a745' : ($check[3] === 'ERROR' ? '#dc3545' : '#ffc107');
    echo "<tr>";
    echo "<td>" . htmlspecialchars($check[0]) . "</td>";
    echo "<td>" . htmlspecialchars($check[1]) . " - " . htmlspecialchars($check[2]) . "</td>";
    echo "<td style='color: $statusColor; font-weight: bold;'>" . $check[3] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Resumen final
echo "<h2>6. Resultado Final</h2>";

if (empty($errors) && empty($warnings)) {
    echo "<div class='check ok' style='padding: 20px; font-size: 16px;'>";
    echo "<strong>✓ TODOS LOS CAMBIOS VERIFICADOS CORRECTAMENTE</strong><br><br>";
    echo "El sistema de recuperación de contraseña está configurado con:<br>";
    echo "• Puerto SMTP: 587<br>";
    echo "• Cifrado: TLS<br>";
    echo "• Función directa sendEmail()<br>";
    echo "• Sistema de logging automático<br>";
    echo "• Depuración SMTP a archivo<br>";
    echo "</div>";
} elseif (empty($errors)) {
    echo "<div class='check warning' style='padding: 20px; font-size: 16px;'>";
    echo "<strong>⚠ CAMBIOS APLICADOS CON ALGUNAS ADVERTENCIAS</strong><br><br>";
    echo "Advertencias:<br>";
    foreach ($warnings as $warning) {
        echo "• $warning<br>";
    }
    echo "</div>";
} else {
    echo "<div class='check error' style='padding: 20px; font-size: 16px;'>";
    echo "<strong>✗ SE ENCONTRARON ERRORES</strong><br><br>";
    echo "Problemas encontrados:<br>";
    foreach ($errors as $error) {
        echo "• $error<br>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<div style='margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 4px;'>";
echo "<h3>Próximos Pasos:</h3>";
echo "<ol>";
echo "<li><strong>Prueba el sistema:</strong> Accede a <span class='code'>test_email_config.php</span> para probar el envío de emails</li>";
echo "<li><strong>Revisa los logs:</strong> Verifica los registros en <span class='code'>/logs/email_debug.log</span></li>";
echo "<li><strong>Prueba la recuperación:</strong> Intenta usar la función de olvidé contraseña en el sitio</li>";
echo "<li><strong>Si hay errores:</strong> Revisa el documento <span class='code'>CAMBIOS_EMAIL_REALIZADOS.md</span> para solución de problemas</li>";
echo "</ol>";
echo "</div>";

echo "</body>";
echo "</html>";
?>
