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

// Aceptamos COMPLETED (para pagos únicos) o ACTIVE (para suscripciones)
if ($status === 'COMPLETED' || $status === 'ACTIVE') {
    
    // Actualizar el tipo de usuario a premium
    $stmt = $conn->prepare("UPDATE users SET tipo_usuario = 'premium' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Aquí podrías añadir lógica para guardar la fecha de expiración según el plan
        // Por ahora, simplemente activamos el estado premium
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario actualizado a PREMIUM (Plan: ' . $plan . ')',
            'orderID' => $orderID
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al actualizar la base de datos: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'El estado del pago no es válido para activación: ' . $status
    ]);
}
