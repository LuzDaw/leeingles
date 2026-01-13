<div id="paypal-button-container-P-77C5644585713392LNFPKT6Q"></div>
<script>
(function() {
    var containerId = 'paypal-button-container-P-77C5644585713392LNFPKT6Q';
    var planId = 'P-77C5644585713392LNFPKT6Q';

    function render() {
        var container = document.getElementById(containerId);
        if (window.paypal && container) {
            container.innerHTML = '';
            window.paypal.Buttons({
                style: {
                    shape: 'pill',
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
                            body: 'orderID=' + data.subscriptionID + '&status=' + realStatus + '&plan=Pro'
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                if (realStatus === 'ACTIVE') {
                                    window.location.href = 'index.php?tab=account&payment_success=1';
                                } else {
                                    // Caso de pago pendiente (eCheck / Cargo en cuenta)
                                    window.location.href = 'index.php?tab=account&payment_pending=1';
                                }
                            } else {
                                alert('Error: ' + res.message);
                            }
                        });
                    });
                }
            }).render('#' + containerId);
        } else {
            setTimeout(render, 500);
        }
    }
    render();
})();
</script>
