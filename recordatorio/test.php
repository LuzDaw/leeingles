<?php
/**
 * recordatorio/test.php
 * Centro de pruebas para el sistema de notificaciones y recordatorios.
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../dePago/subscription_functions.php';
require_once __DIR__ . '/email_templates.php';

session_start();

// Para pruebas, si no hay sesión, intentamos pillar el primer usuario de la BD
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $res = $conn->query("SELECT id, username, email FROM users LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['id'];
        $test_username = $row['username'];
        $test_email = $row['email'];
    }
} else {
    $test_username = $_SESSION['username'] ?? 'Usuario';
    // Obtener email del usuario actual
    $res = $conn->query("SELECT email FROM users WHERE id = $user_id");
    $test_email = ($row = $res->fetch_assoc()) ? $row['email'] : '';
}

// --- PROCESAMIENTO DE ACCIONES ---

// 1. Simular Inactividad (Cambiar fecha de última conexión)
if (isset($_POST['action']) && $_POST['action'] === 'simulate_inactivity' && $user_id) {
    $days = (int)$_POST['days_ago'];
    $new_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    debugUpdateLastConnection($user_id, $new_date);
    header("Location: test.php?inactivity_updated=1");
    exit;
}

// 1b. Simular Cambio de Tipo de Usuario (Para poder ser detectado)
if (isset($_POST['action']) && $_POST['action'] === 'change_type' && $user_id) {
    $new_type = $_POST['tipo_usuario'];
    $stmt = $conn->prepare("UPDATE users SET tipo_usuario = ? WHERE id = ?");
    $stmt->bind_param("si", $new_type, $user_id);
    $stmt->execute();
    header("Location: test.php?type_updated=1");
    exit;
}

// 2. Enviar Email de Prueba (Plantilla Reutilizable)
if (isset($_POST['action']) && $_POST['action'] === 'test_email' && $user_id) {
    $asunto = $_POST['asunto'] ?? 'Asunto de prueba';
    $titulo = $_POST['titulo'] ?? 'Título de prueba';
    $mensaje = $_POST['mensaje'] ?? 'Este es un mensaje de prueba personalizado.';
    
    $email_res = enviarEmailPlantillaBase($test_email, $test_username, $asunto, $titulo, $mensaje);
    $status_msg = $email_res['success'] ? "success" : "error";
    header("Location: test.php?email_sent=$status_msg");
    exit;
}

// 3. Enviar Recordatorio de Inactividad Específico
if (isset($_POST['action']) && $_POST['action'] === 'test_inactivity_email' && $user_id) {
    $email_res = enviarRecordatorioInactividad($test_email, $test_username);
    $status_msg = $email_res['success'] ? "success" : "error";
    header("Location: test.php?email_sent=$status_msg");
    exit;
}

// 4. Ejecutar Proceso Automático (Simulador de Cron)
if (isset($_POST['action']) && $_POST['action'] === 'run_cron' && $user_id) {
    // Activamos el modo prueba para el cron
    $is_test_mode = true;
    
    // Capturamos la salida del cron para mostrarla
    ob_start();
    include 'cron_inactividad.php';
    $output = ob_get_clean();
    
    // Guardamos el resultado en la sesión para mostrarlo tras el redirect
    $_SESSION['cron_output'] = $output;
    header("Location: test.php?cron_executed=1");
    exit;
}

// Obtener datos actualizados del usuario para la ficha
$user_data = null;
if ($user_id) {
    $res = $conn->query("SELECT ultima_conexion, ultimo_email_recordatorio, tipo_usuario FROM users WHERE id = $user_id");
    $user_data = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Recordatorios - LeeIngles</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; padding: 20px; background: #f0f2f5; color: #1a1a1a; }
        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 25px; border: 1px solid #e1e4e8; }
        h1 { color: #1e40af; text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1e40af; padding-bottom: 10px; }
        h2 { color: #334155; border-left: 5px solid #3b82f6; padding-left: 15px; margin-top: 0; }
        .data-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .label { font-weight: 600; color: #64748b; }
        .value { font-weight: 500; }
        .btn { width: 100%; padding: 12px; cursor: pointer; font-weight: bold; border-radius: 8px; border: none; transition: all 0.2s; margin-top: 10px; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: white; border: 2px solid #cbd5e1; color: #475569; }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #475569; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-limitado { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

<div class="container">
    <h1>Centro de Pruebas: Recordatorios</h1>

    <?php if (isset($_GET['email_sent'])): ?>
        <div class="alert <?php echo $_GET['email_sent'] == 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $_GET['email_sent'] == 'success' ? '✅ Email enviado correctamente a ' . $test_email : '❌ Error al enviar el email. Revisa la configuración SMTP.'; ?>
        </div>
    <?php endif; ?>

    <!-- FICHA DEL USUARIO -->
    <div class="card">
        <h2>Ficha de Actividad del Usuario</h2>
        <p>Probando con: <strong><?php echo htmlspecialchars($test_username); ?></strong> (<?php echo htmlspecialchars($test_email); ?>)</p>
        
        <div class="data-row">
            <span class="label">Estado de Suscripción:</span>
            <span class="value">
                <span class="badge badge-<?php echo $user_data['tipo_usuario']; ?>">
                    <?php echo $user_data['tipo_usuario']; ?>
                </span>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Última Conexión Registrada:</span>
            <span class="value" style="color: #2563eb; font-weight: bold;">
                <?php echo $user_data['ultima_conexion'] ?? 'Nunca (NULL)'; ?>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Último Email de Recordatorio:</span>
            <span class="value">
                <?php echo $user_data['ultimo_email_recordatorio'] ?? 'Ninguno enviado aún'; ?>
            </span>
        </div>
    </div>

    <!-- SIMULADOR DE ESTADO -->
    <div class="card">
        <h2>Simulador de Estado y Tiempo</h2>
        <p>Para que el sistema te detecte, debes ser de tipo <strong>'limitado'</strong> y llevar <strong>14 días</strong> inactivo:</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <form method="POST">
                <input type="hidden" name="action" value="change_type">
                <input type="hidden" name="tipo_usuario" value="limitado">
                <button type="submit" class="btn btn-outline" style="background: #fff7ed; border-color: #fdba74;">Cambiarme a 'LIMITADO'</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="change_type">
                <input type="hidden" name="tipo_usuario" value="gratis">
                <button type="submit" class="btn btn-outline">Volver a 'GRATIS'</button>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <form method="POST">
                <input type="hidden" name="action" value="simulate_inactivity">
                <input type="hidden" name="days_ago" value="0">
                <button type="submit" class="btn btn-outline">Conectado HOY</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="simulate_inactivity">
                <input type="hidden" name="days_ago" value="15">
                <button type="submit" class="btn btn-outline" style="color: #ff8a00; border-color: #fecaca;">Inactivo (15 días)</button>
            </form>
        </div>
    </div>

    <!-- PRUEBA DE EMAIL PERSONALIZADO -->
    <div class="card">
        <h2>Prueba de Email Personalizado</h2>
        <p>Envía un correo usando la <strong>plantilla reutilizable</strong>:</p>
        <form method="POST">
            <input type="hidden" name="action" value="test_email">
            <div class="form-group">
                <label>Asunto del Email:</label>
                <input type="text" name="asunto" value="¡Hola de nuevo desde LeeIngles!">
            </div>
            <div class="form-group">
                <label>Título (H2):</label>
                <input type="text" name="titulo" value="Tenemos novedades para ti">
            </div>
            <div class="form-group">
                <label>Mensaje (HTML soportado):</label>
                <textarea name="mensaje" rows="4">¿Sabías que leer solo 10 minutos al día mejora tu inglés un 50% más rápido? <br><br> Entra hoy y descubre los nuevos textos que hemos seleccionado para ti.</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enviar Email Personalizado</button>
        </form>
    </div>

    <!-- PRUEBA DE RECORDATORIO ESPECÍFICO -->
    <div class="card">
        <h2>Recordatorio de Inactividad (Rápido)</h2>
        <p>Envía el email estándar de "Te echamos de menos":</p>
        <form method="POST">
            <input type="hidden" name="action" value="test_inactivity_email">
            <button type="submit" class="btn btn-outline">Enviar Recordatorio Estándar</button>
        </form>
    </div>

    <!-- SIMULADOR DE CRON -->
    <div class="card" style="border-top: 5px solid #1e40af;">
        <h2>Simulador de Proceso Automático (Cron)</h2>
        <p>Este botón ejecuta la lógica real que se usará en producción para detectar y enviar correos a <strong>todos</strong> los usuarios inactivos.</p>
        
        <?php if (isset($_SESSION['cron_output'])): ?>
            <div style="background: #1e293b; color: #34d399; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-bottom: 15px; white-space: pre-wrap;"><?php 
                echo htmlspecialchars($_SESSION['cron_output']); 
                unset($_SESSION['cron_output']); 
            ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="run_cron">
            <button type="submit" class="btn btn-primary" style="background: #1e40af;">
                🚀 Ejecutar Detección y Envío Automático
            </button>
        </form>
        <p style="font-size: 12px; color: #64748b; margin-top: 10px;">
            <em>Nota: Para que te detecte a ti mismo, primero debes usar el botón "Inactivo (15 días)" arriba.</em>
        </p>
    </div>

</div>

</body>
</html>
