<?php
require_once __DIR__ . '/../../db/connection.php';
session_start();

// AUTO-LOGUEO DE PRUEBA: Si no hay sesión, pillamos un usuario de prueba para que la activación funcione
if (!isset($_SESSION['user_id'])) {
    $res = $conn->query("SELECT id, username FROM users WHERE tipo_usuario = 'limitado' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
    }
}

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'No identificado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Sandbox - Pruebas Combinadas</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .section-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 800px; width: 100%; margin-bottom: 30px; }
        h2 { color: #1a1a1a; margin-bottom: 1rem; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; color: #0056b3; text-align: left; }
        
        #paypal-button-container-original { margin-top: 20px; min-height: 150px; border: 1px dashed #ccc; padding: 10px; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        .status { margin-top: 15px; font-size: 0.9rem; color: #666; padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #0070ba; }
        
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 20px; }
        .plan-card { border: 1px solid #eee; padding: 20px; border-radius: 10px; background: #fafafa; transition: transform 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .plan-card h3 { margin-top: 0; color: #0070ba; }
        .price { font-size: 1.5rem; font-weight: bold; margin: 10px 0; color: #333; }
        .paypal-button-container { min-height: 150px; margin-top: 15px; }

        @media (max-width: 600px) {
            .plans-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="section-container">
    <h2>Diagnóstico de Sesión</h2>
    <div class="info-box" style="background: #fff3cd; color: #856404; border-color: #ffeeba;">
        <strong>Usuario actual:</strong> <?php echo htmlspecialchars($username); ?><br>
        <strong>ID de Usuario:</strong> <?php echo $user_id ? $user_id : '<span style="color:red">No detectado (Inicia sesión primero)</span>'; ?>
    </div>
</div>

<div class="section-container">
    <h2>Prueba de Conexión Sandbox (Original)</h2>
    <div class="info-box">
        <strong>Entorno:</strong> Sandbox (Pruebas Reales)<br>
        <strong>Vendedor:</strong> piknte-facilitator@gmail.com<br>
        <strong>Tipo:</strong> Pago Único (1.00 EUR)
    </div>
    <div id="paypal-button-container-original"></div>
    <div id="status-log" class="status">Esperando al SDK de PayPal...</div>
</div>

<div class="section-container">
    <h2>Nuevos Botones Dinámicos</h2>
    <div class="info-box">
        <strong>Modo:</strong> Pago Único Dinámico (Activación Automática)
    </div>
    <div class="plans-grid">
        <div class="plan-card">
            <h3>Plan Básico</h3>
            <p>Un mes sin límites de uso</p>
            <div class="price">0,01 €</div>
            <div id="paypal-button-container-basico" class="paypal-button-container"></div>
        </div>
        <div class="plan-card">
            <h3>Plan Económico</h3>
            <p>6 meses sin límites de uso</p>
            <div class="price">0,02 €</div>
            <div id="paypal-button-container-economico" class="paypal-button-container"></div>
        </div>

        <!-- Plan Pro -->
        <div class="plan-card">
            <h3>Plan Pro</h3>
            <p>1 Año sin límites de uso</p>
            <div class="price">0,04 €</div>
            <div id="paypal-button-container-pro" class="paypal-button-container"></div>
        </div>
    </div>
</div>

<!-- SDK de PayPal con el Client ID correcto para la cuenta piknte-facilitator@gmail.com -->
<script src="https://www.paypal.com/sdk/js?client-id=AaQy-0aO2EsQkF7YAotIavQcHXwRF96D6ygaBfIDNLzojTuRAhp0dGON4oh9mmpbX_HIcd7zichV_K6F&currency=EUR"></script>

<script>
    const log = (msg) => {
        const el = document.getElementById('status-log');
        if (el) el.innerText = msg;
    };

    /**
     * Notifica al servidor para activar el plan
     */
    function notifyServer(orderID, status, planName) {
        log(`Notificando al servidor: Plan ${planName}, ID ${orderID}...`);
        
        // Aseguramos que el estado sea uno de los que el servidor acepta (COMPLETED, ACTIVE, APPROVED)
        // Si PayPal devuelve algo distinto pero exitoso, lo normalizamos
        let finalStatus = status;
        if (status === 'CREATED' || status === 'SAVED') {
            log("⚠️ Estado no finalizado, esperando COMPLETED...");
            return;
        }

        const params = new URLSearchParams();
        params.append('orderID', orderID);
        params.append('status', finalStatus);
        params.append('plan', planName);

        return fetch('../ajax_confirm_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                log(`✅ ¡Éxito! Plan ${planName} activado hasta ${data.fecha_fin || 'fin de periodo'}.`);
                // Opcional: Redirigir tras éxito
                // window.location.href = 'webhook_handler.php?payment_success=1';
            } else {
                log(`❌ Error del servidor: ${data.message}`);
            }
        })
        .catch(error => {
            log(`❌ Error de red: ${error.message}`);
        });
    }

    function initPayPalButton(containerId, amount, description, planName) {
        if (typeof paypal === 'undefined') return;

        paypal.Buttons({
            style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'pay' },
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: amount, currency_code: 'EUR' },
                        description: description
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    // Usamos el estado principal de la orden (COMPLETED) para evitar el PENDING de la captura en Sandbox
                    let realStatus = details.status;

                    log(`✅ Pago de ${amount}€ capturado (Estado: ${realStatus}).`);
                    
                    // Reutilizamos la lógica de activación del servidor
                    notifyServer(details.id, realStatus, planName);
                });
            },
            onError: function(err) {
                log(`❌ Error de PayPal: ${err}`);
            }
        }).render(containerId);
    }

    function initAll() {
        if (typeof paypal === 'undefined') {
            setTimeout(initAll, 500);
            return;
        }
        log("✅ SDK cargado. Botones listos para activar planes.");

        // Botón Original (1.00€) -> Activa plan 'Basico'
        initPayPalButton('#paypal-button-container-original', '1.00', 'Prueba de Conexión LeeIngles', 'Basico');

        // Plan Básico (0.01€) -> Activa plan 'Basico' (1 mes)
        initPayPalButton('#paypal-button-container-basico', '0.01', 'Plan Básico - 1 mes', 'Basico');

        // Plan Económico (0.02€) -> Activa plan 'Ahorro' (6 meses)
        initPayPalButton('#paypal-button-container-economico', '0.02', 'Plan Económico - 6 meses', 'Ahorro');

        // Plan Pro (0.04€) -> Activa plan 'Pro' (12 meses)
        initPayPalButton('#paypal-button-container-pro', '0.04', 'Plan Pro - 1 año', 'Pro');

        log("💡 Nota: Si el plan no se activa, verifica que el usuario en 'Diagnóstico de Sesión' sea el que quieres mejorar.");
    }

    document.addEventListener('DOMContentLoaded', initAll);
</script>

</body>
</html>
