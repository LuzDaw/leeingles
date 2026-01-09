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

// Mapeo de estados para visualización
$status_labels = [
    'EnPrueba' => 'En prueba',
    'limitado' => 'Limitada',
    'Inicio'   => 'Activa (Inicio)',
    'Ahorro'   => 'Activa (Ahorro)',
    'Pro'      => 'Activa (Pro)'
];

$account_status = $status_labels[$status['estado_logico']] ?? 'Desconocido';
$free_month_start = date('d/m/Y', strtotime($status['fecha_registro']));
$free_month_end = date('d/m/Y', strtotime($status['fin_mes_gratuito']));

// Traducciones semanales
$usage = getWeeklyUsage($user_id);
$available_translations = max(0, 300 - $usage);
$next_activation_date = date('d/m/Y', strtotime($status['proximo_reinicio_semanal']));

// Plan activo
$active_plan = 'Ninguno';
if (in_array($status['estado_logico'], ['Inicio', 'Ahorro', 'Pro'])) {
    $active_plan = $status['estado_logico'];
}

$reading_time = '0h 0m'; // Pendiente de implementar lógica real de tiempo
$practice_time = '0h 0m'; // Pendiente de implementar lógica real de tiempo

$conn->close();
?>

<div class="tab-content-wrapper">
    <!-- 1️⃣ Encabezado – Identidad del usuario -->
    <div class="account-header">
        <div>
            <h2 style="margin: 0; font-size: 24px; color: #1e293b;">Hola, <?= htmlspecialchars($user_data['username']) ?></h2>
            <p style="margin: 4px 0 0 0; color: #64748b;"><?= htmlspecialchars($user_data['email']) ?></p>
        </div>
        <div class="account-badge <?= ($status['es_premium'] || $status['estado_logico'] === 'EnPrueba') ? 'badge-trial' : 'badge-limited' ?>" style="<?= ($status['es_premium']) ? 'background: #e8f5e9; color: #2e7d32;' : '' ?>">
            <?= $account_status ?>
        </div>
    </div>

    <div class="account-grid">
        <!-- 2️⃣ Información de la cuenta -->
        <div class="info-box">
            <h4>📅 Información de la cuenta</h4>
            <div class="info-item">
                <span class="info-label">Tipo de usuario:</span>
                <span class="info-value"><?= $account_status ?></span>
            </div>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <div class="info-item">
                    <span class="info-label">Periodo de prueba:</span>
                    <span class="info-value" style="color: <?= $status['es_periodo_gratuito'] ? '#059669' : '#ef4444' ?>;">
                        <?= $status['es_periodo_gratuito'] ? 'Activo' : 'Finalizado' ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Inicio:</span>
                    <span class="info-value"><?= $free_month_start ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fin:</span>
                    <span class="info-value"><?= $free_month_end ?></span>
                </div>
            </div>
        </div>

        <!-- 3️⃣ Traducciones -->
        <div class="info-box">
            <h4>🌐 Traducciones</h4>
            <div class="translations-counter">
                <span class="counter-number"><?= $available_translations ?></span>
                <span class="info-label">disponibles</span>
            </div>
            
            <?php if ($available_translations <= 0): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; font-size: 13px; text-align: center; margin-bottom: 15px;">
                    ⚠️ No tienes traducciones disponibles
                </div>
            <?php endif; ?>

            <div style="font-size: 13px; color: #64748b; line-height: 1.5;">
                <div class="info-item" style="margin-bottom: 4px;">
                    <span>Próxima activación:</span>
                    <span class="info-value"><?= $next_activation_date ?></span>
                </div>
                <p style="margin: 0; font-style: italic;">* Activación automática de 300 traducciones.</p>
            </div>
        </div>
    </div>

    <!-- 4️⃣ Plan de suscripción -->
    <div class="info-box" style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">💎 Plan de suscripción</h4>
            <span style="font-size: 14px; color: #64748b;">Plan actual: <strong style="color: #ff8a00;"><?= $active_plan ?></strong></span>
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

    <!-- 5️⃣ Actividad del usuario -->
    <div class="info-box">
        <h4>📊 Actividad del usuario</h4>
        <div class="activity-stats">
            <div class="activity-card">
                <div class="activity-icon">📄</div>
                <span class="activity-number"><?= $uploaded_texts ?></span>
                <span class="activity-label">Textos subidos</span>
            </div>
            <div class="activity-card">
                <div class="activity-icon">📖</div>
                <span class="activity-number"><?= $reading_time ?></span>
                <span class="activity-label">Tiempo lectura</span>
            </div>
            <div class="activity-card">
                <div class="activity-icon">🎯</div>
                <span class="activity-number"><?= $practice_time ?></span>
                <span class="activity-label">Tiempo práctica</span>
            </div>
        </div>
    </div>

    <!-- Botones de acción secundarios -->
    <div style="margin-top: 40px; display: flex; gap: 16px; justify-content: center;">
        <button class="nav-btn" style="color: #64748b; font-size: 13px;">Eliminar Cuenta</button>
    </div>
</div>
