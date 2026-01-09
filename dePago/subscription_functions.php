<?php
/**
 * Funciones de control de suscripción y tiempo de uso
 * Ubicación: dePago/subscription_functions.php
 */

if (!function_exists('getSubscriptionData')) {
    /**
     * Obtiene los datos básicos de suscripción del usuario desde la base de datos
     */
    function getSubscriptionData($user_id) {
        global $conn;
        
        if (!$conn) {
            require_once __DIR__ . '/../db/connection.php';
        }
        
        $stmt = $conn->prepare("SELECT fecha_registro, tipo_usuario, ultima_conexion FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row;
        }
        return null;
    }
}

if (!function_exists('getUserSubscriptionStatus')) {
    /**
     * Calcula el estado actual del usuario basado en el tiempo transcurrido desde su registro
     * Retorna un array con el tipo de usuario, días transcurridos y el mes relativo de uso.
     */
    function getUserSubscriptionStatus($user_id) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';

        $data = getSubscriptionData($user_id);
        if (!$data) return null;

        $fecha_registro = new DateTime($data['fecha_registro']);
        $hoy = new DateTime();
        
        // Calculamos la diferencia total
        $intervalo = $fecha_registro->diff($hoy);
        
        // Días totales transcurridos
        $dias_transcurridos = $intervalo->days;
        
        // Cálculo de meses de uso (mes 0 es el primer mes gratuito)
        // Usamos una lógica de 30 días por mes para simplificar el control
        $mes_de_uso = floor($dias_transcurridos / 30);
        
        // Determinar el estado lógico
        $es_periodo_gratuito = ($dias_transcurridos < 30);
        
        $estado_actual = 'limitado';
        if (in_array($data['tipo_usuario'], ['Inicio', 'Ahorro', 'Pro'])) {
            // Verificar si la suscripción sigue vigente en la tabla user_subscriptions
            $stmt = $conn->prepare("SELECT fecha_fin FROM user_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY fecha_fin DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($sub = $res->fetch_assoc()) {
                $fecha_fin = new DateTime($sub['fecha_fin']);
                if ($fecha_fin > $hoy) {
                    $estado_actual = $data['tipo_usuario'];
                } else {
                    // Ha expirado, actualizamos el tipo_usuario a limitado
                    $conn->query("UPDATE users SET tipo_usuario = 'limitado' WHERE id = $user_id");
                    $estado_actual = 'limitado';
                }
            } else {
                $estado_actual = 'limitado';
            }
        } elseif ($es_periodo_gratuito) {
            $estado_actual = 'EnPrueba';
        }

        // Calcular el inicio y fin del periodo mensual actual (para el mes gratuito)
        $inicio_periodo_mensual = (new DateTime($data['fecha_registro']))->add(new DateInterval('P' . ($mes_de_uso * 30) . 'D'));
        $fin_periodo_mensual = (new DateTime($data['fecha_registro']))->add(new DateInterval('P' . (($mes_de_uso + 1) * 30) . 'D'));

        // Calcular el próximo domingo (reinicio semanal)
        $proximo_domingo = new DateTime();
        $proximo_domingo->modify('next sunday');
        $proximo_domingo->setTime(23, 59, 59);

        return [
            'user_id' => $user_id,
            'tipo_base' => $data['tipo_usuario'],
            'estado_logico' => $estado_actual,
            'fecha_registro' => $data['fecha_registro'],
            'dias_transcurridos' => $dias_transcurridos,
            'mes_de_uso' => (int)$mes_de_uso,
            'es_periodo_gratuito' => $es_periodo_gratuito,
            'fin_mes_gratuito' => (new DateTime($data['fecha_registro']))->add(new DateInterval('P30D'))->format('Y-m-d H:i:s'),
            'es_premium' => in_array($estado_actual, ['Inicio', 'Ahorro', 'Pro']),
            'proximo_reinicio_semanal' => $proximo_domingo->format('Y-m-d H:i:s'),
            'semana_iso' => (int)date('W'),
            'anio_iso' => (int)date('o'),
            'ultima_conexion' => $data['ultima_conexion']
        ];
    }
}

if (!function_exists('initUserSubscription')) {
    /**
     * Función para inicializar la ficha de suscripción de un nuevo usuario.
     * Se puede llamar justo después de crear el usuario en la tabla 'users'.
     */
    function initUserSubscription($user_id) {
        global $conn;
        
        // Por ahora, la base de datos ya pone los valores por defecto (fecha_registro y tipo_usuario='EnPrueba').
        // Esta función queda preparada por si en el futuro queremos insertar un registro inicial 
        // en 'uso_traducciones' o enviar un email de bienvenida específico.
        
        return true; 
    }
}

if (!function_exists('getWeeklyUsage')) {
    /**
     * Obtiene el conteo de palabras traducidas en la semana actual.
     * Si no existe el registro, lo crea.
     */
    function getWeeklyUsage($user_id) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        $semana = (int)date('W');
        $anio = (int)date('o'); // 'o' es el año ISO-8601, mejor para semanas
        $mes = (int)date('n');
        
        $stmt = $conn->prepare("SELECT contador FROM uso_traducciones WHERE user_id = ? AND semana = ? AND anio = ?");
        if (!$stmt) return 0;

        $stmt->bind_param("iii", $user_id, $semana, $anio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (int)$row['contador'];
        } else {
            // Crear registro inicial para la semana
            // Incluimos 'mes' porque en la estructura original es NOT NULL y sin valor por defecto
            $stmt_init = $conn->prepare("INSERT INTO uso_traducciones (user_id, semana, mes, anio, contador) VALUES (?, ?, ?, ?, 0)");
            if (!$stmt_init) return 0;

            $stmt_init->bind_param("iiii", $user_id, $semana, $mes, $anio);
            $stmt_init->execute();
            return 0;
        }
    }
}

if (!function_exists('incrementTranslationUsage')) {
    /**
     * Cuenta las palabras de un texto e incrementa el contador semanal.
     */
    function incrementTranslationUsage($user_id, $text) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        // Contar palabras (limpiando espacios y caracteres extra)
        $word_count = str_word_count(strip_tags($text));
        if ($word_count === 0 && trim($text) !== '') $word_count = 1; // Al menos 1 si hay texto
        
        $semana = (int)date('W');
        $anio = (int)date('o');
        
        // Asegurar que el registro existe
        getWeeklyUsage($user_id);
        
        $stmt = $conn->prepare("UPDATE uso_traducciones SET contador = contador + ? WHERE user_id = ? AND semana = ? AND anio = ?");
        if (!$stmt) return false;

        $stmt->bind_param("iiii", $word_count, $user_id, $semana, $anio);
        return $stmt->execute();
    }
}

if (!function_exists('getInactiveLimitedUsers')) {
    /**
     * Obtiene la lista de usuarios con estado 'limitado' que llevan más de 14 días sin conectarse.
     */
    function getInactiveLimitedUsers($days = 14) {
        global $conn;
        if (!$conn) {
            require_once __DIR__ . '/../db/connection.php';
        }

        // Buscamos usuarios 'limitado' cuya última conexión sea anterior a X días
        // O que nunca se hayan conectado (ultima_conexion IS NULL) pero lleven registrados más de X días
        $stmt = $conn->prepare("
            SELECT id, username, email, ultima_conexion, fecha_registro 
            FROM users 
            WHERE (tipo_usuario = 'limitado' OR tipo_usuario = 'EnPrueba')
            AND (
                ultima_conexion < DATE_SUB(NOW(), INTERVAL ? DAY)
                OR (ultima_conexion IS NULL AND fecha_registro < DATE_SUB(NOW(), INTERVAL ? DAY))
            )
        ");
        
        $stmt->bind_param("ii", $days, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
}

if (!function_exists('checkTranslationLimit')) {
    /**
     * Verifica si el usuario puede realizar más traducciones.
     * Límite: 300 palabras semanales para usuarios 'limitado' fuera del mes gratuito.
     * Se permite un margen de cortesía de 50 palabras (total 350) si la lectura ya está en curso.
     */
    function checkTranslationLimit($user_id, $is_active_reading = false) {
        $status = getUserSubscriptionStatus($user_id);
        
        // Planes de pago y Mes de Prueba no tienen límite
        if ($status['es_premium'] || $status['estado_logico'] === 'EnPrueba') {
            return [
                'can_translate' => true, 
                'reason' => 'unlimited',
                'status' => $status['estado_logico']
            ];
        }
        
        $usage = getWeeklyUsage($user_id);
        $base_limit = 300;
        $grace_limit = 350; // Margen de 50 palabras
        
        $current_limit = $is_active_reading ? $grace_limit : $base_limit;
        
        if ($usage >= $current_limit) {
            return [
                'can_translate' => false, 
                'limit_reached' => true,
                'usage' => $usage, 
                'limit' => $base_limit,
                'grace_limit' => $grace_limit,
                'reason' => 'limit_reached',
                'next_reset' => $status['proximo_reinicio_semanal'],
                'status' => $status['estado_logico']
            ];
        }
        
        return [
            'can_translate' => true, 
            'usage' => $usage, 
            'limit' => $base_limit,
            'remaining' => $base_limit - $usage,
            'next_reset' => $status['proximo_reinicio_semanal'],
            'status' => $status['estado_logico']
        ];
    }
}

if (!function_exists('debugUpdateRegistrationDate')) {
    /**
     * FUNCIÓN DE PRUEBA: Permite cambiar la fecha de registro de un usuario.
     */
    function debugUpdateRegistrationDate($user_id, $new_date) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        $stmt = $conn->prepare("UPDATE users SET fecha_registro = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("si", $new_date, $user_id);
        return $stmt->execute();
    }
}

if (!function_exists('debugAddUsage')) {
    /**
     * FUNCIÓN DE PRUEBA: Añade uso artificial al contador semanal.
     */
    function debugAddUsage($user_id, $words) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        $semana = (int)date('W');
        $anio = (int)date('o');
        getWeeklyUsage($user_id);
        
        $stmt = $conn->prepare("UPDATE uso_traducciones SET contador = contador + ? WHERE user_id = ? AND semana = ? AND anio = ?");
        if (!$stmt) return false;
        $stmt->bind_param("iiii", $words, $user_id, $semana, $anio);
        return $stmt->execute();
    }
}

if (!function_exists('debugUpdateLastConnection')) {
    /**
     * FUNCIÓN DE PRUEBA: Permite cambiar la fecha de última conexión de un usuario.
     */
    function debugUpdateLastConnection($user_id, $new_date) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        $stmt = $conn->prepare("UPDATE users SET ultima_conexion = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("si", $new_date, $user_id);
        return $stmt->execute();
    }
}

/**
 * Ejemplo de uso:
 * $status = getUserSubscriptionStatus($_SESSION['user_id']);
 * if ($status['es_periodo_gratuito']) { ... }
 * echo "Estás en tu mes de uso número: " . $status['mes_de_uso'];
 */
