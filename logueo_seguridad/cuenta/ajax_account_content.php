<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../includes/content_functions.php';
require_once __DIR__ . '/../../dePago/subscription_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="text-align: center; padding: 40px; color: #ff8a00;">Debes iniciar sesión para ver tu cuenta.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario
$user_data = ['username' => 'Usuario', 'email' => ''];
$stmt_user = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($row_user = $result_user->fetch_assoc()) {
        $user_data = $row_user;
    }
    $stmt_user->close();
}

// Obtener datos reales de suscripción
$status = getUserSubscriptionStatus($user_id);
if (!$status) {
    echo '<div style="text-align: center; padding: 40px; color: #ff8a00;">Error al obtener datos de suscripción.</div>';
    exit;
}
$limit_info = checkTranslationLimit($user_id);

// Obtener datos reales de actividad
$uploaded_texts = getTotalUserTexts($user_id);

// Tiempo de lectura real
$total_reading_seconds = 0;
$stmt_read = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM reading_time WHERE user_id = ?");
if ($stmt_read) {
    $stmt_read->bind_param("i", $user_id);
    $stmt_read->execute();
    $res_read = $stmt_read->get_result();
    $row_read = $res_read->fetch_assoc();
    $total_reading_seconds = $row_read['total_seconds'] ?? 0;
    $stmt_read->close();
}
$reading_h = floor($total_reading_seconds / 3600);
$reading_m = floor(($total_reading_seconds % 3600) / 60);
$reading_time = "{$reading_h}h {$reading_m}m";

// Tiempo de práctica real
$total_practice_seconds = 0;
$stmt_prac = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM practice_time WHERE user_id = ?");
if ($stmt_prac) {
    $stmt_prac->bind_param("i", $user_id);
    $stmt_prac->execute();
    $res_prac = $stmt_prac->get_result();
    $row_prac = $res_prac->fetch_assoc();
    $total_practice_seconds = $row_prac['total_seconds'] ?? 0;
    $stmt_prac->close();
}
$practice_h = floor($total_practice_seconds / 3600);
$practice_m = floor(($total_practice_seconds % 3600) / 60);
$practice_time = "{$practice_h}h {$practice_m}m";

// Mapeo de estados para visualización
$status_labels = [
    'EnPrueba' => 'Promoción: 30 días gratis',
    'limitado' => 'Plan Gratuito - 300 traducciones por semana',
    'Inicio'   => 'Plan Basico - 30 días Sin límintes',
    'Ahorro'   => 'Plan Ahorro - 6 meses Sin límintes',
    'Pro'      => 'Plan Pro 12 meses Sin límintes'
];

$account_status = $status_labels[$status['estado_logico']] ?? 'Plan Básico - Ativo 30 Días';

$free_month_start = date('d/m/Y', strtotime($status['fecha_registro']));
$free_month_end = date('d/m/Y', strtotime($status['fin_mes_gratuito']));

// Días restantes y fechas del plan
$days_remaining_text = "";
$plan_start_date = $free_month_start;
$plan_end_date = $free_month_end;

if ($status['estado_logico'] === 'EnPrueba') {
    $now = new DateTime();
    $end = new DateTime($status['fin_mes_gratuito']);
    $diff = $now->diff($end);
    $days = $diff->invert ? 0 : $diff->days;
    $days_remaining_text = " · $days días restantes";
} elseif ($status['es_premium']) {
    $stmt_sub_active = $conn->prepare("SELECT fecha_inicio, fecha_fin FROM user_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY fecha_fin DESC LIMIT 1");
    if ($stmt_sub_active) {
        $stmt_sub_active->bind_param("i", $user_id);
        $stmt_sub_active->execute();
        $res_sub_active = $stmt_sub_active->get_result();
        if ($sub = $res_sub_active->fetch_assoc()) {
            $plan_start_date = date('d/m/Y', strtotime($sub['fecha_inicio']));
            $plan_end_date = date('d/m/Y', strtotime($sub['fecha_fin']));
            $now = new DateTime();
            $end = new DateTime($sub['fecha_fin']);
            $diff = $now->diff($end);
            $days = $diff->invert ? 0 : $diff->days;
            $days_remaining_text = " · $days días restantes";
        }
        $stmt_sub_active->close();
    }
}

// Mapeo de estado simplificado para la tabla
$simple_status = 'limitado';
if ($status['es_premium']) {
    $simple_status = 'premium';
} elseif ($status['estado_logico'] === 'EnPrueba') {
    $simple_status = 'prueba';
}

// Preparar datos para la tabla de planes
$has_premium = $status['es_premium'];
$is_trial_active = $status['es_periodo_gratuito'];
$next_reset_date = date('d/m/Y', strtotime($status['proximo_reinicio_semanal']));
$plan_table_rows = [];

// 1. Fila de Plan Premium
if ($has_premium) {
    $plan_table_rows[] = [
        'activo' => '✅',
        'caracteristica' => '<a href="#subscription-plans-section" style="color: inherit; text-decoration: none;">' . $account_status . '</a>',
        'inicio' => $plan_start_date,
        'fin' => $plan_end_date,
        'clase' => ''
    ];
} else {
    $plan_table_rows[] = [
        'activo' => '-',
        'caracteristica' => '<a href="#subscription-plans-section" style="color: #3b82f6; text-decoration: underline; display: inline-flex; align-items: center; gap: 6px;"><span>💎</span> Planes desde 4,99 €</a>',
        'inicio' => '-',
        'fin' => '-',
        'clase' => ''
    ];
}

// 2. Fila de Plan Limitado
$plan_table_rows[] = [
    'activo' => ($has_premium || $is_trial_active) ? '❌' : '✅',
    'caracteristica' => 'Plan gratuito – 300 traducciones por semana',
    'inicio' => $free_month_start,
    'fin' => $next_reset_date,
    'clase' => ($has_premium || $is_trial_active) ? 'row-faint' : ''
];

// 3. Fila de Plan de Prueba (Solo si está activo)
if ($is_trial_active) {
    $plan_table_rows[] = [
        'activo' => '✅',
        'caracteristica' => 'Promoción: 30 días gratis',
        'inicio' => $free_month_start,
        'fin' => $free_month_end,
        'clase' => ''
    ];
}

// Traducciones semanales
$usage = getWeeklyUsage($user_id);
$base_limit = 300;
$usage_percent = min(100, round(($usage / $base_limit) * 100));
$available_translations = max(0, $base_limit - $usage);

// Obtener historial de suscripciones/pagos
$payment_history = [];
$check_col = $conn->query("SHOW COLUMNS FROM user_subscriptions LIKE 'payment_method'");
$has_payment_method = ($check_col && $check_col->num_rows > 0);

$sql_history = $has_payment_method 
    ? "SELECT plan_name, payment_method, status, created_at, fecha_fin FROM user_subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
    : "SELECT plan_name, 'paypal' as payment_method, status, created_at, fecha_fin FROM user_subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";

$stmt = $conn->prepare($sql_history);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $payment_history[] = $row;
}
$stmt->close();
?>

<style>
    .account-dashboard {
        font-family: 'Inter', -apple-system, sans-serif;
        color: #1e293b;
    }
    .dashboard-section {
        margin-bottom: 40px;
    }
    .section-title {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
    }
    .horizontal-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        flex: 1;
        min-width: 200px;
        transition: all 0.2s ease;
    }
    .stat-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .stat-label {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 4px;
        display: block;
    }
    .stat-value {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }
    .stat-subvalue {
        font-size: 13px;
        color: #94a3b8;
        margin-top: 4px;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
    }
    .badge-status.active {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .feature-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        color: #334155;
        border: 1px solid #f1f5f9;
    }
    .activity-number {
        font-size: 28px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
        margin-bottom: 4px;
    }

    .progress-container {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        margin-top: 8px;
        overflow: hidden;
    }
    .progress-bar {
        height: 100%;
        background: #3b82f6;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    .progress-bar.warning { background: #f59e0b; }
    .progress-bar.danger { background: #ff8a00; }

    .user-info-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 20px;
    }
    .user-info-table th {
        background: #f8fafc;
        padding: 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
    }
    .user-info-table td {
        padding: 16px;
        font-size: 15px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
    }
    .user-info-table tr:last-child td {
        border-bottom: none;
    }
    .badge-status.prueba { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
    .badge-status.premium { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-status.limitado { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .row-faint {
        opacity: 0.5;
        background: #fcfcfc;
    }
    .row-faint td {
        color: #94a3b8 !important;
    }
    /* Asegurar que los contenedores de PayPal tengan altura para ser visibles */
    .paypal-button-container {
        min-height: 150px;
        margin-top: 15px;
        width: 100%;
        border: 1px dashed #e2e8f0;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #ffffff;
        overflow: hidden;
    }
</style>

<script>
    // Cargador único del SDK para pagos únicos (Capture) con namespace dedicado
    (function() {
        if (!window.paypalUnicoLoaded) {
            window.paypalUnicoLoaded = true;
            var script = document.createElement('script');
            // Unificamos con el Client ID de suscripciones que es el real que funciona
            script.src = "https://www.paypal.com/sdk/js?client-id=ATfzdeOVWZvM17U3geOdl_yV513zZfX7oCm_wa0wqog2acHfSIz846MkdZnpu7oCdWFzqdMn0NEN0xSM&currency=EUR";
            script.setAttribute('data-namespace', 'paypalUnico');
            script.async = true;
            document.head.appendChild(script);
        }
    })();

    // Función global de inicialización replicando sandbox_minimal.php
    window.initUnicoButton = function(containerId, amount, description, planName) {
        var container = document.getElementById(containerId);
        if (!container) return;

        // Verificamos el namespace paypalUnico
        if (typeof window.paypalUnico === 'undefined') {
            if (!container.innerHTML.includes('Cargando')) {
                container.innerHTML = '<div style="text-align:center; color:#64748b; padding:20px; font-size:13px;">Cargando pasarela de pago...</div>';
            }
            setTimeout(() => window.initUnicoButton(containerId, amount, description, planName), 1000);
            return;
        }
        
        // Si ya tiene contenido de PayPal, no re-renderizamos
        if (container.querySelector('iframe') || container.querySelector('.paypal-buttons')) {
            return;
        }

        container.innerHTML = '';

        window.paypalUnico.Buttons({
            style: {
                shape: 'rect',
                color: 'gold',
                layout: 'vertical',
                label: 'pay'
            },
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: amount,
                            currency_code: 'EUR'
                        },
                        description: description
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    // Usamos el estado principal de la orden (COMPLETED)
                    let realStatus = details.status;
                    
                    return fetch('dePago/ajax_confirm_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'orderID=' + details.id + '&status=' + realStatus + '&plan=' + planName
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            window.location.href = 'index.php?tab=account&payment_success=1';
                        } else {
                            alert('Error: ' + res.message);
                        }
                    });
                });
            },
            onError: function(err) {
                console.error('PayPal Button Error (' + planName + '):', err);
                container.innerHTML = '<div style="color:red; padding:10px; font-size:12px;">Error al cargar el botón. Reintenta en unos segundos.</div>';
            }
        }).render('#' + containerId).catch(err => {
            console.error('Error al renderizar el botón en ' + containerId + ':', err);
        });
    };
</script>

<div class="tab-content-wrapper account-dashboard">
    <?php 
    if (isset($_GET['payment_success'])) {
        include '../../dePago/payment_success_modal.php';
    }
    ?>
    <!-- 1️⃣ Encabezado – Identidad del usuario -->
    <div class="dashboard-section" style="margin-bottom: 32px;">
        <h1 style="margin: 0; font-size: 32px; font-weight: 800; color: #0f172a;">Hola, <?= htmlspecialchars($user_data['username']) ?></h1>
        
        <table class="user-info-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($user_data['username']) ?></td>
                    <td><?= htmlspecialchars($user_data['email']) ?></td>
                    <td><?= $free_month_start ?></td>
                    <td>
                        <span class="badge-status <?= $simple_status ?>">
                            <?= ucfirst($simple_status) ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 3️⃣ Tabla de Planes y Características -->
    <div class="dashboard-section">
        <h3 class="section-title">Detalles de tu suscripción</h3>
        <table class="user-info-table">
            <thead>
                <tr>
                    <th style="width: 80px; text-align: center;">Activo</th>
                    <th>Característica / Plan</th>
                <th>Inicio</th>
                    <th>Renovación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plan_table_rows as $row): ?>
                    <tr class="<?= $row['clase'] ?>">
                        <td style="text-align: center; font-size: 18px;"><?= $row['activo'] ?></td>
                        <td style="font-weight: 600;"><?= $row['caracteristica'] ?></td>
                        <td><?= $row['inicio'] ?></td>
                        <td><?= $row['fin'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 4️⃣ Actividad del usuario -->
    <div class="dashboard-section">
        <h3 class="section-title">Tu actividad y límites</h3>
        <div class="horizontal-row">
            <div class="stat-card">
                <span class="stat-label">Traducciones Semanales</span>
                <?php if ($status['es_premium'] || $status['estado_logico'] === 'EnPrueba'): ?>
                    <div class="activity-number">Ilimitado</div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 100%; background: #10b981;"></div>
                    </div>
                    <span class="stat-subvalue">Plan Premium Activo 💎</span>
                <?php else: ?>
                    <div class="activity-number"><?= $usage ?> <span style="font-size: 14px; color: #64748b; font-weight: 400;">/ <?= $base_limit ?></span></div>
                    <div class="progress-container">
                        <div class="progress-bar <?= $usage_percent > 90 ? 'danger' : ($usage_percent > 70 ? 'warning' : '') ?>" style="width: <?= $usage_percent ?>%"></div>
                    </div>
                    <span class="stat-subvalue">Reinicia el <?= $next_reset_date ?></span>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <span class="stat-label">Tiempo de lectura</span>
                <div class="activity-number"><?= $reading_time ?></div>
                <span class="stat-subvalue">Total acumulado</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Tiempo de práctica</span>
                <div class="activity-number"><?= $practice_time ?></div>
                <span class="stat-subvalue">Ejercicios realizados</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Textos subidos</span>
                <div class="activity-number"><?= $uploaded_texts ?></div>
                <span class="stat-subvalue">Biblioteca personal</span>
            </div>
        </div>
    </div>

    <!-- 4.5️⃣ Historial de Pagos -->
    <?php if (!empty($payment_history)): ?>
    <div class="dashboard-section">
        <h3 class="section-title">Historial de suscripciones</h3>
        <table class="user-info-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Método</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_history as $pay): 
                    $pay_status_class = 'limitado';
                    if ($pay['status'] === 'active') $pay_status_class = 'premium';
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($pay['plan_name']) ?></td>
                    <td><?= strtoupper(htmlspecialchars($pay['payment_method'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($pay['created_at'])) ?></td>
                    <td>
                        <span class="badge-status <?= $pay_status_class ?>">
                            <?= $pay['status'] === 'active' ? 'Activo' : $pay['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- 5️⃣ Pago Único (Desplegable) -->
    <div id="one-time-payment-section" class="info-box" style="margin-top: 64px; border: 1px solid #e2e8f0; background: #f8fafc;">
        <div id="toggle-one-time-payment" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
            <h4 style="margin: 0;" class="word-selection word-selection-start">💰 Pago Único</h4>
            <span id="toggle-icon" style="font-size: 20px; color: #64748b;">▼</span>
        </div>
        
        <div id="one-time-plans-container" class="subscription-plans" style="display: none; margin-top: 20px;">
            <div class="plan-card">
                <div class="plan-duration">🟢 Plan Basico - 1 mes</div>
                 <div class="plan-info">Accede a todas las funciones durante 1 mes.</div>
                <div class="plan-prom">Ideal para probar la aplicación sin compromiso.</div>
                <div class="plan-price">0,01 €</div>
                <?php include '../../dePago/paypal_unico_1_mes.php'; ?>
            </div>
            
            <div class="plan-card recommended">
                <div class="recommended-tag">RECOMENDADO</div>
                <div class="plan-duration">🔵 Plan Ahorro - 6 meses</div>
                <div class="plan-info">Todas las funciones activas durante 6 meses.</div>
                <div class="plan-prom">Más tiempo, mejor precio y sin renovaciones mensuales</div>
                <div class="plan-price">0,02 €</div>
                <?php include '../../dePago/paypal_unico_6_meses.php'; ?>
            </div>
            
            <div class="plan-card">
                <div class="plan-duration">🟣 Plan Pro – 12 meses</div>
                <div class="plan-info">Accede a todas las funciones durante 12 meses.</div>
                <div class="plan-prom">La mejor opción en precio y tranquilidad.</div>
                <div class="plan-price">0,04 €</div>
                <?php include '../../dePago/paypal_unico_1_ano.php'; ?>
            </div>
        </div>
    </div>

    <!-- 6️⃣ Plan de suscripción (PayPal) -->
    <div id="subscription-plans-section" class="info-box" style="margin-top: 24px; border: 1px solid #e2e8f0; background: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">💎 Plan de suscripción</h4>
            <span style="font-size: 14px; color: #64748b;">Plan actual: <strong style="color: #ff8a00;"><?= $account_status ?></strong></span>
        </div>
        
        <div class="subscription-plans">
            <div class="plan-card">
                <div class="plan-duration">🟢 Plan Basico - 1 mes</div>
                 <div class="plan-info">Accede a todas las funciones durante 1 mes.</div>
                <div class="plan-prom">Ideal para probar la aplicación sin compromiso.</div>
                <div class="plan-price">4,99 €</div>
                <?php include '../../dePago/paypal_1_mes.php'; ?>
            </div>
            
            <div class="plan-card recommended">
                <div class="recommended-tag">RECOMENDADO</div>
                <div class="plan-duration">🔵 Plan Ahorro - 6 meses</div>
                <div class="plan-info">Todas las funciones activas durante 6 meses.</div>
                <div class="plan-prom">Más tiempo, mejor precio y sin renovaciones mensuales</div>
                <div class="plan-price">19,99 €</div>
                <?php include '../../dePago/paypal_6_meses.php'; ?>
            </div>
            
            <div class="plan-card">
                <div class="plan-duration">🟣 Plan Pro – 12 meses</div>
                <div class="plan-info">Accede a todas las funciones durante 12 meses.</div>
                <div class="plan-prom">La mejor opción en precio y tranquilidad.</div>
                <div class="plan-price">31,99 €</div>
                <?php include '../../dePago/paypal_1_ano.php'; ?>
            </div>
        </div>
    </div>

    <script>
        // Aseguramos que el evento se asigne correctamente usando addEventListener
        (function() {
            const toggleBtn = document.getElementById('toggle-one-time-payment');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const container = document.getElementById('one-time-plans-container');
                    const icon = document.getElementById('toggle-icon');
                    
                    if (container.style.display === 'none' || container.style.display === '') {
                        container.style.display = 'grid';
                        icon.textContent = '▲';
                        
                        // Forzamos la inicialización de los botones con importes de prueba
                        const initButtons = () => {
                            if (typeof window.initUnicoButton === 'function') {
                                window.initUnicoButton('paypal-button-container-unico-1-mes', '0.01', 'Plan Basico - 1 mes', 'Basico');
                                window.initUnicoButton('paypal-button-container-unico-6-meses', '0.02', 'Plan Ahorro - 6 meses', 'Ahorro');
                                window.initUnicoButton('paypal-button-container-unico-1-ano', '0.04', 'Plan Pro - 12 meses', 'Pro');
                            }
                        };
                        
                        // Ejecutamos con un pequeño delay para asegurar visibilidad
                        setTimeout(initButtons, 300);
                    } else {
                        container.style.display = 'none';
                        icon.textContent = '▼';
                    }
                });
            }
        })();
    </script>

    <div style="margin-top: 40px; display: flex; flex-direction: column; align-items: center; gap: 16px;">
        <button id="btn-show-delete-panel" class="nav-btn" style="color: #64748b; font-size: 13px; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; background: transparent; cursor: pointer;">Eliminar Cuenta</button>
        
        <div id="delete-account-panel" style="display: none; width: 100%; max-width: 400px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 24px; margin-top: 10px; text-align: center;">
            <h4 style="color: #991b1b; margin-top: 0; margin-bottom: 12px;">¿Estás seguro de que quieres eliminar tu cuenta?</h4>
            <p style="color: #b91c1c; font-size: 14px; margin-bottom: 20px;">Esta acción es irreversible. Se borrarán todos tus textos, palabras guardadas y progreso de estudio.</p>
            
            <div style="margin-bottom: 20px;">
                <label for="delete-confirm-text" style="display: block; font-size: 13px; color: #7f1d1d; margin-bottom: 8px; font-weight: 600;">Escribe <span style="background: #fee2e2; padding: 2px 6px; border-radius: 4px; font-family: monospace;">Eliminate</span> para confirmar:</label>
                <input type="text" id="delete-confirm-text" autocomplete="off" placeholder="Escribe aquí..." style="width: 100%; padding: 10px; border: 1px solid #fca5a5; border-radius: 6px; text-align: center; font-size: 15px;">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="btn-cancel-delete" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; font-weight: 600;">Cancelar</button>
                <button id="btn-confirm-delete" disabled style="padding: 10px 20px; border-radius: 8px; border: none; background: #ff8a00; color: white; cursor: not-allowed; font-weight: 600; opacity: 0.5;">Eliminar Permanentemente</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const showPanelBtn = document.getElementById('btn-show-delete-panel');
    const deletePanel = document.getElementById('delete-account-panel');
    const cancelBtn = document.getElementById('btn-cancel-delete');
    const confirmBtn = document.getElementById('btn-confirm-delete');
    const confirmInput = document.getElementById('delete-confirm-text');
    const targetPhrase = "Eliminate";

    if (showPanelBtn) {
        showPanelBtn.addEventListener('click', function() {
            deletePanel.style.display = 'block';
            showPanelBtn.style.display = 'none';
            confirmInput.focus();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            deletePanel.style.display = 'none';
            showPanelBtn.style.display = 'block';
            confirmInput.value = '';
            confirmBtn.disabled = true;
            confirmBtn.style.cursor = 'not-allowed';
            confirmBtn.style.opacity = '0.5';
        });
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (this.value.trim() === targetPhrase) {
                confirmBtn.disabled = false;
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.style.opacity = '1';
            } else {
                confirmBtn.disabled = true;
                confirmBtn.style.cursor = 'not-allowed';
                confirmBtn.style.opacity = '0.5';
            }
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (confirmInput.value.trim() !== targetPhrase) return;
            
            if (!confirm('¿Estás absolutamente seguro? Esta acción no se puede deshacer.')) return;

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Eliminando...';

            fetch('logueo_seguridad/ajax_delete_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Tu cuenta ha sido eliminada correctamente. Esperamos verte pronto.');
                    window.location.href = 'index.php';
                } else {
                    alert('Error: ' + (data.error || 'No se pudo eliminar la cuenta.'));
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Eliminar Permanentemente';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al procesar la solicitud.');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Eliminar Permanentemente';
            });
        });
    }
})();
</script>
<?php
$conn->close();
} catch (Exception $e) {
    echo '<div style="padding: 20px; color: red; background: #fee2e2; border: 1px solid #ff8a00; border-radius: 8px; margin: 20px;">';
    echo '<strong>Error fatal:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
} catch (Error $e) {
    echo '<div style="padding: 20px; color: red; background: #fee2e2; border: 1px solid #ff8a00; border-radius: 8px; margin: 20px;">';
    echo '<strong>Error de PHP:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>
