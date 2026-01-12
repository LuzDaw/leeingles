<?php
/**
 * Archivo de prueba para pagos con PayPal Sandbox
 * Ubicación: dePago/paypal_sandbox_test.php
 */
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../subscription_functions.php';

session_start();

// Obtener el usuario logueado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Si no hay sesión, intentamos pillar el primero para no romper la prueba
    $res = $conn->query("SELECT id, username FROM users LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $user_id = $row['id'];
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $row['username'];
    }
}

$status = $user_id ? getUserSubscriptionStatus($user_id) : null;

// Usamos el Client ID que ya tienes en paypal.html
$clientId = "ATfzdeOVWZvM17U3geOdl_yV513zZfX7oCm_wa0wqog2acHfSIz846MkdZnpu7oCdWFzqdMn0NEN0xSM";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba PayPal Real - LeeIngles</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 400px; width: 100%; text-align: center; }
        .user-box { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbdefb; }
        .status { font-weight: bold; color: #1976d2; }
        #paypal-button-container { margin-top: 20px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Finalizar Suscripción</h2>
    
    <div class="user-box">
        Usuario: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Invitado'); ?></strong><br>
        Estado: <span class="status"><?php echo strtoupper($status['estado_logico'] ?? 'desconocido'); ?></span>
    </div>

    <p>Estás a un paso de ser <strong>PREMIUM</strong>.</p>

    <div id="paypal-button-container"></div>
</div>

<!-- SDK de PayPal con la configuración exacta de tu archivo paypal.html -->
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo $clientId; ?>&currency=EUR" data-sdk-integration-source="button-factory"></script>

<script>
    paypal.Buttons({
        style: {
            shape: 'rect',
            color: 'blue',
            layout: 'vertical',
            label: 'pay'
        },
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '10.00',
                        currency_code: 'EUR'
                    },
                    description: 'Suscripción Premium LeeIngles'
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Enviamos la confirmación al servidor
                fetch('../ajax_confirm_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'orderID=' + data.orderID + '&status=' + details.status
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('¡Pago completado con éxito! Ahora eres PREMIUM.');
                        window.location.href = 'webhook_handler.php?payment_success=1';
                    } else {
                        alert('Error al actualizar: ' + res.message);
                    }
                });
            });
        },
        onError: function(err) {
            console.error('Error PayPal:', err);
            alert('Hubo un error con el pago. Por favor, inténtalo de nuevo.');
        }
    }).render('#paypal-button-container');
</script>

</body>
</html>
