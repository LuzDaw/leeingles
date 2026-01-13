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
        
        .plans-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
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
        <strong>Modo:</strong> Pago Único Dinámico (0.01€ / 0.02€)
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
    </div>
</div>

<!-- SDK de PayPal con el Client ID correcto para la cuenta piknte-facilitator@gmail.com -->
<script src="https://www.paypal.com/sdk/js?client-id=AaQy-0aO2EsQkF7YAotIavQcHXwRF96D6ygaBfIDNLzojTuRAhp0dGON4oh9mmpbX_HIcd7zichV_K6F&currency=EUR"></script>

<script>
    const log = (msg) => {
        console.log(msg);
        const el = document.getElementById('status-log');
        if (el) el.innerText = msg;
    };

    function initPayPalButton(containerId, amount, description) {
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
                    log(`✅ Pago de ${amount}€ completado.`);
                    window.location.href = 'webhook_handler.php?payment_success=1';
                });
            }
        }).render(containerId);
    }

    function initAll() {
        if (typeof paypal === 'undefined') {
            setTimeout(initAll, 500);
            return;
        }
        log("✅ SDK cargado con Client ID correcto.");

        // Botón Original (1.00€)
        initPayPalButton('#paypal-button-container-original', '1.00', 'Prueba de Conexión LeeIngles');

        // Plan Básico (0.01€)
        initPayPalButton('#paypal-button-container-basico', '0.01', 'Plan Básico - 1 mes');

        // Plan Económico (0.02€)
        initPayPalButton('#paypal-button-container-economico', '0.02', 'Plan Económico - 6 meses');
    }

    document.addEventListener('DOMContentLoaded', initAll);
</script>

</body>
</html>
