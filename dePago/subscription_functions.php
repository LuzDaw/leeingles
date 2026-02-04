<?php
/**
 * Funciones de control de suscripción y tiempo de uso
 * Ubicación: dePago/subscription_functions.php
 */

if (!function_exists('getSubscriptionData')) {
    /**
     * Obtiene los datos básicos de suscripción de un usuario desde la base de datos.
     *
     * Recupera la fecha de registro, el tipo de usuario y la última conexión del usuario.
     *
     * @param int $user_id El ID del usuario.
     * @return array|null Un array asociativo con 'fecha_registro', 'tipo_usuario' y 'ultima_conexion', o null si el usuario no se encuentra.
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
     * Calcula el estado actual de la suscripción de un usuario.
     *
     * Determina si el usuario está en un período de prueba, limitado o tiene un plan premium activo,
     * basándose en la fecha de registro, la última conexión y los datos de suscripción.
     *
     * @param int $user_id El ID del usuario.
     * @return array|null Un array asociativo con el estado de la suscripción, días transcurridos,
     *                    mes de uso, fechas de reinicio y otros detalles relevantes, o null si el usuario no se encuentra.
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
        if (in_array($data['tipo_usuario'], ['Basico', 'Ahorro', 'Pro'])) {
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
            'es_premium' => in_array($estado_actual, ['Basico', 'Ahorro', 'Pro']),
            'proximo_reinicio_semanal' => $proximo_domingo->format('Y-m-d H:i:s'),
            'semana_iso' => (int)date('W'),
            'anio_iso' => (int)date('o'),
            'ultima_conexion' => $data['ultima_conexion']
        ];
    }
}

if (!function_exists('initUserSubscription')) {
    /**
     * Inicializa la ficha de suscripción de un nuevo usuario.
     *
     * Esta función se puede llamar después de crear un nuevo usuario. Actualmente,
     * la base de datos maneja los valores por defecto, pero esta función está
     * preparada para futuras inicializaciones (ej. registros en `uso_traducciones` o emails de bienvenida).
     *
     * @param int $user_id El ID del nuevo usuario.
     * @return bool Siempre devuelve true por ahora.
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
     * Obtiene el conteo de palabras traducidas por un usuario en la semana ISO actual.
     *
     * Si no existe un registro semanal para el usuario, la semana y el año actuales,
     * intenta buscar un registro mensual (sistema antiguo) o crea un nuevo registro semanal con contador a 0.
     *
     * @param int $user_id El ID del usuario.
     * @return int El número de palabras traducidas en la semana actual.
     */
    function getWeeklyUsage($user_id) {
        global $conn;
        if (!$conn) require_once __DIR__ . '/../db/connection.php';
        
        $semana = (int)date('W');
        $anio = (int)date('o'); // 'o' es el año ISO-8601, mejor para semanas
        $mes = (int)date('n');
        
        // Intentar obtener por semana (nuevo sistema)
        $stmt = $conn->prepare("SELECT contador FROM uso_traducciones WHERE user_id = ? AND semana = ? AND anio = ?");
        if ($stmt) {
            $stmt->bind_param("iii", $user_id, $semana, $anio);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return (int)$row['contador'];
            }
        }

        // Si no existe por semana, intentar por mes (sistema antiguo o fallback)
        $stmt_old = $conn->prepare("SELECT contador FROM uso_traducciones WHERE user_id = ? AND mes = ? AND anio = ? AND semana IS NULL");
        if ($stmt_old) {
            $stmt_old->bind_param("iii", $user_id, $mes, $anio);
            $stmt_old->execute();
            $result_old = $stmt_old->get_result();
            if ($row_old = $result_old->fetch_assoc()) {
                return (int)$row_old['contador'];
            }
        }

        // Si no existe nada, crear registro semanal
        // Usamos una lógica que capture el error de duplicidad para evitar el Fatal Error
        try {
            $stmt_init = $conn->prepare("INSERT INTO uso_traducciones (user_id, semana, mes, anio, contador) VALUES (?, ?, ?, ?, 0)");
            if ($stmt_init) {
                $stmt_init->bind_param("iiii", $user_id, $semana, $mes, $anio);
                $stmt_init->execute();
                $stmt_init->close();
            }
        } catch (Exception $e) {
            // Si falla por duplicidad (índice antiguo), simplemente no hacemos nada, 
            // ya que el registro mensual ya existe y se usará como fallback.
        } catch (Error $e) {
            // Capturar errores de motor de PHP
        }
        
        return 0;
    }
}

if (!function_exists('incrementTranslationUsage')) {
    /**
     * Cuenta las palabras de un texto e incrementa el contador de uso semanal del usuario.
     *
     * Limpia el texto de etiquetas HTML antes de contar las palabras.
     * Asegura que el registro semanal de uso exista antes de intentar incrementarlo.
     *
     * @param int $user_id El ID del usuario.
     * @param string $text El texto cuyo número de palabras se va a contar.
     * @return bool `true` si el contador se incrementó correctamente, `false` en caso contrario.
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
     * Obtiene una lista de usuarios con estado 'limitado' o 'EnPrueba' que han estado inactivos.
     *
     * Un usuario se considera inactivo si su `ultima_conexion` es anterior a `$days` días,
     * o si `ultima_conexion` es NULL y `fecha_registro` es anterior a `$days` días.
     *
     * @param int $days (Opcional) El número de días de inactividad para considerar a un usuario inactivo. Por defecto es 14.
     * @return array Un array de objetos de usuario inactivos.
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
     * Verifica si un usuario puede realizar más traducciones basándose en su plan y uso semanal.
     *
     * Los usuarios 'limitado' tienen un límite semanal de 300 palabras. Se aplica un margen
     * de cortesía de 50 palabras adicionales si el usuario ya está en una sesión de lectura activa.
     * Los usuarios premium o en período de prueba no tienen límite.
     *
     * @param int $user_id El ID del usuario.
     * @param bool $is_active_reading (Opcional) Indica si el usuario está actualmente en una sesión de lectura activa. Por defecto es false.
     * @return array Un array asociativo con 'can_translate' (booleano), 'limit_reached' (booleano),
     *               'usage', 'limit', 'grace_limit', 'reason', 'next_reset' y 'status'.
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
     * FUNCIÓN DE DEPURACIÓN: Permite cambiar la fecha de registro de un usuario.
     *
     * Útil para pruebas de lógica de suscripción y períodos de prueba.
     *
     * @param int $user_id El ID del usuario.
     * @param string $new_date La nueva fecha de registro en formato 'YYYY-MM-DD HH:MM:SS'.
     * @return bool `true` si la actualización fue exitosa, `false` en caso contrario.
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
     * FUNCIÓN DE DEPURACIÓN: Añade uso artificial al contador semanal de traducciones de un usuario.
     *
     * Útil para pruebas de límites de traducción.
     *
     * @param int $user_id El ID del usuario.
     * @param int $words El número de palabras a añadir al contador.
     * @return bool `true` si la actualización fue exitosa, `false` en caso contrario.
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
     * FUNCIÓN DE DEPURACIÓN: Permite cambiar la fecha de última conexión de un usuario.
     *
     * Útil para pruebas de lógica de inactividad.
     *
     * @param int $user_id El ID del usuario.
     * @param string $new_date La nueva fecha de última conexión en formato 'YYYY-MM-DD HH:MM:SS'.
     * @return bool `true` si la actualización fue exitosa, `false` en caso contrario.
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
