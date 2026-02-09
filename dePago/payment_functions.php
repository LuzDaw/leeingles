<?php
/**
 * Funciones centrales para la gestión de pagos y activaciones de planes
 * Ubicación: dePago/payment_functions.php
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

/**
 * Activa un plan para un usuario, actualizando tanto su suscripción como su rango en la tabla users.
 *
 * Esta función gestiona la lógica de activación de planes de suscripción.
 * Si el usuario ya tiene un plan activo, el nuevo tiempo se suma al final del plan actual.
 * Registra la suscripción en `user_subscriptions` y actualiza el `tipo_usuario` en la tabla `users`.
 *
 * @param int $user_id ID del usuario.
 * @param string $plan Nombre del plan (ej. 'Basico', 'Ahorro', 'Pro').
 * @param string $paypal_id ID de la transacción o suscripción de PayPal.
 * @param string $method Método de pago (ej. 'paypal', 'transferencia'). Por defecto 'paypal'.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error',
 *               además de 'plan' y 'fecha_fin' si la operación fue exitosa.
 */
function activateUserPlan($user_id, $plan, $paypal_id, $method = 'paypal') {
    global $conn;
    if (!$conn) require __DIR__ . '/../db/connection.php';

    $plan_durations = [
        'Basico' => 1,
        'Ahorro' => 6,
        'Pro' => 12
    ];

    $meses = $plan_durations[$plan] ?? 0;
    if ($meses <= 0) {
        return ['success' => false, 'message' => 'Plan no válido: ' . $plan];
    }

    // Lógica de acumulación: Si ya tiene un plan activo, sumar el tiempo al final del actual
    $base_time = time();
    $stmt_current = $conn->prepare("SELECT fecha_fin FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND fecha_fin > NOW() ORDER BY fecha_fin DESC LIMIT 1");
    $stmt_current->bind_param("i", $user_id);
    $stmt_current->execute();
    $res_current = $stmt_current->get_result();
    if ($row_current = $res_current->fetch_assoc()) {
        $base_time = strtotime($row_current['fecha_fin']);
    }
    $stmt_current->close();

    $fecha_fin = date('Y-m-d H:i:s', strtotime("+$meses months", $base_time));

    // 1. Verificar si ya existe una suscripción pendiente con ese paypal_id
    $stmt_check = $conn->prepare("SELECT id FROM user_subscriptions WHERE paypal_subscription_id = ? AND user_id = ?");
    $stmt_check->bind_param("si", $paypal_id, $user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    $success_sub = false;
    if ($row = $res_check->fetch_assoc()) {
        // Actualizar suscripción existente
        $sub_id = $row['id'];
        $stmt_upd = $conn->prepare("UPDATE user_subscriptions SET status = 'active', fecha_inicio = NOW(), fecha_fin = ?, payment_method = ? WHERE id = ?");
        $stmt_upd->bind_param("ssi", $fecha_fin, $method, $sub_id);
        $success_sub = $stmt_upd->execute();
    } else {
        // Crear nueva suscripción activa
        $stmt_ins = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status, payment_method, fecha_inicio) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
        $stmt_ins->bind_param("issss", $user_id, $plan, $fecha_fin, $paypal_id, $method);
        $success_sub = $stmt_ins->execute();
    }

    if (!$success_sub) {
        return ['success' => false, 'message' => 'Error al registrar la suscripción en la db.'];
    }

    // 2. Actualizar el tipo de usuario en la tabla users
    $stmt_user = $conn->prepare("UPDATE users SET tipo_usuario = ? WHERE id = ?");
    $stmt_user->bind_param("si", $plan, $user_id);
    
    if ($stmt_user->execute()) {
        return [
            'success' => true, 
            'message' => "Plan $plan activado con éxito para el usuario $user_id",
            'plan' => $plan,
            'fecha_fin' => $fecha_fin
        ];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar rango de usuario: ' . $conn->error];
    }
}

/**
 * Procesa un recurso de webhook de PayPal (Sale o Capture).
 *
 * Esta función extrae el ID de PayPal del recurso del webhook y, si el pago
 * está completado, busca la suscripción asociada para activar el plan del usuario.
 *
 * @param array $resource El array de datos del recurso del webhook de PayPal.
 * @param bool $is_completed Indica si el pago asociado al webhook está completado. Por defecto es true.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error',
 *               o el resultado de `activateUserPlan` si el pago está completado.
 */
function handlePaypalWebhookResource($resource, $is_completed = true) {
    global $conn;
    
    $paypal_id = $resource['id'] ?? $resource['parent_payment'] ?? $resource['billing_agreement_id'] ?? '';
    if (empty($paypal_id) && isset($resource['purchase_units'][0]['payments']['captures'][0]['id'])) {
        $paypal_id = $resource['purchase_units'][0]['payments']['captures'][0]['id'];
    }
    
    if (empty($paypal_id)) return ['success' => false, 'message' => 'No se encontró ID de PayPal'];

    if ($is_completed) {
        // Buscar la suscripción para saber el user_id y el plan
        $stmt = $conn->prepare("SELECT user_id, plan_name FROM user_subscriptions WHERE paypal_subscription_id = ? LIMIT 1");
        $stmt->bind_param("s", $paypal_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            return activateUserPlan($row['user_id'], $row['plan_name'], $paypal_id, 'paypal');
        } else {
            return ['success' => false, 'message' => 'No se encontró registro previo para este ID: ' . $paypal_id];
        }
    }
    return ['success' => false, 'message' => 'Pago no completado'];
}
