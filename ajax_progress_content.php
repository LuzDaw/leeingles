<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="text-align: center; padding: 40px; color: #ef4444;">Debes iniciar sesión para ver tu progreso.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Obtener estadísticas de palabras guardadas
$stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_words = $result->fetch_assoc()['total_words'];
$stmt->close();

// Obtener palabras guardadas por día (últimos 7 días)
$stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM saved_words WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$daily_words = [];
while ($row = $result->fetch_assoc()) {
    $daily_words[$row['date']] = $row['count'];
}
$stmt->close();

// Obtener textos subidos
$stmt = $conn->prepare("SELECT COUNT(*) as total_texts FROM texts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_texts = $result->fetch_assoc()['total_texts'];
$stmt->close();

// Obtener progreso de práctica por modo
$practice_stats = [];
$practice_modes = ['selection', 'writing', 'sentences'];
foreach ($practice_modes as $mode) {
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_attempts,
        AVG(accuracy) as success_rate,
        SUM(total_words) as unique_words
        FROM practice_progress 
        WHERE user_id = ? AND mode = ?");
    $stmt->bind_param("is", $user_id, $mode);
    $stmt->execute();
    $result = $stmt->get_result();
    $practice_stats[$mode] = $result->fetch_assoc();
    $stmt->close();
}

// Obtener número de textos leídos al 100%
$read_texts_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as read_count FROM reading_progress WHERE user_id = ? AND percent >= 100");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$read_texts_count = $result->fetch_assoc()['read_count'];
$stmt->close();

// === PROGRESO DE LECTURA AJAX ===
if (isset($_SESSION['user_id']) && (isset($_GET['text_id']) || isset($_POST['text_id']))) {
    $user_id = $_SESSION['user_id'];
    $text_id = isset($_GET['text_id']) ? intval($_GET['text_id']) : intval($_POST['text_id']);
    // --- GET: Recuperar progreso ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log('[PROGRESS][GET] user_id=' . $user_id . ' text_id=' . $text_id);
        $q = $conn->query("SHOW TABLES LIKE 'reading_progress'");
        if ($q->num_rows === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No existe la tabla reading_progress']);
            exit;
        }
        $stmt = $conn->prepare("SELECT percent, pages_read, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
        $stmt->bind_param('ii', $user_id, $text_id);
        $stmt->execute();
        $stmt->bind_result($percent, $pages_read, $read_count);
        if ($stmt->fetch()) {
            $pages_read_arr = json_decode($pages_read, true) ?: [];
            error_log('[PROGRESS][GET-RESULT] user_id=' . $user_id . ' text_id=' . $text_id . ' percent=' . intval($percent) . ' read_count=' . intval($read_count));
            header('Content-Type: application/json');
            echo json_encode(['percent' => intval($percent), 'pages_read' => $pages_read_arr, 'read_count' => intval($read_count)]);
        } else {
            error_log('[PROGRESS][GET-EMPTY] user_id=' . $user_id . ' text_id=' . $text_id);
            header('Content-Type: application/json');
            echo json_encode(['percent' => 0, 'pages_read' => [], 'read_count' => 0]);
        }
        $stmt->close();
        exit;
    }
    // --- POST: Guardar progreso ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['percent']) && isset($_POST['pages_read'])) {
        error_log('[PROGRESS][POST] user_id=' . $user_id . ' text_id=' . $text_id . ' percent=' . $_POST['percent']);
        $q = $conn->query("SHOW TABLES LIKE 'reading_progress'");
        if ($q->num_rows === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No existe la tabla reading_progress']);
            exit;
        }
        $percent = intval($_POST['percent']);
        $pages_read = $_POST['pages_read'];
        // Solo actualizar si el nuevo porcentaje es mayor
        $stmt = $conn->prepare("SELECT percent, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
        $stmt->bind_param('ii', $user_id, $text_id);
        $stmt->execute();
        $stmt->bind_result($old_percent, $old_read_count);
        if ($stmt->fetch()) {
            $stmt->close();
            $new_read_count = $old_read_count;
            if ($percent >= 100 && $old_percent < 100) {
                $new_read_count = $old_read_count + 1;
            }
            $stmt2 = $conn->prepare("UPDATE reading_progress SET percent = ?, pages_read = ?, updated_at = NOW(), read_count = ? WHERE user_id = ? AND text_id = ?");
            $stmt2->bind_param('isiii', $percent, $pages_read, $new_read_count, $user_id, $text_id);
            $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt->close();
            $init_read_count = ($percent >= 100) ? 1 : 0;
            $stmt2 = $conn->prepare("INSERT INTO reading_progress (user_id, text_id, percent, pages_read, updated_at, read_count) VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt2->bind_param('iiisi', $user_id, $text_id, $percent, $pages_read, $init_read_count);
            $stmt2->execute();
            $stmt2->close();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
?>

<link rel="stylesheet" href="css/progress-styles.css">

<div class="tab-content-wrapper">
    <div class="stats-grid">
        <div class="stat-card clickable-stat" onclick="switchToTab('texts')" title="Ver mis textos">
            <div class="stat-icon">📄</div>
            <div class="stat-number"><?= $total_texts ?></div>
            <div class="stat-label">Textos Subidos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?= $read_texts_count ?></div>
            <div class="stat-label">Textos Leídos</div>
        </div>
        
        <div class="stat-card clickable-stat" onclick="switchToTab('saved-words')" title="Ver mis palabras guardadas">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?= $total_words ?></div>
            <div class="stat-label">Palabras Guardadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-number"><?= array_sum(array_filter(array_column($practice_stats, 'total_attempts'))) ?></div>
            <div class="stat-label">Sesiones de Práctica</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-number"><?= count($practice_stats) > 0 && array_sum(array_filter(array_column($practice_stats, 'success_rate'))) > 0 ? round(array_sum(array_filter(array_column($practice_stats, 'success_rate'))) / count(array_filter(array_column($practice_stats, 'success_rate')))) : 0 ?>%</div>
            <div class="stat-label">Precisión Promedio</div>
        </div>
    </div>

    <?php if (!empty($daily_words)): ?>
   <div class="progress-section">
  <h3>📈 Actividad Reciente (Últimos 7 días) - Práctica y Lectura</h3>
  
  <!-- Layout horizontal para calendario y actividad reciente -->
  <div class="progress-layout">
    <!-- Calendario -->
    <div class="calendar-section">
      <div class="calendar-container">
        <div class="calendar-header">
          <div class="month-navigator">
            <button class="calendar-nav-btn" onclick="previousMonth()">‹</button>
            <h2 class="current-month">Julio 2025</h2>
            <button class="calendar-nav-btn" onclick="nextMonth()">›</button>
          </div>
          <button class="calendar-nav-btn" onclick="updateCalendarNow()" style="background: #28a745; color: white; border-color: #28a745;">🔄</button>
        </div>
        
        <div class="calendar-grid">
          <!-- Días de la semana -->
          <div class="day-header">Dom</div>
          <div class="day-header">Lun</div>
          <div class="day-header">Mar</div>
          <div class="day-header">Mié</div>
          <div class="day-header">Jue</div>
          <div class="day-header">Vie</div>
          <div class="day-header">Sáb</div>
          
          <!-- Los días se cargarán dinámicamente -->
        </div>
      </div>
    </div>

    <!-- Progreso de Práctica -->
    <div class="activity-section">
        <h3>🎯 Progreso de Práctica por Modo</h3>
        <div class="practice-modes" id="practice-modes-container">
            <div style="text-align: center; padding: 20px; color: #6b7280;">
                <div class="loading-spinner"></div>
                <p>Cargando estadísticas...</p>
            </div>
        </div>
    </div>
  </div>
</div>
    <?php endif; ?>



    <script>
    // Cargar estadísticas de práctica
    async function loadPracticeStats() {
        try {
            const response = await fetch('get_practice_stats.php');
            const data = await response.json();
            
            if (data.success) {
                const container = document.getElementById('practice-modes-container');
                const modeNames = {
                    'selection': '📝 Selección Múltiple',
                    'writing': '✍️ Escritura Libre',
                    'sentences': '📖 Práctica de Oraciones'
                };
                
                const elementNames = {
                    'selection': 'palabras',
                    'writing': 'palabras',
                    'sentences': 'frases'
                };
                
                let html = '';
                let hasStats = false;
                
                for (const [mode, stats] of Object.entries(data.stats)) {
                    if (stats.sessions > 0) {
                        hasStats = true;
                        html += `
                            <div class="practice-mode-card">
                                <div class="mode-header">
                                    <span class="mode-title">${modeNames[mode] || mode}</span>
                                    <span class="mode-success">${stats.last_accuracy}% precisión</span>
                                </div>
                                <div class="mode-details">
                                    <span>${stats.sessions} sesiones</span>
                                    <span>${stats.total_words} ${elementNames[mode] || 'elementos'} totales</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${stats.last_accuracy}%;"></div>
                                </div>
                            </div>
                        `;
                    }
                }
                
                if (!hasStats) {
                    html = `
                        <div style="text-align: center; padding: 40px; color: #6b7280;">
                            <div style="font-size: 3rem; margin-bottom: 20px;">🎯</div>
                            <h3>No hay estadísticas de práctica aún</h3>
                            <p>Comienza a practicar para ver tu progreso aquí.</p>
                            <button onclick="loadTabContent('practice')" class="nav-btn primary" style="margin-top: 20px;">
                                🎯 Ir a Práctica
                            </button>
                        </div>
                    `;
                }
                
                if (container) {
                    container.innerHTML = html;
                }
            } else {
                // Error cargando estadísticas
            }
        } catch (error) {
            const container = document.getElementById('practice-modes-container');
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <p>Error cargando estadísticas de práctica.</p>
                    </div>
                `;
            }
        }
    }
    
    // Cargar estadísticas cuando se carga la página
    loadPracticeStats();
    
    // Inicializar calendario
    if (typeof initializeCalendar === 'function') {
        initializeCalendar();
    } else {
        // Esperar a que se cargue el script del calendario
        setTimeout(() => {
            if (typeof initializeCalendar === 'function') {
                initializeCalendar();
            } else {
                // No se pudo inicializar el calendario
            }
        }, 1000);
    }
    </script>

    <?php if ($total_words == 0 && $total_texts == 0): ?>
    <div class="empty-state">
        <div class="empty-icon">📊</div>
        <h3>¡Comienza tu viaje de aprendizaje!</h3>
        <p>Sube tu primer texto y comienza a guardar palabras para ver tu progreso aquí.</p>
        <button onclick="loadTabContent('upload')" class="btn btn-primary">⬆ Subir Primer Texto</button>
    </div>
    <?php endif; ?>
</div>
