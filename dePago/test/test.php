<?php
/**
 * Archivo de prueba para el sistema de suscripción y control de tiempo (NUEVOS PLANES)
 * Ubicación: dePago/test.php
 */

require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../subscription_functions.php';

session_start();

// Para pruebas, si no hay sesión, intentamos pillar el primer usuario de la BD
$user_id = $_SESSION['user_id'] ?? null;
$test_username = 'Desconocido';

if (!$user_id) {
    $res = $conn->query("SELECT id, username FROM users LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $user_id = $row['id'];
        $test_username = $row['username'];
    }
} else {
    $test_username = $_SESSION['username'] ?? 'Usuario';
    // Verificar que el usuario existe en la BD y obtener su nombre real si es posible
    $stmt_check = $conn->prepare("SELECT username FROM users WHERE id = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($row_check = $res_check->fetch_assoc()) {
            $test_username = $row_check['username'];
        }
    }
}

// Procesar cambio de fecha para pruebas (Simulador de tiempo)
if (isset($_POST['action']) && $_POST['action'] === 'update_date' && $user_id) {
    $days = (int)$_POST['days_ago'];
    $new_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    debugUpdateRegistrationDate($user_id, $new_date);
    header("Location: test.php?updated=1");
    exit;
}

// Procesar simulación de uso
if (isset($_POST['action']) && $_POST['action'] === 'simulate_usage' && $user_id) {
    $words = (int)$_POST['words'];
    debugAddUsage($user_id, $words);
    header("Location: test.php?usage_updated=1");
    exit;
}

// Procesar simulación de pago (NUEVO - Corregido para usar activateUserPlan)
if (isset($_POST['action']) && $_POST['action'] === 'simulate_payment' && $user_id) {
    $plan = $_POST['plan'];
    require_once __DIR__ . '/../payment_functions.php';
    
    $orderID = 'FAKE_PAYMENT_' . time();
    // Usamos 'paypal' como método porque la BD tiene un ENUM restringido
    $result = activateUserPlan($user_id, $plan, $orderID, 'paypal');
    
    if ($result['success']) {
        header("Location: test.php?payment_simulated=1");
    } else {
        echo "Error en simulación: " . $result['message'];
        exit;
    }
    exit;
}

// Procesar simulación de expiración (NUEVO)
if (isset($_POST['action']) && $_POST['action'] === 'simulate_expiration' && $user_id) {
    // Ponemos la fecha de fin de la suscripción activa en el pasado
    $conn->query("UPDATE user_subscriptions SET fecha_fin = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE user_id = $user_id AND status = 'active'");
    header("Location: test.php?expired_simulated=1");
    exit;
}

// Procesar simulación de vencimiento específico (NUEVO para renovaciones)
if (isset($_POST['action']) && $_POST['action'] === 'set_expiration' && $user_id) {
    $days = (int)$_POST['days_to_expire'];
    $new_date = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    // Actualizar la suscripción activa más reciente
    $stmt = $conn->prepare("UPDATE user_subscriptions SET fecha_fin = ? WHERE user_id = ? AND status = 'active' ORDER BY fecha_fin DESC LIMIT 1");
    $stmt->bind_param("si", $new_date, $user_id);
    $stmt->execute();
    
    header("Location: test.php?expiration_set=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Suscripción - LeeIngles</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f9; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 600px; margin: auto; margin-bottom: 20px; }
        h1, h2, h3 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .data-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
        .label { font-weight: bold; color: #555; }
        .value { color: #000; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .EnPrueba { background: #e3f2fd; color: #1976d2; }
        .limitado { background: #fff3e0; color: #f57c00; }
        .Inicio { background: #e8f5e9; color: #2e7d32; }
        .Ahorro { background: #f3e5f5; color: #7b1fa2; }
        .Pro { background: #fffde7; color: #fbc02d; border: 1px solid #fbc02d; }
        .debug { margin-top: 20px; font-size: 0.8em; color: #888; background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn-test { width:100%; padding:10px; cursor:pointer; font-weight:bold; border-radius: 4px; border: 1px solid; margin-bottom: 5px; }
        .btn-plan { color: white; border: none; }
    </style>
</head>
<body>

<div class="card">
    <h1>Control de Suscripción (Nuevos Planes)</h1>
    
    <?php if ($user_id): 
        $status = getUserSubscriptionStatus($user_id);
        $limit_info = checkTranslationLimit($user_id);
    ?>
        <p>Mostrando datos de prueba para: <strong><?php echo htmlspecialchars($test_username); ?></strong> (ID: <?php echo $user_id; ?>)</p>
        
        <div class="data-row">
            <span class="label">Estado Actual:</span>
            <span class="value status-badge <?php echo $status['estado_logico']; ?>">
                <?php echo strtoupper($status['estado_logico']); ?>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Tipo en BD:</span>
            <span class="value"><?php echo $status['tipo_base']; ?></span>
        </div>

        <div class="data-row">
            <span class="label">Fecha de Registro:</span>
            <span class="value"><?php echo $status['fecha_registro']; ?></span>
        </div>

        <div class="data-row">
            <span class="label">Días Transcurridos:</span>
            <span class="value"><?php echo $status['dias_transcurridos']; ?> días</span>
        </div>

        <div class="data-row">
            <span class="label">Periodo de Prueba:</span>
            <span class="value">
                <?php echo ($status['es_periodo_gratuito']) ? '✅ ACTIVO' : '❌ FINALIZADO'; ?>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Fin Mes de Prueba:</span>
            <span class="value"><?php echo $status['fin_mes_gratuito']; ?></span>
        </div>

        <?php
        // Obtener datos de la suscripción activa si existe
        $stmt_sub = $conn->prepare("SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY fecha_fin DESC LIMIT 1");
        $stmt_sub->bind_param("i", $user_id);
        $stmt_sub->execute();
        $res_sub = $stmt_sub->get_result();
        if ($sub = $res_sub->fetch_assoc()):
        ?>
        <div style="margin-top: 15px; padding: 10px; background: #f1f8e9; border-radius: 8px; border: 1px solid #c5e1a5;">
            <h3 style="margin-top:0; font-size: 1em;">Suscripción Activa</h3>
            <div class="data-row">
                <span class="label">Plan:</span>
                <span class="value"><?php echo $sub['plan_name']; ?></span>
            </div>
            <div class="data-row">
                <span class="label">Vence el:</span>
                <span class="value" style="font-weight:bold;"><?php echo $sub['fecha_fin']; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 5px solid #1976d2;">
            <h3 style="margin-top:0;">Consumo Semanal (Límite 300)</h3>
            <div class="data-row">
                <span class="label">Palabras Traducidas:</span>
                <span class="value" style="font-size: 1.2em; font-weight: bold; color: #1976d2;">
                    <?php echo getWeeklyUsage($user_id); ?>
                </span>
            </div>
            <div class="data-row">
                <span class="label">Próximo Reinicio:</span>
                <span class="value"><?php echo $status['proximo_reinicio_semanal']; ?></span>
            </div>
            
            <div style="margin-top: 10px; font-size: 0.9em;">
                <strong>Estado:</strong> <?php echo $limit_info['can_translate'] ? '✅ PERMITIDO' : '❌ BLOQUEADO'; ?>
            </div>
        </div>

        <div class="debug">
            <strong>Raw Data (Status):</strong><br>
            <pre><?php print_r($status); ?></pre>
        </div>

    <?php else: ?>
        <p style="color: red;">No se encontró ningún usuario para realizar la prueba.</p>
    <?php endif; ?>
</div>

<?php if ($user_id): ?>
<div class="card">
    <h2>Simulador de Pagos (Activar Planes)</h2>
    <p>Simula un pago exitoso de PayPal para activar un plan:</p>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
        <form method="POST">
            <input type="hidden" name="action" value="simulate_payment">
            <input type="hidden" name="plan" value="Inicio">
            <button type="submit" class="btn-test btn-plan" style="background:#2e7d32;">Plan Inicio (1 mes)</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="simulate_payment">
            <input type="hidden" name="plan" value="Ahorro">
            <button type="submit" class="btn-test btn-plan" style="background:#7b1fa2;">Plan Ahorro (6 meses)</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="simulate_payment">
            <input type="hidden" name="plan" value="Pro">
            <button type="submit" class="btn-test btn-plan" style="background:#fbc02d; color: black;">Plan Pro (12 meses)</button>
        </form>
    </div>
    <form method="POST" style="margin-top: 10px;">
        <input type="hidden" name="action" value="simulate_expiration">
        <button type="submit" class="btn-test" style="background:#d32f2f; color:white; border:none;">Simular EXPIRACIÓN de Plan</button>
    </form>
</div>

<div class="card">
    <h2>Simulador de Vencimiento (Para Renovaciones)</h2>
    <p>Establece cuándo vence el plan actual para probar la acumulación de tiempo:</p>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
        <form method="POST">
            <input type="hidden" name="action" value="set_expiration">
            <input type="hidden" name="days_to_expire" value="0">
            <button type="submit" class="btn-test" style="background:#fce4ec; color:#c2185b;">Vence HOY</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="set_expiration">
            <input type="hidden" name="days_to_expire" value="2">
            <button type="submit" class="btn-test" style="background:#f3e5f5; color:#7b1fa2;">Vence en 2 días</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="set_expiration">
            <input type="hidden" name="days_to_expire" value="5">
            <button type="submit" class="btn-test" style="background:#e8eaf6; color:#3f51b5;">Vence en 5 días</button>
        </form>
    </div>
    <form method="POST" style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
        <input type="hidden" name="action" value="set_expiration">
        <label>Días personalizados:</label>
        <input type="number" name="days_to_expire" value="10" style="padding:8px; width:60px;">
        <button type="submit" style="padding:8px 20px; cursor:pointer;">Establecer</button>
    </form>
</div>

<div class="card">
    <h2>Simulador de Tiempo (Registro)</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <form method="POST">
            <input type="hidden" name="action" value="update_date">
            <input type="hidden" name="days_ago" value="0">
            <button type="submit" class="btn-test" style="background:#e3f2fd; color:#1976d2;">Registro HOY (EnPrueba)</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="update_date">
            <input type="hidden" name="days_ago" value="35">
            <button type="submit" class="btn-test" style="background:#fff3e0; color:#f57c00;">Hace 35 Días (Limitado)</button>
        </form>
    </div>
</div>

<div class="card">
    <h2>Simulador de Consumo</h2>
    <form method="POST">
        <input type="hidden" name="action" value="simulate_usage">
        <input type="number" name="words" value="100" style="padding:8px; width:80px;">
        <button type="submit" style="padding:8px 20px; cursor:pointer;">Añadir Palabras</button>
    </form>
</div>
<?php endif; ?>

    <?php include '../limit_modal.php'; ?>
    <script src="../limit_modal.js"></script>
</body>
</html>
