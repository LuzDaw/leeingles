<?php
/**
 * Escuchador de Webhooks de PayPal y Gestor de Pagos (Pruebas Reales)
 * Ubicación: dePago/webhook_handler.php
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

session_start();

// --- LÓGICA DE SESIÓN PARA PRUEBAS (IDÉNTICA A test.php) ---
$user_id = $_SESSION['user_id'] ?? null;
$test_username = 'Desconocido';

if (!$user_id) {
    $res = $conn->query("SELECT id, username FROM users LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $user_id = $row['id'];
        $test_username = $row['username'];
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $test_username;
    }
} else {
    $test_username = $_SESSION['username'] ?? 'Usuario';
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

// --- PROCESAMIENTO DE ACCIONES MANUALES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_action'])) {
    header('Content-Type: application/json');
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Usuario no identificado']);
        exit;
    }

    $action = $_POST['manual_action'];

    // Acción para resetear usuario (Facilita pruebas)
    if ($action === 'reset_user') {
        $conn->query("UPDATE users SET tipo_usuario = 'limitado' WHERE id = $user_id");
        $conn->query("DELETE FROM user_subscriptions WHERE user_id = $user_id");
        echo json_encode(['success' => true, 'message' => 'Usuario reseteado a LIMITADO y suscripciones eliminadas']);
        exit;
    }

    if ($action === 'create_pending_transfer') {
        $plan = $_POST['plan'] ?? 'Inicio';
        $ref = 'TRANSF_' . time();
        
        // Forzamos el método 'transferencia' y estado 'pending'
        $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, payment_method, status) VALUES (?, ?, NOW(), ?, 'transferencia', 'pending')");
        
        if (!$stmt) {
            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status) VALUES (?, ?, NOW(), ?, 'pending')");
        }
        
        $stmt->bind_param("iss", $user_id, $plan, $ref);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Transferencia registrada como PENDIENTE']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    if ($action === 'confirm_transfer') {
        $sub_id = (int)$_POST['sub_id'];
        $stmt = $conn->prepare("SELECT plan_name, user_id FROM user_subscriptions WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $plan = $row['plan_name'];
            $target_user_id = $row['user_id'];
            $plan_durations = ['Inicio' => 1, 'Ahorro' => 6, 'Pro' => 12];
            $meses = $plan_durations[$plan] ?? 1;
            $fecha_fin = date('Y-m-d H:i:s', strtotime("+$meses months"));
            
            $stmt_upd = $conn->prepare("UPDATE user_subscriptions SET status = 'active', fecha_fin = ?, fecha_inicio = NOW() WHERE id = ?");
            $stmt_upd->bind_param("si", $fecha_fin, $sub_id);
            $stmt_upd->execute();
            
            $stmt_user = $conn->prepare("UPDATE users SET tipo_usuario = ? WHERE id = ?");
            $stmt_user->bind_param("si", $plan, $target_user_id);
            $stmt_user->execute();
            
            echo json_encode(['success' => true, 'message' => 'Pago confirmado. Usuario ahora es PREMIUM.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Suscripción no encontrada o ya activa']);
        }
        exit;
    }

    // NUEVA ACCIÓN: Simular Webhook de PayPal (Dinero recibido)
    if ($action === 'simulate_paypal_webhook') {
        $paypal_id = $_POST['paypal_id'] ?? '';
        if (empty($paypal_id)) {
            echo json_encode(['success' => false, 'message' => 'ID de PayPal no proporcionado']);
            exit;
        }

        // Simulamos el objeto de recurso que enviaría PayPal
        $mock_resource = [
            'id' => $paypal_id,
            'status' => 'COMPLETED'
        ];

        // Llamamos a la función real que procesa los webhooks
        handleSaleCompleted($mock_resource, $conn);
        
        echo json_encode(['success' => true, 'message' => 'Simulación de Webhook completada. El plan debería estar activo.']);
        exit;
    }
}

// --- MANEJO DE WEBHOOKS REALES (POST JSON) ---
$raw_data = file_get_contents('php://input');
$event = json_decode($raw_data, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $event_type = $event['event_type'] ?? 'DESCONOCIDO';
    $resource = $event['resource'] ?? [];
    
    // REGLA DE ORO: Solo el estado COMPLETED de la CAPTURA activa el plan.
    $is_payment_confirmed = false;
    $paypal_id = '';

    // 1. Intentamos extraer el estado de la captura real
    if (isset($resource['purchase_units'][0]['payments']['captures'][0])) {
        $capture = $resource['purchase_units'][0]['payments']['captures'][0];
        $paypal_id = $capture['id'];
        if (strtoupper($capture['status']) === 'COMPLETED') {
            $is_payment_confirmed = true;
        }
    } elseif ($event_type === 'PAYMENT.CAPTURE.COMPLETED' || $event_type === 'PAYMENT.SALE.COMPLETED') {
        $paypal_id = $resource['id'] ?? '';
        if (strtoupper($resource['status'] ?? $resource['state'] ?? '') === 'COMPLETED') {
            $is_payment_confirmed = true;
        }
    }

    // 2. Si no hay confirmación de dinero recibido, lo tratamos como pendiente
    if (!$is_payment_confirmed) {
        // Si no tenemos ID de captura, usamos el de la orden/pago para el registro
        if (empty($paypal_id)) $paypal_id = $resource['id'] ?? '';
        handleSalePending($resource, $conn);
    } else {
        // 3. ¡DINERO RECIBIDO! Activación automática
        handleSaleCompleted($resource, $conn);
    }
    
    http_response_code(200);
    exit;
}

// Obtener estado real del usuario para la vista
$status = getUserSubscriptionStatus($user_id);

// Verificar si hay algún pago pendiente
$has_pending = false;
if ($user_id) {
    $check_pending = $conn->query("SELECT plan_name, payment_method FROM user_subscriptions WHERE user_id = $user_id AND status = 'pending' LIMIT 1");
    if ($check_pending && $check_pending->num_rows > 0) {
        $has_pending = $check_pending->fetch_assoc();
    }
}

// --- INTERFAZ DE PRUEBAS (GET) ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Pagos y Webhooks - LeeIngles</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h1, h2 { color: #1a73e8; margin-top: 0; }
        .user-info { background: #e8f0fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d2e3fc; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px; }
        .status-item { background: white; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0; }
        .status-label { font-size: 0.8rem; color: #666; display: block; }
        .status-value { font-weight: bold; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .badge-pending { color: #f57c00; background: #fff3e0; }
        .badge-active { color: #2e7d32; background: #e8f5e9; }
        .badge-expired { color: #d32f2f; background: #ffebee; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-success { background: #34a853; color: white; }
        .btn-danger { background: #d93025; color: white; }
        .plan-selector { padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-right: 10px; }
        .premium-status { color: #1a73e8; font-weight: bold; }
        .trial-status { color: #34a853; font-weight: bold; }
        .limited-status { color: #f57c00; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Estado Real del Usuario</h1>
            <button onclick="resetUser()" class="btn btn-danger" style="font-size: 0.8rem;">Resetear a LIMITADO</button>
        </div>
        <div class="user-info">
            Usuario: <strong><?php echo htmlspecialchars($test_username); ?></strong> (ID: <?php echo $user_id; ?>)
            
            <div class="status-grid">
                <div class="status-item">
                    <span class="status-label">Estado Lógico</span>
                    <span class="status-value <?php echo $status['es_premium'] ? 'premium-status' : ($status['estado_logico'] === 'EnPrueba' ? 'trial-status' : 'limited-status'); ?>">
                        <?php echo strtoupper($status['estado_logico']); ?>
                    </span>
                    <?php if ($has_pending): ?>
                        <br><span style="font-size: 0.8rem; color: #e65100; font-weight: bold; background: #fff3e0; padding: 2px 5px; border-radius: 3px; border: 1px solid #ffe0b2; display: inline-block; margin-top: 5px;">
                            ⏳ ESPERANDO PAGO: <?php echo strtoupper($has_pending['plan_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="status-item">
                    <span class="status-label">Tipo en BD (Real)</span>
                    <span class="status-value">
                        <?php 
                        $check_db = $conn->query("SELECT tipo_usuario FROM users WHERE id = $user_id");
                        $row_db = $check_db->fetch_assoc();
                        echo $row_db['tipo_usuario'] ?? 'N/A';
                        ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Días Registrado</span>
                    <span class="status-value"><?php echo $status['dias_transcurridos']; ?> días</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Periodo Gratuito</span>
                    <span class="status-value"><?php echo ($status['es_periodo_gratuito']) ? '✅ ACTIVO' : '❌ FINALIZADO'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Simular Nueva Transferencia</h2>
        <div style="display: flex; align-items: center; gap: 10px;">
            <select id="planSelect" class="plan-selector">
                <option value="Inicio">Plan Inicio (1 mes)</option>
                <option value="Ahorro">Plan Ahorro (6 meses)</option>
                <option value="Pro">Plan Pro (12 meses)</option>
            </select>
            <button onclick="createTransfer()" class="btn btn-primary">Registrar Transferencia</button>
        </div>
    </div>

    <div class="card">
        <h2>Historial de Suscripciones (Real)</h2>
        <table id="transfersTable">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Método</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($user_id) {
                    $has_records = false;
                    $res = $conn->query("SELECT * FROM user_subscriptions WHERE user_id = $user_id ORDER BY id DESC");
                    
                    if ($res && $res->num_rows > 0) {
                        $has_records = true;
                        $hoy = new DateTime();
                        while ($row = $res->fetch_assoc()):
                            $fecha_fin = new DateTime($row['fecha_fin']);
                            $is_expired = ($fecha_fin < $hoy && $row['status'] === 'active');
                            $is_pending = ($row['status'] === 'pending');
                            $method = $row['payment_method'] ?? 'paypal';
                            
                            // Determinar etiqueta de estado real
                            $estado_label = strtoupper($row['status']);
                            $badge_class = $row['status'];
                            
                            if ($is_pending) {
                                $estado_label = 'ESPERANDO PAGO';
                                $badge_class = 'pending';
                            } elseif ($is_expired) {
                                $estado_label = 'EXPIRADO';
                                $badge_class = 'expired';
                            }
                ?>
                <tr>
                    <td><strong><?php echo $row['plan_name']; ?></strong></td>
                    <td><?php echo strtoupper($method); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $badge_class; ?>">
                            <?php echo $estado_label; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_pending && $method === 'transferencia'): ?>
                            <button onclick="confirmTransfer(<?php echo $row['id']; ?>)" class="btn btn-success">Confirmar Pago</button>
                        <?php elseif ($is_pending && $method === 'paypal'): ?>
                            <button onclick="simulateWebhook('<?php echo $row['paypal_subscription_id']; ?>')" class="btn btn-primary" style="font-size: 0.7rem;">Simular Pago Recibido (Webhook)</button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; 
                    }
                    
                    if ($status['estado_logico'] === 'EnPrueba' || !$has_records):
                ?>
                <tr>
                    <td><strong>Mes de Prueba</strong></td>
                    <td>SISTEMA</td>
                    <td><?php echo $status['fecha_registro']; ?></td>
                    <td><span class="badge badge-active">ACTIVO (GRATIS)</span></td>
                    <td>-</td>
                </tr>
                <?php endif; 
                } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function resetUser() {
    if (!confirm('¿Seguro que quieres resetear este usuario a LIMITADO y borrar sus suscripciones?')) return;
    const formData = new FormData();
    formData.append('manual_action', 'reset_user');
    fetch('webhook_handler.php', { method: 'POST', body: formData })
    .then(r => r.json()).then(res => { alert(res.message); location.reload(); });
}

function createTransfer() {
    const plan = document.getElementById('planSelect').value;
    const formData = new FormData();
    formData.append('manual_action', 'create_pending_transfer');
    formData.append('plan', plan);

    fetch('webhook_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) location.reload();
        else alert('Error: ' + res.message);
    });
}

function confirmTransfer(subId) {
    const formData = new FormData();
    formData.append('manual_action', 'confirm_transfer');
    formData.append('sub_id', subId);

    fetch('webhook_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) { alert(res.message); location.reload(); }
        else alert('Error: ' + res.message);
    });
}

function simulateWebhook(paypalId) {
    const formData = new FormData();
    formData.append('manual_action', 'simulate_paypal_webhook');
    formData.append('paypal_id', paypalId);

    fetch('webhook_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) { alert(res.message); location.reload(); }
        else alert('Error: ' + res.message);
    });
}
</script>
</body>
</html>
<?php
function handleSaleCompleted($resource, $conn) {
    $paypal_id = $resource['id'] ?? $resource['parent_payment'] ?? $resource['billing_agreement_id'] ?? '';
    
    if (empty($paypal_id) && isset($resource['purchase_units'][0]['payments']['captures'][0]['id'])) {
        $paypal_id = $resource['purchase_units'][0]['payments']['captures'][0]['id'];
    }
    
    if (empty($paypal_id)) return;

    $stmt = $conn->prepare("SELECT id, user_id, plan_name FROM user_subscriptions WHERE paypal_subscription_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param("s", $paypal_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $sub_id = $row['id'];
        $user_id = $row['user_id'];
        $plan = $row['plan_name'];
        
        $plan_durations = ['Inicio' => 1, 'Ahorro' => 6, 'Pro' => 12];
        $meses = $plan_durations[$plan] ?? 1;
        $fecha_fin = date('Y-m-d H:i:s', strtotime("+$meses months"));
        
        $conn->query("UPDATE user_subscriptions SET status = 'active', fecha_inicio = NOW(), fecha_fin = '$fecha_fin' WHERE id = $sub_id");
        $conn->query("UPDATE users SET tipo_usuario = '$plan' WHERE id = $user_id");
    }
}

function handleSalePending($resource, $conn) {
    $paypal_id = $resource['id'] ?? $resource['parent_payment'] ?? '';
    if (empty($paypal_id)) return;

    // Si ya existe, no hacemos nada, solo nos aseguramos de que esté en pending
    $conn->query("UPDATE user_subscriptions SET status = 'pending' WHERE paypal_subscription_id = '$paypal_id' AND status != 'active'");
}
?>
