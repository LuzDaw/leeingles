<?php
/**
 * Contenido de la pestaña de progreso
 * Muestra estadísticas generales, tiempo de actividad y calendario
 */
session_start();
require_once '../../db/connection.php';
require_once '../../includes/content_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="text-align: center; padding: 40px; color: #ff8a00;">Debes iniciar sesión para ver tu progreso.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Liberar bloqueo de sesión para permitir otras peticiones paralelas
session_write_close();

// 1. Estadísticas de palabras guardadas
$stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_words = $result->fetch_assoc()['total_words'];
$stmt->close();

// 2. Textos subidos
$total_texts = getTotalUserTexts($user_id);

// 3. Tiempo de lectura acumulado
$total_reading_seconds = 0;
$stmt_read = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM reading_time WHERE user_id = ?");
if ($stmt_read) {
    $stmt_read->bind_param("i", $user_id);
    $stmt_read->execute();
    $res_read = $stmt_read->get_result();
    $row_read = $res_read->fetch_assoc();
    $total_reading_seconds = $row_read['total_seconds'] ?? 0;
    $stmt_read->close();
}
$reading_h = floor($total_reading_seconds / 3600);
$reading_m = floor(($total_reading_seconds % 3600) / 60);
$reading_time = "{$reading_h}h {$reading_m}m";

// 4. Tiempo de práctica acumulado
$total_practice_seconds = 0;
$stmt_prac = $conn->prepare("SELECT SUM(duration_seconds) as total_seconds FROM practice_time WHERE user_id = ?");
if ($stmt_prac) {
    $stmt_prac->bind_param("i", $user_id);
    $stmt_prac->execute();
    $res_prac = $stmt_prac->get_result();
    $row_prac = $res_prac->fetch_assoc();
    $total_practice_seconds = $row_prac['total_seconds'] ?? 0;
    $stmt_prac->close();
}
$practice_h = floor($total_practice_seconds / 3600);
$practice_m = floor(($total_practice_seconds % 3600) / 60);
$practice_time = "{$practice_h}h {$practice_m}m";

// 5. Textos completados (100%)
$read_texts_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as read_count FROM reading_progress WHERE user_id = ? AND percent >= 100");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$read_texts_count = $result->fetch_assoc()['read_count'];
$stmt->close();

// Manejo de peticiones AJAX para progreso de lectura (Legacy support)
if (isset($_GET['text_id']) || isset($_POST['text_id'])) {
    $text_id = isset($_GET['text_id']) ? intval($_GET['text_id']) : intval($_POST['text_id']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT percent, pages_read, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
        $stmt->bind_param('ii', $user_id, $text_id);
        $stmt->execute();
        $stmt->bind_result($percent, $pages_read, $read_count);
        if ($stmt->fetch()) {
            $pages_read_arr = json_decode((string)$pages_read, true) ?: [];
            header('Content-Type: application/json');
            echo json_encode(['percent' => intval($percent), 'pages_read' => $pages_read_arr, 'read_count' => intval($read_count)]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['percent' => 0, 'pages_read' => [], 'read_count' => 0]);
        }
        $stmt->close();
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['percent']) && isset($_POST['pages_read'])) {
        $percent = intval($_POST['percent']);
        $pages_read = $_POST['pages_read'];
        $finish = isset($_POST['finish']) ? intval($_POST['finish']) : 0;
        
        // Forzar updated_at para asegurar que se registra el cambio
        $now = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("SELECT percent, read_count FROM reading_progress WHERE user_id = ? AND text_id = ?");
        $stmt->bind_param('ii', $user_id, $text_id);
        $stmt->execute();
        $stmt->bind_result($old_percent, $old_read_count);
        
        if ($stmt->fetch()) {
            $stmt->close();
            $new_read_count = (int)$old_read_count;
            if ($finish === 1 || ($percent >= 100 && (int)$old_percent < 100)) {
                $new_read_count++;
            }
            
            $stmt2 = $conn->prepare("UPDATE reading_progress SET percent = ?, pages_read = ?, updated_at = ?, read_count = ? WHERE user_id = ? AND text_id = ?");
            $stmt2->bind_param('issiii', $percent, $pages_read, $now, $new_read_count, $user_id, $text_id);
            $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt->close();
            $init_read_count = ($percent >= 100 || $finish === 1) ? 1 : 0;
            $stmt2 = $conn->prepare("INSERT INTO reading_progress (user_id, text_id, percent, pages_read, updated_at, read_count) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param('iiissi', $user_id, $text_id, $percent, $pages_read, $now, $init_read_count);
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
    <div class="tab-header-container">
        <h3 id="progreso">📊 Mi Progreso</h3>
    </div>
    <!-- Grid de Estadísticas Principales -->
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
            <div class="stat-icon">📖</div>
            <div class="stat-number"><?= $reading_time ?></div>
            <div class="stat-label">Tiempo de Lectura</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-number"><?= $practice_time ?></div>
            <div class="stat-label">Tiempo de Práctica</div>
        </div>
    </div>

    <!-- Sección de Actividad Reciente -->
    <div class="progress-section">
        <h3>📈 Actividad Reciente - Práctica y Lectura</h3>
        
        <div class="progress-layout">
            <!-- Calendario de Actividad -->
            <div class="calendar-section">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="month-navigator">
                            <button class="calendar-nav-btn" onclick="previousMonth()" title="Mes anterior">‹</button>
                            <h2 class="current-month">Cargando...</h2>
                            <button class="calendar-nav-btn" onclick="nextMonth()" title="Mes siguiente">›</button>
                        </div>
                        <button class="calendar-nav-btn" onclick="updateCalendarNow()" title="Actualizar">🔄</button>
                    </div>
                    
                    <div class="calendar-grid">
                        <div class="day-header">Dom</div>
                        <div class="day-header">Lun</div>
                        <div class="day-header">Mar</div>
                        <div class="day-header">Mié</div>
                        <div class="day-header">Jue</div>
                        <div class="day-header">Vie</div>
                        <div class="day-header">Sáb</div>
                        <!-- Los días se cargan vía JS -->
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Práctica por Modo -->
            <div class="activity-section">
            <?php if ($total_words == 0 && $total_texts == 0): ?>
                <div style="text-align: center; padding: 20px; margin-bottom: 10px;">
                    <p style="margin-bottom: 15px; color: #6b7280; font-size: 0.95em;">Sube tu primer texto para comenzar</p>
                    <button onclick="loadTabContent('upload')" class="btn btn-primary">⬆ Subir Primer Texto</button>
                </div>
            <?php endif; ?>
            <h3>🎯 Progreso de Práctica por Modo</h3>
            <div class="practice-modes" id="practice-modes-container">
            <div style="text-align: center; padding: 20px; color: #6b7280;">
                <div class="loading-spinner"></div>
            <p>Cargando...</p>
            </div>
            </div>
            </div>
        </div>
    </div>

    <script>
    /**
     * Carga las estadísticas detalladas de práctica
     */
    async function loadPracticeStats() {
        try {
            const response = await fetch('practicas/get_practice_stats.php');
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
                    <?php if ($total_words == 0 && $total_texts == 0): ?>
                    html = '';
                    <?php else: ?>
                    html = `
                        <div style="text-align: center; padding: 40px; color: #6b7280;">
                            <p>Comienza a practicar para ver tu progreso aquí.</p>
                            <button onclick="loadTabContent('practice')" class="nav-btn primary" style="margin-top: 20px;">
                                🎯 Ir a Práctica
                            </button>
                        </div>
                    `;
                    <?php endif; ?>
                }
                
                if (container) container.innerHTML = html;
            }
        } catch (error) {
            const container = document.getElementById('practice-modes-container');
            if (container) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ff8a00;"><p>Error cargando estadísticas.</p></div>';
            }
        }
    }
    
    // Inicialización
    loadPracticeStats();
    
    if (typeof initializeCalendar === 'function') {
        initializeCalendar();
    } else {
        setTimeout(() => {
            if (typeof initializeCalendar === 'function') initializeCalendar();
        }, 1000);
    }
    </script>

</div>
