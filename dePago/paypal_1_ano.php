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
                    alert('Suscripción de 1 año completada: ' + data.subscriptionID);
                }
            }).render('#' + containerId);
        } else {
            setTimeout(render, 500);
        }
    }
    render();
})();
</script>
