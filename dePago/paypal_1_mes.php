<div id="paypal-button-container-P-3WX572712E0639547NFPJE2Y"></div>
<script>
(function() {
    var containerId = 'paypal-button-container-P-3WX572712E0639547NFPJE2Y';
    var planId = 'P-3WX572712E0639547NFPJE2Y';
    var clientId = 'AeBnuVj2_5qgtbjWiE0XzHZPMkwG1DcVdm647HJdRhQqv1QkN2hRM-2KcwlMFTPsZ1KmRgXW7lpeiJBz';

    function render() {
        var container = document.getElementById(containerId);
        if (window.paypal && container) {
            container.innerHTML = '';
            window.paypal.Buttons({
                style: {
                    shape: 'rect',
                    color: 'blue',
                    layout: 'vertical',
                    label: 'subscribe'
                },
                createSubscription: function(data, actions) {
                    return actions.subscription.create({
                        'plan_id': planId
                    });
                },
                onApprove: function(data, actions) {
                    // Obtener el estado real de la suscripción desde PayPal
                    return actions.subscription.get().then(function(details) {
                        var realStatus = details.status; // ACTIVE, PENDING, etc.
                        
                        fetch('dePago/ajax_confirm_payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'orderID=' + data.subscriptionID + '&status=' + realStatus + '&plan=Inicio'
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                if (realStatus === 'ACTIVE') {
                                    alert('¡Plan Inicio activado con éxito!');
                                    window.location.href = 'index.php?payment_success=1';
                                } else {
                                    // Caso de pago pendiente (eCheck / Cargo en cuenta)
                                    window.location.href = 'index.php?payment_pending=1';
                                }
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });
                }
            }).render('#' + containerId);
        } else {
            // Si el SDK no está o es el de otro Client ID, intentamos cargarlo específicamente
            if (!document.getElementById('paypal-sdk-1m')) {
                var script = document.createElement('script');
                script.id = 'paypal-sdk-1m';
                script.src = "https://www.paypal.com/sdk/js?client-id=" + clientId + "&vault=true&intent=subscription";
                document.head.appendChild(script);
            }
            setTimeout(render, 500);
        }
    }
    render();
})();
</script>
