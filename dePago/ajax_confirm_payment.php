<?php
/**
 * Procesa la confirmación de pago de PayPal Sandbox y actualiza al usuario
 * Ubicación: dePago/ajax_confirm_payment.php
 */
require_once __DIR__ . '/../db/connection.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

$user_id = $_SESSION['user_id'];
$orderID = $_POST['orderID'] ?? '';
$status = $_POST['status'] ?? '';
$plan = $_POST['plan'] ?? 'desconocido';

// Mapeo de planes a duraciones (meses)
$plan_durations = [
    'Inicio' => 1,
    'Ahorro' => 6,
    'Pro' => 12
];

// Aceptamos COMPLETED/ACTIVE (activación inmediata) o PENDING (espera)
if ($status === 'COMPLETED' || $status === 'ACTIVE') {
    $meses = $plan_durations[$plan] ?? 0;
    if ($meses > 0) {
        $fecha_fin = date('Y-m-d H:i:s', strtotime("+$meses months"));
        
        // 1. Registrar la suscripción como activa (con fallback por si no existe la columna payment_method)
        $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status, payment_method) VALUES (?, ?, ?, ?, 'active', 'paypal')");
        if ($stmt_sub) {
            $stmt_sub->bind_param("isss", $user_id, $plan, $fecha_fin, $orderID);
            $stmt_sub->execute();
        } else {
            $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt_sub->bind_param("isss", $user_id, $plan, $fecha_fin, $orderID);
            $stmt_sub->execute();
        }
        
        // 2. Actualizar el tipo de usuario en la tabla users
        $stmt = $conn->prepare("UPDATE users SET tipo_usuario = ? WHERE id = ?");
        $stmt->bind_param("si", $plan, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "Plan $plan activado hasta $fecha_fin", 'orderID' => $orderID]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Plan no reconocido: ' . $plan]);
    }
} elseif ($status === 'PENDING' || $status === 'pending') {
    // PAGO EN PAUSA: Registramos pero NO activamos el tipo de usuario
    $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status, payment_method) VALUES (?, ?, NOW(), ?, 'pending', 'paypal')");
    
    if (!$stmt_sub) {
        $stmt_sub = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status) VALUES (?, ?, NOW(), ?, 'pending')");
    }

    $stmt_sub->bind_param("iss", $user_id, $plan, $orderID);
    
    if ($stmt_sub->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Pago recibido pero pendiente de confirmación (eCheck/Transferencia). El plan se activará cuando se complete el proceso.",
            'orderID' => $orderID,
            'status' => 'pending'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar pago pendiente: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Estado de pago no válido: ' . $status]);
}
