<?php
/**
 * Funciones centrales para la gestión de pagos y activaciones de planes
 * Ubicación: dePago/payment_functions.php
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

if (!function_exists('activateUserPlan')) {
    /**
     * Activa un plan para un usuario, actualizando tanto su suscripción como su rango en la tabla users.
     * 
     * @param int $user_id ID del usuario
     * @param string $plan Nombre del plan (Inicio, Ahorro, Pro)
     * @param string $paypal_id ID de la transacción o suscripción de PayPal
     * @param string $method Método de pago (paypal, transferencia, etc.)
     * @return array Resultado de la operación
     */
    function activateUserPlan($user_id, $plan, $paypal_id, $method = 'paypal') {
        global $conn;
        if (!$conn) require __DIR__ . '/../db/connection.php';

        $plan_durations = [
            'Inicio' => 1,
            'Ahorro' => 6,
            'Pro' => 12
        ];

        $meses = $plan_durations[$plan] ?? 0;
        if ($meses <= 0) {
            return ['success' => false, 'message' => 'Plan no válido: ' . $plan];
        }

        $fecha_fin = date('Y-m-d H:i:s', strtotime("+$meses months"));

        // 1. Verificar si ya existe una suscripción pendiente con ese paypal_id
        $stmt_check = $conn->prepare("SELECT id FROM user_subscriptions WHERE paypal_subscription_id = ? AND user_id = ?");
        $stmt_check->bind_param("si", $paypal_id, $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($row = $res_check->fetch_assoc()) {
            // Actualizar suscripción existente
            $sub_id = $row['id'];
            $stmt_upd = $conn->prepare("UPDATE user_subscriptions SET status = 'active', fecha_inicio = NOW(), fecha_fin = ?, payment_method = ? WHERE id = ?");
            $stmt_upd->bind_param("ssi", $fecha_fin, $method, $sub_id);
            $stmt_upd->execute();
        } else {
            // Crear nueva suscripción activa
            $stmt_ins = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status, payment_method, fecha_inicio) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
            $stmt_ins->bind_param("issss", $user_id, $plan, $fecha_fin, $paypal_id, $method);
            $stmt_ins->execute();
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
}

if (!function_exists('registerPendingPayment')) {
    /**
     * Registra un pago en estado pendiente.
     */
    function registerPendingPayment($user_id, $plan, $paypal_id, $method = 'paypal') {
        global $conn;
        if (!$conn) require __DIR__ . '/../db/connection.php';

        // Verificar si ya existe para no duplicar
        $stmt_check = $conn->prepare("SELECT id FROM user_subscriptions WHERE paypal_subscription_id = ?");
        $stmt_check->bind_param("s", $paypal_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $stmt_upd = $conn->prepare("UPDATE user_subscriptions SET status = 'pending' WHERE paypal_subscription_id = ? AND status != 'active'");
            $stmt_upd->bind_param("s", $paypal_id);
            $stmt_upd->execute();
            return ['success' => true, 'message' => 'Pago pendiente actualizado'];
        }

        $stmt_ins = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_name, fecha_fin, paypal_subscription_id, status, payment_method) VALUES (?, ?, NOW(), ?, 'pending', ?)");
        $stmt_ins->bind_param("isss", $user_id, $plan, $paypal_id, $method);
        
        if ($stmt_ins->execute()) {
            return ['success' => true, 'message' => 'Pago registrado como pendiente'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar pago pendiente: ' . $conn->error];
        }
    }
}

if (!function_exists('handlePaypalWebhookResource')) {
    /**
     * Procesa un recurso de webhook de PayPal (Sale o Capture)
     */
    function handlePaypalWebhookResource($resource, $is_completed = true) {
        global $conn;
        
        $paypal_id = $resource['id'] ?? $resource['parent_payment'] ?? $resource['billing_agreement_id'] ?? '';
        if (empty($paypal_id) && isset($resource['purchase_units'][0]['payments']['captures'][0]['id'])) {
            $paypal_id = $resource['purchase_units'][0]['payments']['captures'][0]['id'];
        }
        
        if (empty($paypal_id)) return ['success' => false, 'message' => 'No se encontró ID de PayPal'];

        if ($is_completed) {
            // Buscar la suscripción pendiente para saber el user_id y el plan
            $stmt = $conn->prepare("SELECT user_id, plan_name FROM user_subscriptions WHERE paypal_subscription_id = ? LIMIT 1");
            $stmt->bind_param("s", $paypal_id);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                return activateUserPlan($row['user_id'], $row['plan_name'], $paypal_id, 'paypal');
            } else {
                return ['success' => false, 'message' => 'No se encontró suscripción previa para este ID: ' . $paypal_id];
            }
        } else {
            // Simplemente asegurar que está en pending si ya existe, o no hacer nada si no sabemos el usuario
            $stmt = $conn->prepare("UPDATE user_subscriptions SET status = 'pending' WHERE paypal_subscription_id = ? AND status != 'active'");
            $stmt->bind_param("s", $paypal_id);
            $stmt->execute();
            return ['success' => true, 'message' => 'Estado actualizado a pendiente'];
        }
    }
}
