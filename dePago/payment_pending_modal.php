<?php
/**
 * Modal de pago pendiente (eCheck / Cargo en cuenta)
 * Se incluye en la pestaña de cuenta cuando se detecta un pago pendiente.
 */
if (!isset($status)) {
    return;
}

// Intentar obtener el plan pendiente de la base de datos si no viene en la URL
$pending_plan = "Plan Seleccionado";
$stmt_p = $conn->prepare("SELECT plan_name FROM user_subscriptions WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$res_p = $stmt_p->get_result();
if ($row_p = $res_p->fetch_assoc()) {
    $pending_plan = $row_p['plan_name'];
}
?>

<div id="payment-pending-modal" class="payment-modal-overlay">
    <div class="payment-modal-content">
        <div class="pending-icon-container">
            <div class="pending-icon">⏳</div>
        </div>
        <h2>Pago en Proceso</h2>
        <p class="success-msg">Hemos detectado tu pago por <strong>cargo en cuenta</strong>.</p>
        
        <div class="plan-details-box" style="border-left: 4px solid #f59e0b;">
            <div class="detail-item">
                <span class="detail-label">Plan Adquirido:</span>
                <span class="detail-value"><?php echo htmlspecialchars($pending_plan); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Estado:</span>
                <span class="detail-value" style="color: #d97706;">Esperando confirmación</span>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-top: 10px; line-height: 1.4;">
                PayPal nos informa que los fondos tardarán unos días en llegar. Tu suscripción se activará automáticamente en cuanto recibamos la confirmación bancaria.
            </p>
        </div>
        
        <p class="auto-close-text">Este mensaje se cerrará en unos segundos...</p>
        
        <button onclick="closePendingModal()" class="close-modal-btn" style="background: #f59e0b;">Entendido</button>
    </div>
</div>

<style>
    .pending-icon-container {
        width: 80px;
        height: 80px;
        background: #fffbeb;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        border: 4px solid #fef3c7;
    }
    
    .pending-icon {
        font-size: 40px;
    }
</style>

<script>
    window.closePendingModal = function() {
        const modal = document.getElementById('payment-pending-modal');
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                modal.remove();
                const url = new URL(window.location);
                url.searchParams.delete('payment_pending');
                window.history.replaceState({}, '', url);
            }, 500);
        }
    }

    // Auto-cerrar después de 8 segundos (un poco más que el de éxito para que de tiempo a leer)
    setTimeout(closePendingModal, 8000);
</script>
