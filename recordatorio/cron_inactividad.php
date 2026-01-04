<?php
/**
 * recordatorio/cron_inactividad.php
 * Script para ser ejecutado por un Cron Job diariamente.
 * Detecta usuarios inactivos y les envía un recordatorio.
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../dePago/subscription_functions.php';
require_once __DIR__ . '/email_templates.php';

// --- SEGURIDAD ---
$cron_key = "LeeIngles2026_Secure";
$received_key = $_GET['key'] ?? '';

// Si no es modo prueba (llamada interna) y la clave no coincide, denegar acceso
if (!isset($is_test_mode) && $received_key !== $cron_key) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acceso denegado. Clave de seguridad incorrecta.";
    exit;
}

// --- CONFIGURACIÓN ---
$dias_inactividad = 14; // 2 semanas
$dias_entre_emails = 7;  // No enviar más de un email por semana

// Modo prueba: permite ignorar la restricción de días entre emails
$modo_prueba = isset($is_test_mode) && $is_test_mode === true;

echo "Iniciando proceso de recordatorios de inactividad..." . ($modo_prueba ? " [MODO PRUEBA ACTIVADO]" : "") . "\n";

// DIAGNÓSTICO: Ver cuántos usuarios hay en total y de qué tipo
if ($modo_prueba) {
    $diag = $conn->query("SELECT tipo_usuario, COUNT(*) as total FROM users GROUP BY tipo_usuario");
    echo "Resumen de usuarios en BD:\n";
    while($d = $diag->fetch_assoc()) {
        echo "- {$d['tipo_usuario']}: {$d['total']}\n";
    }
}

// 1. Obtener usuarios inactivos (gratis y limitado)
$sql = "
    SELECT id, username, email, ultima_conexion, fecha_registro, ultimo_email_recordatorio, tipo_usuario 
    FROM users 
    WHERE 1=1
";

// Filtramos por usuarios que no son premium (gratis y limitado)
if (!$modo_prueba) {
    $sql .= " AND tipo_usuario IN ('gratis', 'limitado')";
}

$sql .= " AND (
        ultima_conexion < DATE_SUB(NOW(), INTERVAL ? DAY)
        OR (ultima_conexion IS NULL AND fecha_registro < DATE_SUB(NOW(), INTERVAL ? DAY))
    )";

// Si no es modo prueba, aplicamos la restricción de no repetir email en X días
if (!$modo_prueba) {
    $sql .= " AND (ultimo_email_recordatorio IS NULL OR ultimo_email_recordatorio < DATE_SUB(NOW(), INTERVAL ? DAY))";
}

$sql .= " LIMIT 50";

$stmt = $conn->prepare($sql);

if ($modo_prueba) {
    $stmt->bind_param("ii", $dias_inactividad, $dias_inactividad);
} else {
    $stmt->bind_param("iii", $dias_inactividad, $dias_inactividad, $dias_entre_emails);
}
$stmt->execute();
$result = $stmt->get_result();

$enviados = 0;
$errores = 0;

if ($result->num_rows === 0) {
    echo "No se han encontrado usuarios que cumplan los criterios de inactividad ($dias_inactividad días).\n";
}

while ($user = $result->fetch_assoc()) {
    // En modo prueba, si el usuario es premium, lo ignoramos (el recordatorio es para gratis/limitado)
    if ($modo_prueba && $user['tipo_usuario'] === 'premium') {
        echo "Usuario detectado: {$user['username']} - IGNORADO (Tipo: premium, no recibe este recordatorio)\n";
        continue;
    }

    echo "Procesando usuario: {$user['username']} ({$user['email']}) [Tipo: {$user['tipo_usuario']}]... ";
    
    $res = enviarRecordatorioInactividad($user['email'], $user['username']);
    
    if ($res['success']) {
        // Actualizar la fecha del último email enviado
        $update = $conn->prepare("UPDATE users SET ultimo_email_recordatorio = NOW() WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();
        $update->close();
        
        echo "Email ENVIADO.\n";
        $enviados++;
    } else {
        echo "ERROR: " . $res['error'] . "\n";
        $errores++;
    }
}

echo "\nProceso finalizado.\n";
echo "Total enviados: $enviados\n";
echo "Total errores: $errores\n";

$conn->close();
?>
