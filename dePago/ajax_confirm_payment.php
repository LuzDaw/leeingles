<?php
/**
 * Procesa la confirmación de pago de PayPal y actualiza al usuario
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

error_log("ajax_confirm_payment: Recibido orderID=$orderID, status=$status, plan=$plan para usuario $user_id");

// REGLA DE ORO: Solo el estado COMPLETED (Pagos únicos), ACTIVE (Suscripciones) o APPROVED activa el plan inmediatamente.
if ($status === 'COMPLETED' || $status === 'ACTIVE' || $status === 'APPROVED') {
    $result = activateUserPlan($user_id, $plan, $orderID, 'paypal');
    if ($result['success']) {
        $result['orderID'] = $orderID;
    }
    echo json_encode($result);
} else {
    // Si el pago no es exitoso, no hacemos nada más que informar del error
    echo json_encode([
        'success' => false, 
        'message' => "El pago no ha podido ser procesado (Estado: $status). Por favor, inténtalo de nuevo o contacta con soporte."
    ]);
}
