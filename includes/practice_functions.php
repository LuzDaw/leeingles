<?php
/**
 * Funciones comunes para la práctica
 * Elimina duplicación entre archivos de práctica
 */

/**
 * Guarda el progreso de práctica de un usuario.
 *
 * Registra el rendimiento del usuario en un modo de práctica específico,
 * incluyendo el número total de palabras, respuestas correctas e incorrectas,
 * y calcula la precisión.
 *
 * @param int $user_id El ID del usuario.
 * @param string $mode El modo de práctica (e.g., 'selection', 'writing', 'sentences').
 * @param int $total_words El número total de palabras involucradas en la práctica.
 * @param int $correct_answers El número de respuestas correctas.
 * @param int $incorrect_answers El número de respuestas incorrectas.
 * @param int|null $text_id (Opcional) El ID del texto si la práctica está asociada a un texto específico.
 * @return array Un array asociativo con 'success' (booleano), 'message' o 'error', y 'accuracy'.
 */
function savePracticeProgress($user_id, $mode, $total_words, $correct_answers, $incorrect_answers, $text_id = null) {
    global $conn;
    
    // Calcular precisión (correctas / total de respuestas)
    $total_answers = $correct_answers + $incorrect_answers;
    $accuracy = $total_answers > 0 ? round(($correct_answers / $total_answers) * 100, 2) : 0;

    try {
        $stmt = $conn->prepare("INSERT INTO practice_progress (user_id, text_id, mode, total_words, correct_answers, incorrect_answers, accuracy) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiiid", $user_id, $text_id, $mode, $total_words, $correct_answers, $incorrect_answers, $accuracy);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'success' => true, 
                'message' => 'Progreso guardado correctamente',
                'accuracy' => $accuracy
            ];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Error al guardar el progreso'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
    }
}

/**
 * Guarda el tiempo dedicado por un usuario a un modo de práctica específico.
 *
 * @param int $user_id El ID del usuario.
 * @param string $mode El modo de práctica.
 * @param int $duration La duración de la sesión de práctica en segundos.
 * @return array Un array asociativo con 'success' (booleano) y 'message' o 'error'.
 */
function savePracticeTime($user_id, $mode, $duration) {
    global $conn;
    
    if ($duration <= 0 || !$mode) {
        return ['success' => false, 'error' => 'Datos inválidos'];
    }

    // Asegurar tabla y comportamiento de acumulación diaria
    $conn->query("CREATE TABLE IF NOT EXISTS practice_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mode VARCHAR(32) NOT NULL,
        duration_seconds INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Intentar encontrar registro del mismo día
    $stmt_check = $conn->prepare("SELECT id FROM practice_time WHERE user_id = ? AND mode = ? AND DATE(created_at) = CURRENT_DATE() LIMIT 1");
    if ($stmt_check) {
        $stmt_check->bind_param('is', $user_id, $mode);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $existing_row = $res_check->fetch_assoc();
        $stmt_check->close();
    } else {
        $existing_row = null;
    }

    if ($existing_row) {
        $stmt = $conn->prepare("UPDATE practice_time SET duration_seconds = duration_seconds + ? WHERE id = ?");
        $stmt->bind_param('ii', $duration, $existing_row['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO practice_time (user_id, mode, duration_seconds) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $user_id, $mode, $duration);
    }

    if (!$stmt) {
        return ['success' => false, 'error' => 'Error preparando consulta: ' . $conn->error];
    }

    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) return ['success' => true];
    return ['success' => false, 'error' => 'Error al guardar en BD'];
}

/**
 * Obtiene las estadísticas de práctica de un usuario.
 *
 * Calcula y devuelve el número de palabras practicadas y la precisión media
 * para cada modo de práctica (selección, escritura, oraciones), así como
 * el total de ejercicios realizados.
 *
 * @param int $user_id El ID del usuario.
 * @return array Un array asociativo con las estadísticas de práctica por modo y el total de ejercicios.
 */
function getPracticeStats($user_id) {
    global $conn;
    $stats = [
        'selection' => ['sessions' => 0, 'total_words' => 0, 'last_accuracy' => 0],
        'writing' => ['sessions' => 0, 'total_words' => 0, 'last_accuracy' => 0],
        'sentences' => ['sessions' => 0, 'total_words' => 0, 'last_accuracy' => 0],
    ];
    $stmt = $conn->prepare("SELECT mode, COUNT(*) as sessions, SUM(total_words) as total_words, AVG(accuracy) as last_accuracy FROM practice_progress WHERE user_id = ? GROUP BY mode");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $mode = $row['mode'];
        if (!isset($stats[$mode])) {
            $stats[$mode] = ['sessions' => 0, 'total_words' => 0, 'last_accuracy' => 0];
        }
        $stats[$mode]['sessions'] = intval($row['sessions']);
        $stats[$mode]['total_words'] = intval($row['total_words']);
        $stats[$mode]['last_accuracy'] = round(floatval($row['last_accuracy']), 1);
    }
    $stmt->close();
    return $stats;
}

/**
 * Obtiene el progreso general de lectura de un usuario.
 *
 * Incluye estadísticas sobre palabras guardadas, textos leídos recientemente,
 * y el progreso en los diferentes modos de práctica.
 *
 * @param int $user_id El ID del usuario.
 * @return array Un array asociativo con el progreso de lectura del usuario.
 */
function getReadingProgress($user_id) {
    global $conn;
    
    $progress = [];
    
    // Obtener estadísticas de palabras guardadas
    $stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress['total_words'] = $result->fetch_assoc()['total_words'];
    $stmt->close();

    // Obtener últimas palabras practicadas
    $stmt = $conn->prepare("SELECT word, translation, created_at FROM saved_words WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress['recent_words'] = [];
    while ($row = $result->fetch_assoc()) {
        $progress['recent_words'][] = $row;
    }
    $stmt->close();

    // Obtener total de textos del usuario
    $stmt = $conn->prepare("SELECT COUNT(*) as total_texts FROM texts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress['total_texts'] = $result->fetch_assoc()['total_texts'];
    $stmt->close();

    // Obtener textos más recientes
    $stmt = $conn->prepare("SELECT title, created_at FROM texts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress['recent_texts'] = [];
    while ($row = $result->fetch_assoc()) {
        $progress['recent_texts'][] = $row;
    }
    $stmt->close();

    // Obtener progreso de práctica
    $progress['practice'] = getPracticeStats($user_id);
    
    return $progress;
}

/**
 * Devuelve la suma total de segundos de lectura para un usuario.
 */
function get_total_reading_seconds($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM reading_time WHERE user_id = ?");
    if (!$stmt) return 0;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return intval($row['total_seconds'] ?? 0);
}

/**
 * Guarda/actualiza el tiempo de lectura diario de un usuario para un texto.
 * @param int $user_id
 * @param int $text_id
 * @param int $duration segundos a añadir
 * @return array ['success'=>bool, 'error' => string?]
 */
function saveReadingTime($user_id, $text_id, $duration) {
    global $conn;

    if ($duration <= 0 || $text_id <= 0) {
        return ['success' => false, 'error' => 'Datos inválidos'];
    }

    // Asegurar tabla
    $conn->query("CREATE TABLE IF NOT EXISTS reading_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        text_id INT NOT NULL,
        duration_seconds INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
    )");

    $stmt_check = $conn->prepare("SELECT id FROM reading_time WHERE user_id = ? AND text_id = ? AND DATE(created_at) = CURRENT_DATE() LIMIT 1");
    if ($stmt_check) {
        $stmt_check->bind_param('ii', $user_id, $text_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $existing = $res_check->fetch_assoc();
        $stmt_check->close();
    } else {
        $existing = null;
    }

    if ($existing) {
        $stmt = $conn->prepare("UPDATE reading_time SET duration_seconds = duration_seconds + ? WHERE id = ?");
        $stmt->bind_param('ii', $duration, $existing['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO reading_time (user_id, text_id, duration_seconds) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $user_id, $text_id, $duration);
    }

    if (!$stmt) {
        return ['success' => false, 'error' => 'Error preparando consulta: ' . $conn->error];
    }

    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) return ['success' => true];
    return ['success' => false, 'error' => 'Error al guardar en BD'];
}

/**
 * Devuelve la suma total de segundos de práctica para un usuario.
 */
function get_total_practice_seconds($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM practice_time WHERE user_id = ?");
    if (!$stmt) return 0;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return intval($row['total_seconds'] ?? 0);
}

/**
 * Cuenta los textos completados (percent >= 100) por usuario.
 */
function get_completed_texts_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as read_count FROM reading_progress WHERE user_id = ? AND percent >= 100");
    if (!$stmt) return 0;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return intval($row['read_count'] ?? 0);
}

/**
 * Elimina entradas de reading_progress para una lista de text_ids y usuario.
 * @param int $user_id
 * @param array $text_ids
 * @return bool
 */
function deleteReadingProgressByTextIds($user_id, $text_ids) {
    global $conn;
    if (empty($text_ids)) return true;
    $placeholders = implode(',', array_fill(0, count($text_ids), '?'));
    $types = str_repeat('i', count($text_ids)) . 'i';
    $params = array_merge($text_ids, [$user_id]);
    $stmt = $conn->prepare("DELETE FROM reading_progress WHERE text_id IN ($placeholders) AND user_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}

/**
 * Obtiene una entrada de progreso de lectura para un usuario y texto.
 * @return array|null ['percent'=>int, 'pages_read'=>string, 'read_count'=>int] o null
 */
function getReadingProgressEntry($user_id, $text_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT percent, pages_read, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('ii', $user_id, $text_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Guarda o actualiza el progreso de lectura para un usuario y texto.
 * @param int $user_id
 * @param int $text_id
 * @param int $percent
 * @param string|array $pages_read JSON string o array
 * @param int $finish indicador si se completó (1)
 * @return array ['success'=>bool, 'error'=>string?]
 */
function saveReadingProgress($user_id, $text_id, $percent, $pages_read, $finish = 0) {
    global $conn;

    if ($text_id <= 0 || $user_id <= 0) return ['success' => false, 'error' => 'Datos inválidos'];

    // Aceptar array y convertir a JSON si es necesario
    if (is_array($pages_read)) {
        $pages_read = json_encode($pages_read);
    }

    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT percent, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
    if (!$stmt) return ['success' => false, 'error' => 'Error preparando consulta'];
    $stmt->bind_param('ii', $user_id, $text_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $new_read_count = (int)$existing['read_count'];
        if ($finish === 1 || ($percent >= 100 && (int)$existing['percent'] < 100)) {
            $new_read_count++;
        }

        $stmt2 = $conn->prepare("UPDATE reading_progress SET percent = ?, pages_read = ?, updated_at = ?, read_count = ? WHERE user_id = ? AND text_id = ?");
        if (!$stmt2) return ['success' => false, 'error' => 'Error preparando actualización'];
        $stmt2->bind_param('issiii', $percent, $pages_read, $now, $new_read_count, $user_id, $text_id);
        $ok = $stmt2->execute();
        $stmt2->close();
        return ['success' => (bool)$ok];
    } else {
        $init_read_count = ($percent >= 100 || $finish === 1) ? 1 : 0;
        $stmt2 = $conn->prepare("INSERT INTO reading_progress (user_id, text_id, percent, pages_read, updated_at, read_count) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt2) return ['success' => false, 'error' => 'Error preparando inserción'];
        $stmt2->bind_param('iiissi', $user_id, $text_id, $percent, $pages_read, $now, $init_read_count);
        $ok = $stmt2->execute();
        $stmt2->close();
        return ['success' => (bool)$ok];
    }
}
?>
