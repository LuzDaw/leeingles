<?php
/**
 * Escuchador de Webhooks de PayPal y Gestor de Pagos (Pruebas Reales)
 * Ubicación: dePago/webhook_handler.php
 */

require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../subscription_functions.php';
require_once __DIR__ . '/../payment_functions.php';

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
        $result = handlePaypalWebhookResource($mock_resource, true);
        
        echo json_encode($result);
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

    // 1. Intentamos extraer el estado de la captura real
    if (isset($resource['purchase_units'][0]['payments']['captures'][0])) {
        $capture = $resource['purchase_units'][0]['payments']['captures'][0];
        if (strtoupper($capture['status']) === 'COMPLETED') {
            $is_payment_confirmed = true;
        }
    } elseif ($event_type === 'PAYMENT.CAPTURE.COMPLETED' || $event_type === 'PAYMENT.SALE.COMPLETED') {
        if (strtoupper($resource['status'] ?? $resource['state'] ?? '') === 'COMPLETED') {
            $is_payment_confirmed = true;
        }
    }

    // 2. Procesar usando las funciones centrales
    handlePaypalWebhookResource($resource, $is_payment_confirmed);
    
    http_response_code(200);
    exit;
}

// Obtener estado real del usuario para la vista
$status = getUserSubscriptionStatus($user_id);

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
        .badge-active { color: #2e7d32; background: #e8f5e9; }
        .badge-expired { color: #d32f2f; background: #ffebee; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-danger { background: #d93025; color: white; }
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
        <h2>Historial de Suscripciones (Real)</h2>
        <table id="transfersTable">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Método</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
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
                            $method = $row['payment_method'] ?? 'paypal';
                            
                            // Determinar etiqueta de estado real
                            $estado_label = strtoupper($row['status']);
                            $badge_class = $row['status'];
                            
                            if ($is_expired) {
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
</script>
</body>
</html>
