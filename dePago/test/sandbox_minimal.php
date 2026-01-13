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
        
        /* Estilos para el botón original */
        #paypal-button-container { margin-top: 20px; min-height: 150px; border: 1px dashed #ccc; padding: 10px; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        .status { margin-top: 15px; font-size: 0.9rem; color: #666; padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #0070ba; }
        
        /* Estilos para los nuevos botones dinámicos */
        .plans-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .plan-card { border: 1px solid #eee; padding: 20px; border-radius: 10px; background: #fafafa; transition: transform 0.2s; display: flex; flex-direction: column; justify-content: space-between; }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .plan-card h3 { margin-top: 0; color: #0070ba; }
        .price { font-size: 1.5rem; font-weight: bold; margin: 10px 0; color: #333; }
        .paypal-hosted-container { min-height: 150px; margin-top: 15px; }

        @media (max-width: 600px) {
            .plans-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- SECCIÓN 1: Botón Original de Suscripción/Prueba -->
<div class="section-container">
    <h2>Prueba de Conexión Sandbox (Original)</h2>
    <div class="info-box">
        <strong>Entorno:</strong> Sandbox (Pruebas Reales)<br>
        <strong>Vendedor:</strong> piknte-facilitator@gmail.com<br>
        <strong>Tipo:</strong> Pago Único (1.00 EUR)
    </div>
    <div id="paypal-button-container">Cargando botón original...</div>
    <div id="status-log" class="status">Esperando al SDK de PayPal...</div>
</div>

<!-- SECCIÓN 2: Nuevos Botones de Pago Único (Hosted) -->
<div class="section-container">
    <h2>Nuevos Botones Dinámicos</h2>
    <div class="info-box">
        <strong>Modo:</strong> Hosted Buttons (Dinámicos)
    </div>
    <div class="plans-grid">
        <div class="plan-card">
            <h3>Plan Básico</h3>
            <p>Un mes sin límites de uso</p>
            <div class="price">0,01 €</div>
            <div id="paypal-container-Y6NGKDFQVVUEA" class="paypal-hosted-container"></div>
        </div>
        <div class="plan-card">
            <h3>Plan Económico</h3>
            <p>6 meses sin límites de uso</p>
            <div class="price">0,02 €</div>
            <div id="paypal-container-LFCL4LYBX79SN" class="paypal-hosted-container"></div>
        </div>
    </div>
</div>

<!-- Cargamos un ÚNICO SDK que incluya ambos componentes -->
<!-- Usamos el Client ID de los Hosted Buttons ya que es el más específico -->
<script src="https://www.paypal.com/sdk/js?client-id=BAAxZqcUKpTsEA9ernmGgUSqshsEaCYG2jdRS65PfQGzsCpxUoOXaD2-4iz9om9zpGpF0hL0DGT0sNpdws&components=buttons,hosted-buttons&currency=EUR"></script>

<script>
    const log = (msg) => {
        console.log(msg);
        const el = document.getElementById('status-log');
        if (el) el.innerText = msg;
    };

    function initAllButtons() {
        if (typeof paypal === 'undefined') {
            setTimeout(initAllButtons, 500);
            return;
        }

        // 1. Renderizar Botón Original (Standard)
        if (paypal.Buttons) {
            log("✅ Renderizando botón original...");
            paypal.Buttons({
                style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'pay' },
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: { value: '1.00', currency_code: 'EUR' },
                            description: 'Prueba de Conexión LeeIngles'
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        log("✅ Pago original procesado.");
                        window.location.href = 'webhook_handler.php?payment_success=1';
                    });
                }
            }).render('#paypal-button-container');
        }

        // 2. Renderizar Botones Hosted
        if (paypal.HostedButtons) {
            paypal.HostedButtons({
                hostedButtonId: "Y6NGKDFQVVUEA",
            }).render("#paypal-container-Y6NGKDFQVVUEA");

            paypal.HostedButtons({
                hostedButtonId: "LFCL4LYBX79SN",
            }).render("#paypal-container-LFCL4LYBX79SN");
            
            console.log("✅ Botones Hosted renderizados");
        }
    }

    document.addEventListener('DOMContentLoaded', initAllButtons);
</script>

</body>
</html>
