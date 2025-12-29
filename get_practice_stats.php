<?php
session_start();
require_once 'db/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Obtener estadísticas por modo
    $stmt = $conn->prepare("
        SELECT 
            mode,
            COUNT(*) as sessions,
            SUM(total_words) as total_words,
            SUM(correct_answers) as total_correct,
            SUM(incorrect_answers) as total_incorrect,
            AVG(accuracy) as avg_accuracy,
            MAX(accuracy) as best_accuracy,
            MIN(completed_at) as first_session,
            MAX(completed_at) as last_session
        FROM practice_progress 
        WHERE user_id = ? 
        GROUP BY mode
        ORDER BY mode
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $mode = $row['mode'];
        
        // Obtener el último ejercicio para este modo
        $stmt2 = $conn->prepare("
            SELECT accuracy, total_words, correct_answers, incorrect_answers, completed_at
            FROM practice_progress 
            WHERE user_id = ? AND mode = ?
            ORDER BY completed_at DESC 
            LIMIT 1
        ");
        $stmt2->bind_param("is", $user_id, $mode);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $last_exercise = $result2->fetch_assoc();
        $stmt2->close();
        
        $stats[$mode] = [
            'sessions' => intval($row['sessions']),
            'total_words' => intval($row['total_words']),
            'total_correct' => intval($row['total_correct']),
            'total_incorrect' => intval($row['total_incorrect']),
            'avg_accuracy' => round(floatval($row['avg_accuracy']), 1),
            'best_accuracy' => round(floatval($row['best_accuracy']), 1),
            'first_session' => $row['first_session'],
            'last_session' => $row['last_session'],
            'last_accuracy' => $last_exercise ? round(floatval($last_exercise['accuracy']), 1) : 0,
            'last_total_words' => $last_exercise ? intval($last_exercise['total_words']) : 0,
            'last_correct' => $last_exercise ? intval($last_exercise['correct_answers']) : 0,
            'last_incorrect' => $last_exercise ? intval($last_exercise['incorrect_answers']) : 0
        ];
    }
    
    // Obtener estadísticas generales
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(total_words) as grand_total_words,
            SUM(correct_answers) as grand_total_correct,
            SUM(incorrect_answers) as grand_total_incorrect,
            AVG(accuracy) as grand_avg_accuracy
        FROM practice_progress 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $general_stats = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'general' => [
            'total_sessions' => intval($general_stats['total_sessions']),
            'grand_total_words' => intval($general_stats['grand_total_words']),
            'grand_total_correct' => intval($general_stats['grand_total_correct']),
            'grand_total_incorrect' => intval($general_stats['grand_total_incorrect']),
            'grand_avg_accuracy' => round(floatval($general_stats['grand_avg_accuracy']), 1)
        ]
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 