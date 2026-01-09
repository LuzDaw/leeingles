<?php
session_start();
require_once '../../db/connection.php';
require_once '../../includes/content_functions.php';
require_once '../../dePago/subscription_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;">Debes iniciar sesión para ver tu cuenta.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario (Placeholder para funciones futuras)
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Obtener datos reales de suscripción
$status = getUserSubscriptionStatus($user_id);
$limit_info = checkTranslationLimit($user_id);

// Obtener datos reales de actividad
$uploaded_texts = getTotalUserTexts($user_id);

// Tiempo de lectura real
$stmt = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM reading_time WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total_reading_seconds = $row['total_seconds'] ?? 0;
$reading_h = floor($total_reading_seconds / 3600);
$reading_m = floor(($total_reading_seconds % 3600) / 60);
$reading_time = "{$reading_h}h {$reading_m}m";
$stmt->close();

// Tiempo de práctica real
$stmt = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM practice_time WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total_practice_seconds = $row['total_seconds'] ?? 0;
$practice_h = floor($total_practice_seconds / 3600);
$practice_m = floor(($total_practice_seconds % 3600) / 60);
$practice_time = "{$practice_h}h {$practice_m}m";
$stmt->close();

// Mapeo de estados para visualización
$status_labels = [
    'EnPrueba' => 'Periodo de Prueba - 30 días ',
    'limitado' => 'Plan Gratuito - 300 Traduciones/Semanal',
    'Inicio'   => 'Plan Inicio - 30 días',
    'Ahorro'   => 'Plan Ahorro - 6 meses',
    'Pro'      => 'Plan Pro 12 meses'
];

$account_status = $status_labels[$status['estado_logico']] ?? 'Desconocido';
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
    $stmt = $conn->prepare("SELECT fecha_inicio, fecha_fin FROM user_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY fecha_fin DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($sub = $res->fetch_assoc()) {
        $plan_start_date = date('d/m/Y', strtotime($sub['fecha_inicio']));
        $plan_end_date = date('d/m/Y', strtotime($sub['fecha_fin']));
        $now = new DateTime();
        $end = new DateTime($sub['fecha_fin']);
        $diff = $now->diff($end);
        $days = $diff->invert ? 0 : $diff->days;
        $days_remaining_text = " · $days días restantes";
    }
    $stmt->close();
}

// Mapeo de estado simplificado para la tabla
$simple_status = 'limitado';
if ($status['estado_logico'] === 'EnPrueba') {
    $simple_status = 'prueba';
} elseif ($status['es_premium']) {
    $simple_status = 'premium';
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
    'caracteristica' => 'Plan Gratuito (300 traducciones/semana)',
    'inicio' => $free_month_start,
    'fin' => $next_reset_date,
    'clase' => ($has_premium || $is_trial_active) ? 'row-faint' : ''
];

// 3. Fila de Plan de Prueba (Solo si está activo)
if ($is_trial_active) {
    $plan_table_rows[] = [
        'activo' => '✅',
        'caracteristica' => 'Periodo de Prueba (Funciones completas)',
        'inicio' => $free_month_start,
        'fin' => $free_month_end,
        'clase' => ''
    ];
}

// Traducciones semanales (para info secundaria)
$usage = getWeeklyUsage($user_id);
$available_translations = max(0, 300 - $usage);

$conn->close();
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

    /* Estilos para la nueva tabla de usuario */
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
</style>

<div class="tab-content-wrapper account-dashboard">
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
        <h3 class="section-title">Tu actividad</h3>
        <div class="horizontal-row">
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

    <!-- 5️⃣ Plan de suscripción (PayPal) - Mantenido como estaba -->
    <div id="subscription-plans-section" class="info-box" style="margin-top: 64px; border: 1px solid #e2e8f0; background: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">💎 Plan de suscripción</h4>
            <span style="font-size: 14px; color: #64748b;">Plan actual: <strong style="color: #ff8a00;"><?= $account_status ?></strong></span>
        </div>
        
        <div class="subscription-plans">
            <div class="plan-card">
                <div class="plan-duration">🟢 Plan Inicio - 1 mes</div>
                 <div class="plan-info">Accede a todas las funciones durante   1 mes.</div>
                <div class="plan-prom">Ideal para probar la aplicación sin compromiso.</div>
                <div class="plan-price">4,99 €</div>
                <?php include '../../dePago/paypal_1_mes.php'; ?>
            </div>
            
            <div class="plan-card recommended">
                <div class="recommended-tag">RECOMENDADO</div>
                <div class="plan-duration">🔵 Plan Ahorro -  6 meses</div>
                <div class="plan-info">Todas las funciones activas durante  6 meses.</div>
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

    <!-- Botones de acción secundarios -->
    <div style="margin-top: 40px; display: flex; gap: 16px; justify-content: center;">
        <button class="nav-btn" style="color: #64748b; font-size: 13px;">Eliminar Cuenta</button>
    </div>
</div>
