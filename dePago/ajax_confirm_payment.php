<?php
/**
 * Procesa la confirmación de pago de PayPal Sandbox y actualiza al usuario
 * Ubicación: dePago/ajax_confirm_payment.php
 */
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/payment_functions.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

$user_id = $_SESSION['user_id'];
$orderID = $_POST['orderID'] ?? '';
$status = strtoupper($_POST['status'] ?? '');
$plan = $_POST['plan'] ?? 'desconocido';

// REGLA DE ORO: Solo el estado COMPLETED activa el plan inmediatamente.
if ($status === 'COMPLETED') {
    $result = activateUserPlan($user_id, $plan, $orderID, 'paypal');
    if ($result['success']) {
        $result['orderID'] = $orderID;
    }
    echo json_encode($result);
} else {
    // CUALQUIER OTRO ESTADO (PENDING, APPROVED, etc.) -> ESPERANDO PAGO
    $result = registerPendingPayment($user_id, $plan, $orderID, 'paypal');
    if ($result['success']) {
        $result['message'] = "Pago en proceso (Estado: $status). El plan se activará automáticamente cuando se confirme la recepción del dinero.";
        $result['orderID'] = $orderID;
        $result['status'] = 'pending';
    }
    echo json_encode($result);
}
