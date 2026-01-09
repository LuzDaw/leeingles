<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Sandbox - Prueba de Conexión Rápida</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0f2f5; padding: 20px; }
        .test-box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; }
        h2 { color: #1a1a1a; margin-bottom: 1rem; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; color: #0056b3; text-align: left; }
        #paypal-button-container { margin-top: 20px; min-height: 150px; border: 1px dashed #ccc; padding: 10px; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        .status { margin-top: 15px; font-size: 0.9rem; color: #666; padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #0070ba; }
        .instructions { text-align: left; font-size: 0.8rem; color: #555; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .success-banner { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: bold; }
    </style>
</head>
<body>

<div class="test-box">
    <?php if (isset($_GET['success'])): ?>
        <div class="success-banner">
            ✅ ¡Pago completado con éxito!<br>
            <span style="font-size: 0.8rem; font-weight: normal;">ID Transacción: <?php echo htmlspecialchars($_GET['id']); ?></span>
        </div>
    <?php endif; ?>

    <h2>Prueba de Conexión Sandbox</h2>
    
    <div class="info-box">
        <strong>Entorno:</strong> Sandbox (Pruebas)<br>
        <strong>Vendedor:</strong> piknte-facilitator@gmail.com<br>
        <strong>Tipo:</strong> Pago Único (1.00 EUR)
    </div>

    <p>Este botón no necesita IDs de plan. Sirve para confirmar que tu Client ID y tu cuenta de vendedor funcionan correctamente.</p>

    <div id="paypal-button-container">Cargando botón...</div>
    <div id="status-log" class="status">Esperando al SDK de PayPal...</div>

    <div class="instructions">
        <strong>Instrucciones:</strong>
        <ul>
            <li>Usa la cuenta de comprador: <code>piknte-buyer@gmail.com</code></li>
            <li>Si el pago se completa, la página se recargará automáticamente.</li>
        </ul>
    </div>
</div>

<!-- SDK de PayPal con tu Client ID de Sandbox -->
<script src="https://www.paypal.com/sdk/js?client-id=AaQy-0aO2EsQkF7YAotIavQcHXwRF96D6ygaBfIDNLzojTuRAhp0dGON4oh9mmpbX_HIcd7zichV_K6F&currency=EUR"></script>

<script>
    const log = (msg) => {
        console.log(msg);
        document.getElementById('status-log').innerText = msg;
    };

    function initPayPal() {
        if (typeof paypal === 'undefined') {
            log("❌ Error: El SDK de PayPal no se cargó. Reintentando...");
            setTimeout(initPayPal, 1000);
            return;
        }

        log("✅ SDK cargado. Renderizando botón de pago único...");
        document.getElementById('paypal-button-container').innerHTML = '';

        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'gold',
                layout: 'vertical',
                label: 'pay'
            },
            createOrder: function(data, actions) {
                log("Creando orden de 1.00 EUR...");
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '1.00',
                            currency_code: 'EUR'
                        },
                        description: 'Prueba de Conexión LeeIngles'
                    }]
                });
            },
            onApprove: function(data, actions) {
                log("Pago aprobado. Capturando transacción...");
                return actions.order.capture().then(function(details) {
                    log("✅ Transacción completada: " + details.id);
                    
                    // Notificar al servidor y redirigir
                    fetch('ajax_confirm_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'orderID=' + details.id + '&status=COMPLETED&plan=test_pago_unico'
                    })
                    .then(() => {
                        // Redirigir a la misma página con un parámetro de éxito
                        window.location.href = 'sandbox_minimal.php?success=1&id=' + details.id;
                    })
                    .catch(() => {
                        window.location.href = 'sandbox_minimal.php?success=1&id=' + details.id;
                    });
                });
            },
            onError: function(err) {
                log("❌ Error de PayPal: " + err);
                console.error(err);
            }
        }).render('#paypal-button-container');
    }

    // Iniciar
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initPayPal();
    } else {
        document.addEventListener('DOMContentLoaded', initPayPal);
    }
</script>

</body>
</html>
