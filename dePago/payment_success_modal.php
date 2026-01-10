<?php
/**
 * Modal de éxito de pago
 * Se incluye en la pestaña de cuenta cuando se detecta un pago exitoso.
 */
if (!isset($status)) {
    // Si se accede directamente o no están las variables, no mostrar nada
    return;
}

$plan_name = $status['tipo_base'];
$fecha_inicio = $plan_start_date ?? '-';
$fecha_renovacion = $plan_end_date ?? '-';
?>

<div id="payment-success-modal" class="payment-modal-overlay">
    <div class="payment-modal-content">
        <div class="success-icon-container">
            <div class="success-icon">✓</div>
        </div>
        <h2>¡Suscripción Exitosa!</h2>
        <p class="success-msg">Tu cuenta ha sido actualizada correctamente.</p>
        
        <div class="plan-details-box">
            <div class="detail-item">
                <span class="detail-label">Plan Activado:</span>
                <span class="detail-value"><?php echo htmlspecialchars($plan_name); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Fecha de Inicio:</span>
                <span class="detail-value"><?php echo $fecha_inicio; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Próxima Renovación:</span>
                <span class="detail-value" style="color: #166534; font-weight: bold;"><?php echo $fecha_renovacion; ?></span>
            </div>
        </div>
        
        <p class="auto-close-text">Este mensaje se cerrará en unos segundos...</p>
        
        <button onclick="closePaymentModal()" class="close-modal-btn">Entendido</button>
    </div>
</div>

<style>
    .payment-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease-out;
    }
    
    .payment-modal-content {
        background: white;
        padding: 40px;
        border-radius: 20px;
        max-width: 450px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(0);
        animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .success-icon-container {
        width: 80px;
        height: 80px;
        background: #f0fdf4;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        border: 4px solid #bbf7d0;
    }
    
    .success-icon {
        color: #166534;
        font-size: 40px;
        font-weight: bold;
    }
    
    .payment-modal-content h2 {
        color: #0f172a;
        margin-bottom: 10px;
        font-size: 24px;
    }
    
    .success-msg {
        color: #64748b;
        margin-bottom: 25px;
    }
    
    .plan-details-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        text-align: left;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        color: #64748b;
        font-size: 14px;
    }
    
    .detail-value {
        color: #1e293b;
        font-weight: 600;
        font-size: 14px;
    }
    
    .auto-close-text {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 15px;
    }
    
    .close-modal-btn {
        background: #1e293b;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        width: 100%;
    }
    
    .close-modal-btn:hover {
        background: #334155;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<script>
    // Definir la función en el ámbito global para que onclick pueda encontrarla
    window.closePaymentModal = function() {
        const modal = document.getElementById('payment-success-modal');
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                modal.remove();
                // Limpiar la URL para que no vuelva a aparecer al recargar
                const url = new URL(window.location);
                url.searchParams.delete('payment_success');
                window.history.replaceState({}, '', url);
            }, 500);
        }
    }

    // Auto-cerrar después de 6 segundos
    setTimeout(closePaymentModal, 6000);
</script>
