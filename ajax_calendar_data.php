<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db/connection.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    // Para pruebas, intentar obtener user_id desde POST/GET
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
    } elseif (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario no autenticado']);
        exit;
    }
} else {
    $user_id = $_SESSION['user_id'];
}

// Obtener mes y año de la petición (por defecto mes actual)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Calcular el primer y último día del mes
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(0, 0, 0, $month + 1, 0, $year);

$start_date = date('Y-m-d', $first_day);
$end_date = date('Y-m-d', $last_day);

try {
    // Consulta consolidada para obtener tiempo de lectura y práctica por día
    $query = "
        SELECT activity_date, SUM(reading_seconds) as reading_seconds, SUM(practice_seconds) as practice_seconds
        FROM (
            SELECT DATE(created_at) as activity_date, duration_seconds as reading_seconds, 0 as practice_seconds
            FROM reading_time 
            WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT DATE(created_at) as activity_date, 0 as reading_seconds, duration_seconds as practice_seconds
            FROM practice_time 
            WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
        ) as combined_activity
        GROUP BY activity_date
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ississ", $user_id, $start_date, $end_date, $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activity_by_date = [];
    while ($row = $result->fetch_assoc()) {
        $activity_by_date[$row['activity_date']] = [
            'reading' => intval($row['reading_seconds']),
            'practice' => intval($row['practice_seconds'])
        ];
    }
    $stmt->close();
    
    // Generar datos para todos los días del mes
    $calendar_data = [];
    $current_date = $first_day;
    
    while ($current_date <= $last_day) {
        $date_key = date('Y-m-d', $current_date);
        $day_number = date('j', $current_date);
        
        $reading_seconds = $activity_by_date[$date_key]['reading'] ?? 0;
        $practice_seconds = $activity_by_date[$date_key]['practice'] ?? 0;
        $total_seconds_day = $reading_seconds + $practice_seconds;
        
        $calendar_data[] = [
            'date' => $date_key,
            'day' => $day_number,
            'seconds' => $total_seconds_day,
            'reading_seconds' => $reading_seconds,
            'practice_seconds' => $practice_seconds,
            'formatted_time' => formatReadingTime($total_seconds_day),
            'formatted_reading' => formatReadingTime($reading_seconds),
            'formatted_practice' => formatReadingTime($practice_seconds),
            'has_activity' => $total_seconds_day > 0
        ];
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    // Calcular estadísticas del mes
    $total_seconds = array_sum(array_column($calendar_data, 'seconds'));
    $days_with_activity = count(array_filter($calendar_data, function($day) {
        return $day['has_activity'];
    }));
    
    $total_days = count($calendar_data);
    $average_seconds = $days_with_activity > 0 ? $total_seconds / $days_with_activity : 0;
    
    $stats = [
        'days_with_activity' => $days_with_activity,
        'total_time' => formatReadingTime($total_seconds),
        'average_time' => formatReadingTime($average_seconds),
        'total_seconds' => $total_seconds
    ];
    
    echo json_encode([
        'success' => true,
        'calendar_data' => $calendar_data,
        'stats' => $stats,
        'month' => $month,
        'year' => $year,
        'month_name' => date('F Y', $first_day)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos del calendario: ' . $e->getMessage()]);
}

/**
 * Formatea segundos en una cadena legible (ej: 1h 20m o 45 min)
 */
function formatReadingTime($seconds) {
    if ($seconds == 0) {
        return '0 min';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    } else {
        return $minutes . ' min';
    }
}
?>
