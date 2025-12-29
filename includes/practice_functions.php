<?php
/**
 * Funciones comunes para la práctica
 * Elimina duplicación entre archivos de práctica
 */

// Función para guardar progreso de práctica
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

// Función para guardar tiempo de práctica
function savePracticeTime($user_id, $mode, $duration) {
    global $conn;
    
    if ($duration <= 0 || !$mode) {
        return ['success' => false, 'error' => 'Datos inválidos'];
    }

    $stmt = $conn->prepare("INSERT INTO practice_time (user_id, mode, duration_seconds) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $user_id, $mode, $duration);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Error al guardar en BD'];
    }
}

// Función para obtener estadísticas de práctica
function getPracticeStats($user_id) {
    global $conn;
    
    $stats = [
        'selection' => ['count' => 0, 'accuracy' => 0],
        'writing' => ['count' => 0, 'accuracy' => 0],
        'sentences' => ['count' => 0, 'accuracy' => 0],
        'total_exercises' => 0
    ];
    
    $stmt = $conn->prepare("SELECT mode, COUNT(*) as cnt, SUM(total_words) as words, AVG(accuracy) as avg_accuracy FROM practice_progress WHERE user_id = ? GROUP BY mode");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_exercises = 0;
    
    while ($row = $result->fetch_assoc()) {
        $mode = $row['mode'];
        $stats[$mode] = [
            'count' => intval($row['words']),
            'accuracy' => round(floatval($row['avg_accuracy']), 1)
        ];
        $total_exercises += intval($row['cnt']);
    }
    
    $stats['total_exercises'] = $total_exercises;
    $stmt->close();
    
    return $stats;
}

// Función para obtener progreso de lectura
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
?> 