<div id="paypal-button-container-P-8TM491282S134543TNFPKMPI"></div>
<script>
(function() {
    var containerId = 'paypal-button-container-P-8TM491282S134543TNFPKMPI';
    var planId = 'P-8TM491282S134543TNFPKMPI';

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
                    fetch('dePago/ajax_confirm_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'orderID=' + data.subscriptionID + '&status=ACTIVE&plan=Ahorro'
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            alert('¡Plan Ahorro activado con éxito!');
                            window.location.reload();
                        } else {
                            alert('Error: ' + res.message);
                        }
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
