<?php
session_start();
require_once 'db/connection.php';

$is_guest = !isset($_SESSION['user_id']);
$user_id = $is_guest ? null : $_SESSION['user_id'];

$public_titles = [];
$private_titles = [];
$user_titles = [];
$text = "";

// VISITANTE: Mostrar títulos públicos más recientes 
if ($is_guest) {
    $result = $conn->query("SELECT t.id, t.title, t.title_translation, u.username FROM texts t JOIN users u ON t.user_id = u.id WHERE t.is_public = 1 ORDER BY t.created_at DESC LIMIT 6");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $public_titles[] = $row;
    }
    $result->close();
  }
} else {
  // USUARIO LOGUEADO: Mostrar sus textos recientes si no hay texto seleccionado
  if (!isset($_GET['text_id']) && !isset($_GET['public_text_id'])) {
    $stmt = $conn->prepare("SELECT id, title, title_translation FROM texts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $user_titles[] = $row;
    }
    $stmt->close();
  }

  // Mostrar texto privado
  if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
    $stmt = $conn->prepare("SELECT content, title FROM texts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $text_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($content, $title);
    if ($stmt->fetch()) {
      $text = $content;
      $current_text_title = $title;
    }
    $stmt->close();
  }

  // Mostrar texto público
  if (isset($_GET['public_text_id'])) {
    $public_id = intval($_GET['public_text_id']);
    $stmt = $conn->prepare("SELECT content, title FROM texts WHERE id = ? AND is_public = 1");
    $stmt->bind_param("i", $public_id);
    $stmt->execute();
    $stmt->bind_result($content, $title);
    if ($stmt->fetch()) {
      $text = $content;
      $current_text_title = $title;
    }
    $stmt->close();
  }
}

// Mostrar texto público (disponible para todos, incluidos invitados)
if (isset($_GET['public_text_id'])) {
  $public_id = intval($_GET['public_text_id']);
  $stmt = $conn->prepare("SELECT content, title FROM texts WHERE id = ? AND is_public = 1");
  $stmt->bind_param("i", $public_id);
  $stmt->execute();
  $stmt->bind_result($content, $title);
  if ($stmt->fetch()) {
    $text = $content;
    $current_text_title = $title;
  }
  $stmt->close();
}

// Mostrar todos los textos públicos cuando se solicite
if (isset($_GET['show_public_texts'])) {
  $result = $conn->query("SELECT t.id, t.title, u.username FROM texts t JOIN users u ON t.user_id = u.id WHERE t.is_public = 1 ORDER BY t.created_at DESC");
  if ($result) {
    $public_titles = []; // Resetear array
    while ($row = $result->fetch_assoc()) {
      $public_titles[] = $row;
    }
    $result->close();
  }
}

// Mostrar estadísticas de progreso cuando se solicite
$progress_data = [];
if (isset($_GET['show_progress']) && isset($_SESSION['user_id'])) {
  // Obtener estadísticas de palabras guardadas
  $stmt = $conn->prepare("SELECT COUNT(*) as total_words FROM saved_words WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $progress_data['total_words'] = $result->fetch_assoc()['total_words'];
  $stmt->close();

  // Obtener últimas palabras practicadas
  $stmt = $conn->prepare("SELECT word, translation, created_at FROM saved_words WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $progress_data['recent_words'] = [];
  while ($row = $result->fetch_assoc()) {
    $progress_data['recent_words'][] = $row;
  }
  $stmt->close();

  // Obtener total de textos del usuario
  $stmt = $conn->prepare("SELECT COUNT(*) as total_texts FROM texts WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $progress_data['total_texts'] = $result->fetch_assoc()['total_texts'];
  $stmt->close();

  // Obtener textos más recientes
  $stmt = $conn->prepare("SELECT title, created_at FROM texts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $progress_data['recent_texts'] = [];
  while ($row = $result->fetch_assoc()) {
    $progress_data['recent_texts'][] = $row;
  }
  $stmt->close();

  // --- NUEVO: Obtener progreso de práctica por modo ---
  $progress_data['practice'] = [
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
    $progress_data['practice'][$mode] = [
      'count' => intval($row['words']),
      'accuracy' => round(floatval($row['avg_accuracy']), 1)
    ];
    $total_exercises += intval($row['cnt']);
  }
  $progress_data['practice']['total_exercises'] = $total_exercises;
  $stmt->close();
}

// Obtener categorías para el formulario de subir texto
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = [];
if ($categories_result) {
  while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
  }
  $categories_result->close();
}

$conn->close();

$text = preg_replace('/(?<=[.?!])\s+/', "\n", $text);

function render_text_clickable($text)
{
  $sentences = preg_split('/(?<=[.?!])\s+|\n+/', $text);
  $pages = [];
  $currentPage = [];
  $wordCount = 0;

  foreach ($sentences as $sentence) {
    $sentence = trim($sentence); // Limpiar espacios
    if (empty($sentence)) continue; // Saltar oraciones vacías

    $wordsInSentence = str_word_count($sentence);
    // 50 palabras por página para oraciones más cortas
    if ($wordCount + $wordsInSentence > 120 && count($currentPage) > 0) {
      $pages[] = $currentPage;
      $currentPage = [];
      $wordCount = 0;
    }
    $currentPage[] = $sentence;
    $wordCount += $wordsInSentence;
  }

  if (count($currentPage) > 0) {
    $pages[] = $currentPage;
  }

  // Obtener el text_id del contexto actual
  $text_id = '';
  if (isset($_GET['text_id'])) {
    $text_id = intval($_GET['text_id']);
  } elseif (isset($_GET['public_text_id'])) {
    $text_id = intval($_GET['public_text_id']);
  }

  $output = '<div id="pages-container" data-total-pages="' . count($pages) . '" data-total-words="' . str_word_count(strip_tags($text)) . '" data-text-id="' . $text_id . '">';
  
  foreach ($pages as $index => $page) {
    $output .= '<div class="page' . ($index === 0 ? ' active' : '') . '">';
    foreach ($page as $sentence) {
      $words = preg_split('/(\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
      $output .= '<p class="paragraph">';
      foreach ($words as $word) {
        if (trim($word) === '') {
          $output .= $word;
        } else {
          $output .= '<span class="clickable-word">' . htmlspecialchars($word) . '</span>';
        }
      }
      $output .= '</p>';
      $output .= '<p class="translation"></p>';
    }
    $output .= '</div>';
  }
  
  // Solo mostrar paginación si hay más de una página
  if (count($pages) > 1) {
    $output .= '<div id="pagination-controls">
            <button id="prev-page" class="pagination-btn" disabled>◀ Anterior</button>
            <span class="page-info"><span id="page-number">1</span> / <span id="total-pages">' . count($pages) . '</span></span>
            <button id="next-page" class="pagination-btn">Siguiente ▶</button>
    </div>';
  }

  $output .= '</div>';

  // Sin área de traducción fija - usaremos tooltips

  // Pestaña del menú - siempre visible
  $output .= '<button onclick="window.toggleFloatingMenu(); event.stopPropagation();" id="menu-btn">☰</button>';
  
  // Menú desplegable - siempre visible pero oculto por CSS
  $output .= '<div id="submenu">
        <div class="submenu-item">
            <button onclick="showAllTranslations()" id="show-all-translations-btn" class="submenu-button">📖 Mostrar todas las traducciones</button>
        </div>
        <div class="submenu-item">
            <button onclick="toggleTranslations()" id="toggle-translations-btn" class="submenu-button translations">👁️ Ocultar Traducciones</button>
        </div>
        <div class="submenu-item">
            <button onclick="printFullTextWithTranslations()" class="submenu-button print">🖨️ Imprimir</button>
        </div>
        <div class="submenu-item">
            <button onclick="readCurrentParagraphTwice(); event.stopPropagation();" class="submenu-button double-read">🔊 Leer dos veces</button>
        </div>
        <div class="speed-control">
            <label>Velocidad:</label>
            <input type="range" id="rate" min="0.5" max="0.9" value="0.9" step="0.1" />
            <span id="rate-value">100%</span>
        </div>
    </div>';

  // Nuevo contenedor para el botón de play flotante, fuera de floating-menu
  $output .= '<div id="floating-play" style="display: block;">
        <button onclick="window.toggleFloatingPlayPause()" id="floating-btn" title="Iniciar lectura">▶️</button>
    </div>';

  return $output;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#1D3557">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="description" content="Aprende inglés leyendo textos con traducciones instantáneas">
  <title>LeerEntender - Aprende inglés leyendo</title>
  <!-- CSS Principal -->
  <link rel="stylesheet" href="css/common-styles.css">
  <link rel="stylesheet" href="css/modern-styles.css">
  <link rel="stylesheet" href="css/color-theme.css">
  <link rel="stylesheet" href="css/header-redesign.css">
  <link rel="stylesheet" href="css/text-styles.css">
  <link rel="stylesheet" href="css/floating-menu.css">
  <link rel="stylesheet" href="css/reading-styles.css">
  <link rel="stylesheet" href="css/practice-styles.css">
  <link rel="stylesheet" href="css/modal-styles.css">
  <link rel="stylesheet" href="css/tab-system.css">
      <link rel="stylesheet" href="css/mobile-ready.css">
    <link rel="stylesheet" href="css/landing-page.css">
    <link rel="stylesheet" href="css/index-page.css">
    <link rel="stylesheet" href="css/calendar-styles.css">

  <!-- Favicon -->
  <link rel="icon" href="img/aprender_ingles.gif" type="image/gif">
  <link href="https://fonts.googleapis.com/css2?family=Gruppo&display=swap" rel="stylesheet">

    <!-- Sistema de voz ResponsiveVoice unificado -->
    <script>
      window.userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
</head>

<body>

  <header class="header" id="main-header">
    <div class="nav-container">
      <div class="nav-left">
        <div class="brand-container">
          <a href="index.php" class="logo">
            <img src="img/aprendiendoIngles.png" alt="Logo" class="logo-img">
          </a>

        </div>
        <div class="brand-text">
          <h1>LeerEntender</h1>
          <div class="slogan">
            Leé en inglés y<br>comprendé en español al instante
          </div>
        </div>

      </div>


      <div class="nav-right" id="nav-menu">
        <a href="index.php" class="nav-btn">🏠 Inicio</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="user-dropdown">
            <button class="user-dropdown-btn">
              <span class="user-greeting">Hola <?= htmlspecialchars($_SESSION['username']) ?></span>
              <span class="dropdown-arrow">▼</span>
            </button>
            <div class="user-dropdown-menu">
              <a href="logueo_seguridad/logout.php" class="dropdown-item">
                <span class="dropdown-icon">🚪</span>
                Cerrar sesión
              </a>
            </div>
          </div>
        <?php else: ?>
          <a href="#caracteristicas" class="nav-btn">📚 Características</a>
          <button onclick="requireLoginForUpload()" class="nav-btn primary">⬆ Subir texto</button>
          <button id="login-btn" class="nav-btn">Iniciar sesión</button>
        <?php endif; ?>
      </div>

      <button class="mobile-menu-toggle" id="mobile-toggle">☰</button>
    </div>
  </header>



  <div class="main-container">
    <div id="text" class="reading-area" data-text-id="<?php if (isset($text_id)) { echo $text_id; } elseif (isset($public_id)) { echo $public_id; } else { echo ''; } ?>">

      <?php if (empty($text)): ?>
        <?php if (isset($_GET['show_upload']) && isset($_SESSION['user_id'])): ?>
          <!-- Mostrar formulario de subir texto -->
          <div id="upload-form-container" style="display: block;">
            <div class="reading-area">
              <h3>⬆ Subir nuevo texto</h3>
              <button onclick="window.location.href='index.php'" class="nav-btn" style="margin-bottom: 20px;">← Volver a la lista</button>

              <div id="upload-messages"></div>

              <form action="upload_text.php" method="post">
                <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: bold;">Título:</label>
                  <input type="text" name="title" id="title-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                  <small style="color: #666;">Si no llenas el título, se generará automáticamente con las primeras 3 palabras del texto</small>
                </div>

                <div style="margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: bold;">Contenido:</label>
                  <textarea name="content" id="content-input" rows="10" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>

                <div style="margin-bottom: 15px;">
                  <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_public" id="is_public">
                    <span style="font-weight: bold;">Texto público</span>
                  </label>
                </div>

                <div id="category_section" style="display: none; margin-bottom: 15px;">
                  <label style="display: block; margin-bottom: 5px; font-weight: bold;">Categoría:</label>
                  <select name="category_id" id="category_select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="0">-- Selecciona categoría --</option>
                    <?php foreach ($categories as $cat): ?>
                      <?php
                      // Separar el nombre en inglés y español
                      $parts = explode(' - ', $cat['name']);
                      $english = $parts[0] ?? '';
                      $spanish = $parts[1] ?? '';
                      
                      // Si no hay traducción, usar el nombre completo como inglés
                      if (empty($spanish)) {
                          $english = $cat['name'];
                          $spanish = '';
                      }
                      
                      // Formatear la opción
                      if (!empty($spanish)) {
                          $display_name = $english . ' - ' . $spanish;
                      } else {
                          $display_name = $english;
                      }
                      ?>
                      <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($display_name) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <button type="submit" class="nav-btn primary" style="padding: 10px 20px;">Subir texto</button>
              </form>
            </div>
          </div>
        <?php elseif (isset($_GET['practice']) && isset($_SESSION['user_id'])): ?>
          <!-- Modo práctica -->
          <div id="practice-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
              <h3>🎯 Practicar Vocabulario</h3>
              <a href="index.php" class="nav-btn" style="text-decoration: none;">✖️ Salir</a>
            </div>
            <div id="practice-content">
              <div style="text-align: center; padding: 40px; color: #6b7280;">
                <div>Cargando ejercicios...</div>
              </div>
            </div>
          </div>
        <?php elseif (isset($_GET['show_public_texts'])): ?>
          <h3><span style="color: #4A90E2;">📖</span> Todos los Textos Públicos</h3>

          <?php if (isset($_SESSION['user_id'])): ?>
            <div style="margin-bottom: 20px; text-align: center;">
              <a href="index.php?practice=1" class="nav-btn primary" style="padding: 10px 20px; text-decoration: none;">
                🧠 Reforzar Palabras Leídas
              </a>
            </div>
          <?php endif; ?>

          <?php if (!empty($public_titles)): ?>
            <ul class="text-list">
              <?php foreach ($public_titles as $pt): ?>
                <li class="text-item">
                  <div class="text-item-container">
                    <a href="index.php?public_text_id=<?= $pt['id'] ?>" class="text-title">
                      <span style="color: #6B7280;">📄</span>
                      <span class="title-english"><?= htmlspecialchars($pt['title']) ?></span>
                      <?php if (!empty($pt['title_translation'])): ?>
                        <span class="title-spanish" style="color: #eaa827; font-size: 0.9em; margin-left: 8px; font-weight: 500;">• <?= htmlspecialchars($pt['title_translation']) ?></span>
                      <?php else: ?>
                        <span class="title-spanish" style="color: #6b7280; font-size: 0.9em; margin-left: 8px;"></span>
                      <?php endif; ?>
                    </a>
                    <span class="text-author">autor: <?= htmlspecialchars($pt['username']) ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No hay textos públicos disponibles.</p>
          <?php endif; ?>
        <?php elseif (isset($_GET['show_progress']) && isset($_SESSION['user_id'])): ?>
          <!-- Página de Progreso -->
          <div class="progress-dashboard">
            <div class="header-controls">
              <h2>📊 Tu Progreso de Aprendizaje</h2>
            </div>

            <!-- Estadísticas Generales -->
            <div class="progress-stats-grid">
              <div class="progress-stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?= $progress_data['total_texts'] ?></div>
                <div class="stat-label">Textos Subidos</div>
              </div>

              <div class="progress-stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-number"><?= $progress_data['total_words'] ?></div>
                <div class="stat-label">Palabras Guardadas</div>
              </div>

              <div class="progress-stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?= $progress_data['practice']['total_exercises'] ?></div>
                <div class="stat-label">Ejercicios Completados</div>
                <div class="stat-note">Total de sesiones de práctica</div>
              </div>

              <div class="progress-stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-number">
                  <?php
                    $accs = array_filter([
                      $progress_data['practice']['selection']['accuracy'],
                      $progress_data['practice']['writing']['accuracy'],
                      $progress_data['practice']['sentences']['accuracy']
                    ], fn($a) => $a > 0);
                    echo count($accs) ? round(array_sum($accs)/count($accs), 1) . '%' : '0%';
                  ?>
                </div>
                <div class="stat-label">Precisión Global</div>
                <div class="stat-note">Promedio de precisión</div>
              </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="progress-sections">
              <div class="progress-section">
                <h3>📖 Textos Recientes</h3>
                <?php if (!empty($progress_data['recent_texts'])): ?>
                  <ul class="recent-items-list">
                    <?php foreach ($progress_data['recent_texts'] as $text): ?>
                      <li class="recent-item">
                        <div class="recent-item-content">
                          <span class="recent-title"><?= htmlspecialchars($text['title']) ?></span>
                          <span class="recent-date"><?= date('d/m/Y', strtotime($text['created_at'])) ?></span>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <div style="text-align: center; margin-top: 15px;">
                    <a href="my_texts.php" class="nav-btn">Ver todos mis textos →</a>
                  </div>
                <?php else: ?>
                  <p style="color: #6b7280; text-align: center; padding: 20px;">
                    No has subido textos aún. <a href="index.php?show_upload=1">Subir uno</a>
                  </p>
                <?php endif; ?>
              </div>

              <div class="progress-section">
                <h3>💬 Palabras Recientes</h3>
                <?php if (!empty($progress_data['recent_words'])): ?>
                  <ul class="recent-items-list">
                    <?php foreach ($progress_data['recent_words'] as $word): ?>
                      <li class="recent-item">
                        <div class="recent-item-content">
                          <span class="recent-word">
                            <span class="word-english"><?= htmlspecialchars($word['word']) ?></span>
                            <span class="word-spanish">→ <?= htmlspecialchars($word['translation']) ?></span>
                          </span>
                          <span class="recent-date"><?= date('d/m/Y', strtotime($word['created_at'])) ?></span>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <div style="text-align: center; margin-top: 15px;">
                    <a href="saved_words.php" class="nav-btn">Ver todas las palabras →</a>
                  </div>
                <?php else: ?>
                  <p style="color: #6b7280; text-align: center; padding: 20px;">
                    No has guardado palabras aún. Lee un texto y haz clic en las palabras para guardarlas.
                  </p>
                <?php endif; ?>
              </div>

              <!-- NUEVA SECCIÓN: Actividad semanal de práctica -->
           
            </div>

            <!-- Sección de Práctica Detallada -->
            <div class="practice-progress-section">
              <h3>🎯 Progreso de Práctica</h3>
              <div class="practice-modes-grid">
                <div class="practice-mode-card">
                  <div class="practice-mode-icon">🔤</div>
                  <h4>Modo Selección</h4>
                  <div class="practice-stats">
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['selection']['count'] ?></span>
                      <span class="practice-label">Palabras practicadas</span>
                    </div>
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['selection']['accuracy'] ?>%</span>
                      <span class="practice-label">Precisión</span>
                    </div>
                  </div>
                </div>
                <div class="practice-mode-card">
                  <div class="practice-mode-icon">✍️</div>
                  <h4>Modo Escritura</h4>
                  <div class="practice-stats">
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['writing']['count'] ?></span>
                      <span class="practice-label">Palabras escritas</span>
                    </div>
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['writing']['accuracy'] ?>%</span>
                      <span class="practice-label">Precisión</span>
                    </div>
                  </div>
                </div>
                <div class="practice-mode-card">
                  <div class="practice-mode-icon">📖</div>
                  <h4>Modo Frases</h4>
                  <div class="practice-stats">
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['sentences']['count'] ?></span>
                      <span class="practice-label">Frases completadas</span>
                    </div>
                    <div class="practice-stat">
                      <span class="practice-number"><?= $progress_data['practice']['sentences']['accuracy'] ?>%</span>
                      <span class="practice-label">Precisión</span>
                    </div>
                  </div>
                </div>
              </div>
              <div style="text-align: center; margin-top: 25px;">
                <a href="index.php?practice=1" class="nav-btn primary">🎯 Practicar Ahora</a>
              </div>
            </div>
          </div>
        <?php elseif ($is_guest): ?>
          <!-- Landing Page para visitantes -->
          <div class="landing-container">
            <!-- Sección Hero -->
            <section class="hero-section">
              <div class="hero-content">
                <div class="hero-main">
                  <h1 class="hero-title">Aprende Inglés <br><spam style="
     background: linear-gradient(to right, #ff6a00, #045d7c); /* colores del texto */
     -webkit-background-clip: text; /* recorta el fondo al texto */
     -webkit-text-fill-color: #bc8f8f00; /* oculta el color sólido y deja ver el degradado */
     font-weight: bold;
     text-shadow: 2px 2px 4px rgb(255 255 255 / 34%);
">Naturalmente</spam></h1>
                  <p class="hero-subtitle">Lee en inglés, entiende en español. 
                    Sin pausas.<br> Traducción instantánea mientras lees.</p>

                  <div class="hero-buttons">
                    <button id="login-btn-hero" class="btn-primary">Comenzar a Aprender Gratis</button>
                    <a href="demo.php" class="btn-secondary">👁️ Ver Demo</a>
                  </div>
                </div>
                
                <div class="hero-advertising">
                  <!-- //<h3 class="ad-title">Consume inglés</h3> -->
                  <div class="ad-space-main">
                   <strong style="  margin-bottom: -9px;">El inglés que se queda contigo</strong><br>
                    “Para aprender un idioma, lo fundamental es exponerse continuamente a él y comprenderlo. Si escuchas o lees sin entender, el aprendizaje no se produce. Comprender mientras te expones al idioma es la clave para asimilarlo de manera efectiva.”
                  </div>
                </div>
              </div>

              <div class="hero-features">
                <div class="hero-feature">
                  <span>✓</span> Prueba gratuita de 14 días
                </div>
                <div class="hero-feature">
                  <span>✓</span> No se requiere tarjeta de crédito
                </div>
                <div class="hero-feature">
                  <span>✓</span> Cancela en cualquier momento
                </div>
              </div>
            </section>
          </div>

          <!-- Sección de espacios publicitarios -->
          <!-- <section class="advertising-section">
            <div class="ad-container">
              <div class="ad-space">
                <strong>Espacio Publicitario 1</strong><br>
                Anuncio Destacado
              </div>
              <div class="ad-space">
                <strong>Espacio Publicitario 2</strong><br>
                Anuncio Destacado
              </div>
              <div class="ad-space">
                <strong>Espacio Publicitario 3</strong><br>
                Anuncio Destacado
              </div>
            </div>
          </section> -->

          <!-- Sección de características -->
          <section class="features-section" id="caracteristicas">
            <div class="features-container">
              <h2 class="features-title">Consume inglés y entiéndelo </h2>
              <p class="features-subtitle">Aprender inglés es más fácil cuando lo entiendes todo.</p>

              <div class="features-grid">
                <div class="feature-card">
                  <div class="feature-icon">📚</div>
                  <h3 class="feature-title">Lectura Interactiva</h3>
                  <p class="feature-description">Practica la lectura de textos auténticos con traducciones instantáneas y definiciones inteligentes.</p>
                </div>
                <div class="feature-card">
                  <div class="feature-icon">🎧</div>
                  <h3 class="feature-title">Inmersión Auditiva</h3>
                  <p class="feature-description">Mejora tu pronunciación y las habilidades de escucha con grabaciones de hablantes nativos.</p>
                </div>
                <div class="feature-card">
                  <div class="feature-icon">👥</div>
                  <h3 class="feature-title">Aprendizaje en Comunidad</h3>
                  <p class="feature-description">Conéctate con otros estudiantes y hablantes nativos alrededor del mundo.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Sección de proceso -->
          <section class="process-section" id="como-funciona">
            <div class="process-container">
              <h2 class="process-title">Cómo funciona LeerEntender</h2>
              <p class="process-subtitle">Nuestro método científicamente probado hace que el aprendizaje de idiomas sea eficiente, agradable y efectivo para estudiantes de todos los niveles.</p>

              <div class="process-steps">
                <div class="process-step">
                  <div class="step-number">01</div>
                  <h3 class="step-title">Elige tu camino</h3>
                  <p class="step-description">Plantea y controla tus metas de aprendizaje en inglés desde la pestaña de progreso, con opciones para mostrar traducciones según tu nivel. Aprende a tu ritmo y mejora constantemente.</p>
                </div>
                <div class="process-step">
                  <div class="step-number">02</div>
                  <h3 class="step-title">Sumérgete y practica</h3>
                  <p class="step-description">Participa con contenido auténtico, ejercicios interactivos y escenarios del mundo real para desarrollar habilidades prácticas.</p>
                </div>
                <div class="process-step">
                  <div class="step-number">03</div>
                  <h3 class="step-title">Sigue y mejora</h3>
                  <p class="step-description">Monitoriza tu progreso con análisis detallados y adapta tu viaje de aprendizaje basándote en tus conocimientos.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Sección de testimonios -->
          <section class="testimonials-section" id="testimonios">
            <div class="testimonials-container">
              <h2 class="testimonials-title">Amado por estudiantes de idiomas en todo el mundo</h2>
              <p class="testimonials-subtitle">Únete a miles de estudiantes exitosos que han transformado sus habilidades lingüísticas con LeerEntender.</p>

              <div class="testimonials-grid">
                <div class="testimonial-card">
                  <div class="testimonial-stars">★★★★★</div>
                  <p class="testimonial-text">"LeerEntender me ayudó a hablar Inglés en solo 3 meses. La función de lectura interactiva es un cambio absoluto de juego!"</p>
                  <div class="testimonial-author">Sarah Chen</div>
                  <div class="testimonial-role">Profesora de Negocios</div>
                </div>
                <div class="testimonial-card">
                  <div class="testimonial-stars">★★★★★</div>
                  <p class="testimonial-text">"Me encanta poder consumir inglés y entenderlo al instante, sin perder tiempo. Los ejercicios de comprensión son motivadores y comprensivos cada día."</p>
                  <div class="testimonial-author">Marcus Rodriguez</div>
                  <div class="testimonial-role">Estudiante Universitario</div>
                </div>
                <div class="testimonial-card">
                  <div class="testimonial-stars">★★★★★</div>
                  <p class="testimonial-text">"Perfecta para aprender sobre la marcha. Lo he usado para aprender inglés básico para mis viajes."</p>
                  <div class="testimonial-author">Emma Thompson</div>
                  <div class="testimonial-role">Blogger de Viajes</div>
                </div>
              </div>
            </div>
          </section>

          <!-- Sección de precios -->
          <!-- <section class="pricing-section" id="precios">
            <div class="pricing-container">
              <h2 class="pricing-title">Elige tu plan de aprendizaje</h2>
              <p class="pricing-subtitle">Comienza gratis y actualiza cuando estés listo para desbloquear funciones avanzadas y acelerar tu progreso.</p>

              <div class="pricing-grid">
                <div class="pricing-card">
                  <h3 class="pricing-plan">Gratis</h3>
                  <div class="pricing-price">$0</div>
                  <div class="pricing-period">para siempre</div>
                  <ul class="pricing-features">
                    <li>5 lecciones por día</li>
                    <li>Seguimiento básico de progreso</li>
                    <li>Acceso a la comunidad</li>
                    <li>1 idioma</li>
                  </ul>
                  <button class="pricing-button" onclick="document.getElementById('login-btn').click()">Comenzar</button>
                </div>

                <div class="pricing-card featured">
                  <div class="pricing-badge">Más Popular</div>
                  <h3 class="pricing-plan">Pro</h3>
                  <div class="pricing-price">$9.99</div>
                  <div class="pricing-period">por mes</div>
                  <ul class="pricing-features">
                    <li>Lecciones ilimitadas</li>
                    <li>Análisis avanzados</li>
                    <li>Modo sin conexión</li>
                    <li>Todos los idiomas</li>
                    <li>Soporte prioritario</li>
                  </ul>
                  <button class="pricing-button">Iniciar Prueba Gratuita</button>
                </div>

                <div class="pricing-card">
                  <h3 class="pricing-plan">Equipos</h3>
                  <div class="pricing-price">$19.99</div>
                  <div class="pricing-period">por usuarios/mes</div>
                  <ul class="pricing-features">
                    <li>Todo en Pro</li>
                    <li>Gestión de equipos</li>
                    <li>Contenido personalizado</li>
                    <li>Soporte dedicado</li>
                    <li>Análisis de uso</li>
                  </ul>
                  <button class="pricing-button">Contactar Ventas</button>
                </div>
              </div>
            </div>
          </section> -->

          <!-- Footer -->
          <footer class="footer">
            <div class="footer-container">
              <div class="footer-section">
                <h3>🌟LeerEntender</h3>
                <p>La forma más efectiva de aprender idiomas a través de lectura inmersiva y personalización con IA.</p>
              </div>
              <div class="footer-section">
                <h3>Producto</h3>
                <ul>
                  <li><a href="#caracteristicas">Características</a></li>
                  <li><a href="index.php?show_public_texts=1">Idiomas</a></li>
                  <li><a href="#testimonios">Comunidad</a></li>
                </ul>
              </div>
              <div class="footer-section">
                <h3>Soporte</h3>
                <ul>
                  <li><a href="#como-funciona">Centro de Ayuda</a></li>
                  <li><a href="#">Comunidad</a></li>
                  <li><a href="#">Carrera</a></li>
                </ul>
              </div>
              <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                  <li><a href="#">Acerca de</a></li>
                  <li><a href="#">Privacidad</a></li>
                  <li><a href="#">Carrera</a></li>
                </ul>
              </div>
            </div>
            <div class="footer-bottom">
              <p>Únete a más de 100,000 estudiantes que ya han descubierto el poder del aprendizaje inmersivo de idiomas.</p>
            </div>
          </footer>
        <?php else: ?>
          <!-- Dashboard de usuario logueado -->
          <div class="user-dashboard">
            <!-- Navegación de pestañas -->
            <div class="tab-navigation" style="display: flex; align-items: center;">
              <button onclick="loadTabContent('progress')" class="tab-btn active" data-tab="progress">
                📊 Progreso
              </button>
              <button onclick="loadTabContent('my-texts')" class="tab-btn" data-tab="my-texts">
                📋 Textos
              </button>
              <button onclick="loadTabContent('saved-words')" class="tab-btn" data-tab="saved-words">
                📚 Palabras
              </button>
              <button onclick="loadTabContent('practice')" class="tab-btn" data-tab="practice">
                🎯 Práctica
              </button>
              <button onclick="loadTabContent('upload')" class="tab-btn" data-tab="upload">
                ⬆ Subir
              </button>
              <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <button onclick="window.location.href='admin_categories.php'" class="tab-btn" data-tab="admin-categories" style="background: #dc2626; color: white;">
                  ⚙️ Admin
                </button>
              <?php endif; ?>
              <div style="flex:1;"></div>
              <button onclick="exitTabs()" class="exit-tab-btn" title="Salir de las pestañas">☰</button>
            </div>

            <!-- Contenedor dinámico para pestañas -->
            <div id="tab-content">
              <div class="tab-header">
                <h2 id="main-title">Subir nuevo texto</h2>
              </div>
              <?php if (!empty($user_titles)): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                  <div style="color: #6b7280; font-weight: 500;">
                    <span style="color: #4A90E2; font-weight: 600;"><?= count($user_titles) ?></span> textos recientes
                  </div>
                  <div>
                    <button onclick="loadTabContent('my-texts')" class="nav-btn" style="font-size: 0.9rem; padding: 8px 16px;">
                      Ver todos →
                    </button>
                  </div>
                </div>

                <ul class="text-list modern-text-list">
                  <?php foreach ($user_titles as $ut): ?>
                    <li class="text-item modern-text-item">
                      <div class="text-icon">
                        📄
                      </div>
                      <a href="index.php?text_id=<?= $ut['id'] ?>" class="text-title modern-text-title">
                        <span class="title-english"><?= htmlspecialchars($ut['title']) ?></span>
                        <?php if (!empty($ut['title_translation'])): ?>
                          <span class="title-spanish" style="color: #eaa827; font-size: 0.9em; margin-left: 8px; font-weight: 500;">• <?= htmlspecialchars($ut['title_translation']) ?></span>
                        <?php else: ?>
                          <span class="title-spanish" style="color: #6b7280; font-size: 0.9em; margin-left: 8px;"></span>
                        <?php endif; ?>
                      </a>
                      <div class="text-actions">
                        <span class="text-status status-private">
                          Privado
                        </span>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                  <div style="font-size: 3rem; margin-bottom: 20px;">📚</div>
                  <h3 style="margin-bottom: 10px; color: #374151;">No has subido ningún texto todavía</h3>
                  <p style="margin-bottom: 30px;">¡Comienza tu viaje de aprendizaje subiendo tu primer texto!</p>
                  <button onclick="loadTabContent('upload')" class="nav-btn primary" style="padding: 15px 30px;">
                    ⬆ Subir mi primer texto
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <?= render_text_clickable($text) ?>
      <?php endif; ?>

    </div>
  </div>

  <div id="end-message" style="display:none; margin-top: 20px; font-size: 18px; color: green; font-weight: bold; text-align: center;"></div>

  <div id="login-modal" class="fixed z-1000 bg-modal" style="display:none; width:100%; height:100%; top:0; left:0;">
    <div class="bg-white p-25 mx-auto relative rounded-12 shadow" style="width:350px;">
      <button id="close-login-modal" class="absolute" style="top:10px; right:15px; background:none; border:none; font-size:20px; cursor:pointer; color:#999;">&times;</button>
      <h2 class="text-center mb-20 color-dark fs-24">🔐 Iniciar sesión</h2>
      <form id="login-form">
        <div class="mb-15">
          <label class="block mb-5 fw-600 color-gray">📧 Email:</label>
          <input type="email" name="email" required class="w-100 p-10 rounded-8 fs-16" style="border:2px solid #e0e0e0; box-sizing:border-box;">
        </div>
        <div class="mb-20">
          <label class="block mb-5 fw-600 color-gray">🔒 Contraseña:</label>
          <div style="position: relative;">
            <input type="password" name="password" id="login-password" required class="w-100 p-10 rounded-8 fs-16" style="border:2px solid #e0e0e0; box-sizing:border-box; width: 90%;">
            <span id="togglePasswordLoginModal" class="password-toggle-icon"></span>
          </div>
          <div class="text-right mt-5">
            <a href="#" onclick="showForgotPasswordModal(); return false;" class="color-blue fs-14" style="text-decoration: none;">¿Olvidaste tu contraseña?</a>
          </div>
        </div>
        <button type="submit" class="w-100 p-12 rounded-8 fw-600 fs-16" style="background:linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%); color:white; border:none; cursor:pointer;">Entrar</button>
        <div id="login-error" class="color-red mt-10 p-8 bg-white rounded-8" style="display:none;"></div>
      </form>
      <hr class="mt-15 mb-15">
      <p class="text-center mt-10 mb-10 color-gray">¿No tienes cuenta?</p>
      <a href="#" onclick="showRegisterModal(); return false;" class="block text-center bg-blue color-white p-10 rounded-8 mt-10" style="text-decoration: none;">Registrarse</a>
    </div>
  </div>

  <!-- Modal de Olvidé mi Contraseña -->
  <div id="forgot-password-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000;">
    <div style="background:#fff; padding:20px; width:350px; margin:40px auto; position:relative; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-height:90vh; overflow-y:auto;">
      <button id="close-forgot-password-modal" style="position:absolute; top:10px; right:15px; background:none; border:none; font-size:20px; cursor:pointer; color:#999;">✕</button>
      <h2 style="text-align:center; margin-bottom:15px; color:#333; font-size:22px;">❓ Olvidé mi Contraseña</h2>
      <form id="forgot-password-form">
        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:3px; font-weight:600; color:#555; font-size:14px;">📧 Email:</label>
          <input type="email" name="email" required style="width:100%; padding:8px; border:2px solid #e0e0e0; border-radius:6px; font-size:14px; box-sizing:border-box;">
        </div>
        <button type="submit" style="width:100%; padding:10px; background:#3B82F6; color:white; border:none; border-radius:6px; font-size:16px; font-weight:600; cursor:pointer;">Enviar enlace de restablecimiento</button>
      </form>
      <div id="forgot-password-messages" style="margin-top:10px; padding:8px; border-radius:5px; display:none; font-size:14px;"></div>
    </div>
  </div>

  <!-- Modal de restablecer contraseña (nuevo) -->
  <div id="reset-password-modal" class="fixed z-1000 bg-modal" style="display:none; width:100%; height:100%; top:0; left:0;">
    <div class="bg-white p-25 mx-auto relative rounded-12 shadow" style="width:350px;">
      <button id="close-reset-password-modal" class="absolute" style="top:10px; right:15px; background:none; border:none; font-size:20px; cursor:pointer; color:#999;">&times;</button>
      <div id="reset-password-modal-content">
        <!-- El contenido de restablecer_contrasena.php se cargará aquí -->
      </div>
    </div>
  </div>

  <!-- Modal de registro -->
  <div id="register-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000;">
    <div style="background:#fff; padding:20px; width:350px; margin:40px auto; position:relative; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-height:90vh; overflow-y:auto;">
      <button id="close-register-modal" style="position:absolute; top:10px; right:15px; background:none; border:none; font-size:20px; cursor:pointer; color:#999;">✕</button>
      <h2 style="text-align:center; margin-bottom:15px; color:#333; font-size:22px;">📝 Crear cuenta</h2>
      <form id="register-form">
        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:3px; font-weight:600; color:#555; font-size:14px;">👤 Usuario:</label>
          <input type="text" name="username" required style="width:100%; padding:8px; border:2px solid #e0e0e0; border-radius:6px; font-size:14px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:3px; font-weight:600; color:#555; font-size:14px;">📧 Email:</label>
          <input type="email" name="email" required style="width:100%; padding:8px; border:2px solid #e0e0e0; border-radius:6px; font-size:14px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
          <label style="display:block; margin-bottom:3px; font-weight:600; color:#555; font-size:14px;">🔒 Contraseña:</label>
          <div style="position: relative;">
            <input type="password" name="password" id="register-password" required style="width:90%; padding:8px; border:2px solid #e0e0e0; border-radius:6px; font-size:14px; box-sizing:border-box;">
            <span id="togglePasswordRegisterModal" class="password-toggle-icon"></span>
          </div>
        </div>
        <div style="margin-bottom:15px;">
          <label style="display:block; margin-bottom:3px; font-weight:600; color:#555; font-size:14px;">🔒 Confirmar:</label>
          <div style="position: relative;">
            <input type="password" name="confirm_password" id="confirm-password" required style="width:90%; padding:8px; border:2px solid #e0e0e0; border-radius:6px; font-size:14px; box-sizing:border-box;">
            <span id="toggleConfirmPasswordRegisterModal" class="password-toggle-icon"></span>
          </div>
        </div>
        <button type="submit" style="width:100%; padding:10px; background:#3B82F6; color:white; border:none; border-radius:6px; font-size:16px; font-weight:600; cursor:pointer;">Crear cuenta</button>
      </form>
      <div id="register-error" style="color:#dc3545; margin-top:10px; padding:8px; background:#ffeaea; border-radius:5px; display:none; font-size:14px;"></div>
      <div id="register-success" class="register-success-tooltip"></div>
    </div>
  </div>

  <!-- Formulario de subir texto (integrado) -->
  <div id="upload-form-container" style="display:none;">
    <div class="reading-area">
      <h3>⬆ Subir nuevo texto</h3>
      <button id="back-to-list" class="nav-btn" style="margin-bottom: 20px;">← Volver a la lista</button>

      <div id="upload-messages"></div>

      <form action="upload_text.php" method="post">
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Título:</label>
          <input type="text" name="title" id="title-input" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          <small style="color: #666;">Si no llenas el título, se generará automáticamente con las primeras 3 palabras del texto</small>
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Contenido:</label>
          <textarea name="content" id="content-input" rows="10" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="is_public" id="is_public">
            <span style="font-weight: bold;">Texto público</span>
          </label>
        </div>

        <div id="category_section" style="display: none; margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Categoría:</label>
          <select name="category_id" id="category_select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="0">-- Selecciona categoría --</option>
            <?php foreach ($categories as $cat): ?>
              <?php
              // Separar el nombre en inglés y español
              $parts = explode(' - ', $cat['name']);
              $english = $parts[0] ?? '';
              $spanish = $parts[1] ?? '';
              
              // Si no hay traducción, usar el nombre completo como inglés
              if (empty($spanish)) {
                  $english = $cat['name'];
                  $spanish = '';
              }
              
              // Formatear la opción
              if (!empty($spanish)) {
                  $display_name = $english . ' - ' . $spanish;
              } else {
                  $display_name = $english;
              }
              ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($display_name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="nav-btn primary" style="padding: 10px 20px;">Subir texto</button>
      </form>
    </div>
  </div>

  <script>
    // Inicializar tooltip y botón flotante al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
      createTooltip();

      // Mostrar botón flotante si hay texto con párrafos
      setTimeout(() => {
        const paragraphs = document.querySelectorAll('.paragraph');
        if (paragraphs.length > 0) {
          showFloatingButton();
          updateFloatingButton();
        }
      }, 500);



      // Detectar parámetro ?tab en la URL
      const urlParams = new URLSearchParams(window.location.search);
      let tab = urlParams.get('tab');
      const resetToken = urlParams.get('token'); // Detectar token de restablecimiento

      if (resetToken) {
        // Si hay un token de restablecimiento, mostrar el modal de restablecimiento de contraseña
        // Esperar a que modal-functions.js esté completamente cargado
        setTimeout(() => {
          if (typeof window.showResetPasswordModal === 'function') {
            window.showResetPasswordModal(resetToken);
          } else {
            console.warn('showResetPasswordModal no está disponible aún');
          }
        }, 500);
      } else if (tab === 'texts') {
        tab = 'my-texts';
        loadTabContent(tab);
      } else if (tab && ['progress','my-texts','saved-words','practice','upload'].includes(tab)) {
        loadTabContent(tab);
      } else {
        // Solo cargar pestañas si no estamos viendo un texto específico
        const isViewingText = window.location.search.includes('text_id=') || window.location.search.includes('public_text_id=');
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        
        if (isLoggedIn && !isViewingText) {
          loadTabContent('progress');
        }
      }
    });

    document.getElementById('login-btn')?.addEventListener('click', () => {
      document.getElementById('login-modal').style.display = 'block';
    });

    document.getElementById('login-btn-hero')?.addEventListener('click', () => {
      document.getElementById('login-modal').style.display = 'block';
    });

    document.getElementById('my-texts-btn')?.addEventListener('click', () => {
      document.getElementById('login-modal').style.display = 'block';
    });

    document.getElementById('public-texts-btn')?.addEventListener('click', () => {
      window.location.href = 'index.php?show_public_texts=1';
    });

    // Función para mostrar modal de registro
    // Los event listeners se manejan en js/modal-functions.js
    window.showRegisterModal = function() {
      document.getElementById('login-modal').style.display = 'none';
      document.getElementById('register-modal').style.display = 'block';
    }

    // toggleFullscreen definida en floating-menu.js

    // Función para cargar textos públicos
    function loadPublicTexts() {
      fetch('index.php?show_public_texts=1')
        .then(response => response.text())
        .then(html => {
          // Extraer solo el contenido del div #text
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const textContent = doc.getElementById('text');
          if (textContent) {
            document.getElementById('text').innerHTML = textContent.innerHTML;
          }
        })
        .catch(error => {
          document.getElementById('text').innerHTML = '<p>Error cargando textos públicos.</p>';
        });
    }

    // Crear tooltip flotante para traducciones
    window.createTooltip = function() {
      if (!document.getElementById('word-tooltip')) {
        const tooltip = document.createElement('div');
        tooltip.id = 'word-tooltip';
        tooltip.style.cssText = `
            position: fixed;
            background-color: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            z-index: 10000;
            display: none;
            max-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            pointer-events: none;
            border: 1px solid #555;
        `;
        document.body.appendChild(tooltip);
      }
    }

    window.showTooltip = function(element, text) {
      createTooltip();
      const tooltip = document.getElementById('word-tooltip');
      const rect = element.getBoundingClientRect();

      // Pausar lectura si está activa (TEMPORALMENTE DESHABILITADO PARA DEBUG)
      let wasReading = false;
      /* if (typeof window.isCurrentlyReading !== 'undefined' && window.isCurrentlyReading && !window.isCurrentlyPaused) {
          if (typeof window.pauseSpeech === 'function') {
              window.pauseSpeech();
              wasReading = true;
          }
      } */

      tooltip.textContent = text;
      tooltip.style.display = 'block';

      // Calcular posición centrada encima del elemento
      const tooltipWidth = tooltip.offsetWidth;
      const left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
      const top = rect.top - tooltip.offsetHeight - 10;

      tooltip.style.left = Math.max(10, left) + 'px';
      tooltip.style.top = Math.max(10, top) + 'px';

      // Configurar eventos para reanudar lectura cuando el mouse salga
      let mouseLeaveTimeout;

      element.addEventListener('mouseleave', function handleMouseLeave() {
        mouseLeaveTimeout = setTimeout(() => {
          tooltip.style.display = 'none';

          // Reanudar lectura si estaba leyendo
          if (wasReading && typeof window.resumeSpeech === 'function') {
            window.resumeSpeech();
          }

          // Remover listener para evitar acumulación
          element.removeEventListener('mouseleave', handleMouseLeave);
        }, 100); // Pequeño delay para suavizar
      });

      // Si el mouse entra de nuevo al elemento, cancelar el timeout
      element.addEventListener('mouseenter', function handleMouseEnter() {
        clearTimeout(mouseLeaveTimeout);
        // Remover listener una vez usado
        element.removeEventListener('mouseenter', handleMouseEnter);
      });

      // Auto ocultar después de 6 segundos como respaldo
      setTimeout(() => {
        if (tooltip.style.display !== 'none') {
          tooltip.style.display = 'none';
          if (wasReading && typeof window.resumeSpeech === 'function') {
            window.resumeSpeech();
          }
        }
      }, 6000);
    }

    window.hideTooltip = function() {
      const tooltip = document.getElementById('word-tooltip');
      if (tooltip) {
        tooltip.style.display = 'none';
      }
    }

    // Variables globales para controlar visibilidad de traducciones y estado de lectura
    // translationsVisible definido en floating-menu.js
    window.isCurrentlyReading = false;
    window.isCurrentlyPaused = false;
    window.userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    window.lastReadParagraphIndex = 0;
    window.lastReadPageIndex = 0;

    // Parar lectura al salir de la página
    window.addEventListener('beforeunload', function() {
      if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
      }
    });



    // Función mejorada de impresión con todo el texto y traducciones
    window.printFullTextWithTranslations = async function() {
      const pages = document.querySelectorAll('.page');
      if (pages.length === 0) {
        window.print();
        return;
      }

      // Obtener título del texto
      const textTitle = '<?= isset($current_text_title) ? htmlspecialchars($current_text_title) : "Texto" ?>';

      let printContent = '<div style="font-family: Arial, sans-serif; line-height: 1.8; max-width: 800px; margin: 0 auto;">';
      printContent += `<h1 style="text-align: center; margin-bottom: 10px; font-size: 24px;">LeerEntender</h1>`;
      printContent += `<h2 style="text-align: center; margin-bottom: 40px; font-size: 18px; color: #666;">${textTitle}</h2>`;

      // Mostrar mensaje de carga
      const loadingWindow = window.open('', '_blank');
      loadingWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head><title>Generando impresión...</title></head>
        <body style="font-family: Arial; text-align: center; padding: 50px;">
            <h2>Generando traducciones para impresión...</h2>
            <p>Por favor espera mientras preparamos el documento.</p>
        </body>
        </html>
    `);

      // Procesar cada página
      for (let pageIndex = 0; pageIndex < pages.length; pageIndex++) {
        const page = pages[pageIndex];
        const paragraphs = page.querySelectorAll('.paragraph');

        // Procesar cada párrafo
        for (let idx = 0; idx < paragraphs.length; idx++) {
          const paragraph = paragraphs[idx];
          const text = paragraph.textContent.trim();

          if (text) {
            printContent += `<p style="margin-bottom: 5px; font-size: 16px; font-weight: normal;">${text}</p>`;

            // Obtener traducción
            try {
              const response = await fetch('traduciones/translate.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'word=' + encodeURIComponent(text)
              });
              const data = await response.json();

              if (data.translation) {
                printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">${data.translation}</p>`;
              } else {
                printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">[Sin traducción disponible]</p>`;
              }
            } catch (error) {
              printContent += `<p style="font-style: italic; color: #666; margin-bottom: 25px; font-size: 14px;">[Error al obtener traducción]</p>`;
            }
          }
        }

        if (pageIndex < pages.length - 1) {
          printContent += '<div style="page-break-after: always;"></div>';
        }
      }

      printContent += '</div>';

      // Cerrar ventana de carga y abrir documento final
      loadingWindow.close();

      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>LeerEntender - ${textTitle}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.8; 
                    margin: 40px 20px; 
                    color: #333;
                }
                @media print {
                    body { margin: 20px; }
                    .page-break { page-break-after: always; }
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
      printWindow.document.close();
      printWindow.print();
    }

    // Funciones para el menú flotante
    window.showFloatingButton = function() {
      const floatingMenu = document.getElementById('floating-menu');
      if (floatingMenu) {
        floatingMenu.style.display = 'block';

        // Mostrar botón de continuar si hay un párrafo anterior guardado
        if (window.lastReadParagraphIndex > 0) {
          const continueBtn = document.getElementById('continue-btn-container');
          if (continueBtn) {
            continueBtn.style.opacity = '1';
            continueBtn.style.transform = 'translateY(0)';
          }
        }
      }
    }

    window.hideFloatingButton = function() {
      const floatingMenu = document.getElementById('floating-menu');
      if (floatingMenu) {
        floatingMenu.style.display = 'none';
      }
    }

    window.updateFloatingButton = function() {
      const floatingBtn = document.getElementById('floating-btn');
      if (!floatingBtn) return;

      if (window.isCurrentlyReading && !window.isCurrentlyPaused) {
        floatingBtn.textContent = '⏸️';
        floatingBtn.title = 'Pausar lectura';
      } else {
        floatingBtn.textContent = '▶️';
        floatingBtn.title = window.isCurrentlyPaused ? 'Continuar lectura' : 'Iniciar lectura';
      }
    }

    // toggleFloatingPlayPause está definido en floating-menu.js

    // Función para continuar desde el último párrafo leído
    window.continueFromLastParagraph = function() {
      if (typeof window.startReadingFromParagraph === 'function') {
        window.startReadingFromParagraph(window.lastReadParagraphIndex, window.lastReadPageIndex);
      } else {
        // Fallback a lectura normal desactivado para evitar auto-inicio inesperado
        // El usuario iniciará manualmente con el botón de play
      }
    }

    // Funciones para mostrar/ocultar el menú desplegable
    // menuVisible definido en floating-menu.js

    // Agregar eventos de hover al menú flotante
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(() => {
        const floatingMenu = document.getElementById('floating-menu');
        const submenu = document.getElementById('floating-submenu');
        const continueBtn = document.getElementById('continue-btn-container');
        let menuTimeout;

        if (floatingMenu && submenu) {
          // Mostrar menú al hacer hover
          floatingMenu.addEventListener('mouseenter', function() {
            clearTimeout(menuTimeout);
            submenu.style.opacity = '1';
            submenu.style.transform = 'translateY(0)';
            submenu.style.pointerEvents = 'auto';

            // Mostrar botón de continuar si existe último párrafo
            if (lastReadParagraphIndex > 0 && continueBtn) {
              continueBtn.style.opacity = '1';
              continueBtn.style.transform = 'translateY(0)';
            }
          });

          // Ocultar menú con delay al salir del hover
          floatingMenu.addEventListener('mouseleave', function() {
            menuTimeout = setTimeout(() => {
              submenu.style.opacity = '0';
              submenu.style.transform = 'translateY(20px)';
              submenu.style.pointerEvents = 'none';

              // Ocultar botón de continuar al salir del hover
              if (continueBtn) {
                continueBtn.style.opacity = '0';
                continueBtn.style.transform = 'translateY(20px)';
              }
            }, 1000); // Delay de 1 segundo para poder hacer clic
          });

          // Mantener menú visible si hacemos hover sobre él
          submenu.addEventListener('mouseenter', function() {
            clearTimeout(menuTimeout);
          });

          submenu.addEventListener('mouseleave', function() {

            menuTimeout = setTimeout(() => {
              submenu.style.opacity = '0';
              submenu.style.transform = 'translateY(20px)';
              submenu.style.pointerEvents = 'none';

              if (continueBtn) {
                continueBtn.style.opacity = '0';
                continueBtn.style.transform = 'translateY(20px)';
              }
            }, 500); // Delay más corto al salir del menú mismo
          });
        }
      }, 600);
    });

    // Función para guardar palabras traducidas
    window.saveTranslatedWord = async function(word, translation, sentence = '') {
      try {
        const formData = new FormData();
        formData.append('word', word);
        formData.append('translation', translation);
        formData.append('context', sentence);

        const response = await fetch('traduciones/save_translated_word.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        if (data.success) {
          // Palabra guardada exitosamente
        } else {
          // Error al guardar palabra
        }
      } catch (error) {
        // Error de red al guardar palabra
      }
    }

    // El manejador del formulario de login se encuentra en js/modal-functions.js
    // para mantener la lógica centralizada de tooltips y validaciones

    // Funcionalidad del formulario de subir texto
    document.getElementById('upload-text-btn-user')?.addEventListener('click', function() {
      window.location.href = 'index.php?show_upload=1';
    });

    document.getElementById('back-to-list')?.addEventListener('click', function() {
      hideUploadForm();
    });

    function showUploadForm() {
      const mainContainer = document.querySelector('.main-container');
      const uploadForm = document.getElementById('upload-form-container');

      mainContainer.style.display = 'none';
      uploadForm.style.display = 'block';
    }

    function hideUploadForm() {
      const mainContainer = document.querySelector('.main-container');
      const uploadForm = document.getElementById('upload-form-container');

      uploadForm.style.display = 'none';
      mainContainer.style.display = 'block';

      // Limpiar formulario
      document.getElementById('upload-form').reset();
      document.getElementById('upload-messages').innerHTML = '';
      document.getElementById('category_section').style.display = 'none';
      // Asegurar que el select de categorías esté reseteado
      document.getElementById('category_select').value = '0';
    }

    // Manejar checkbox de texto público
    document.getElementById('is_public')?.addEventListener('change', function() {
      const categorySection = document.getElementById('category_section');
      const categorySelect = document.getElementById('category_select');
      
      if (this.checked) {
        categorySection.style.display = 'block';
      } else {
        categorySection.style.display = 'none';
        // Resetear el valor del select cuando se desmarca texto público
        categorySelect.value = '0';
      }
    });

    // Manejar envío del formulario
    document.getElementById('upload-form')?.addEventListener('submit', async function(e) {
      e.preventDefault();

      const formData = new FormData();
      const title = document.getElementById('title-input').value.trim();
      const content = document.getElementById('content-input').value.trim();
      const isPublic = document.getElementById('is_public').checked;
      const categoryId = isPublic ? document.getElementById('category_select').value : 0;

      // Validación del frontend
      const messagesDiv = document.getElementById('upload-messages');
      
      if (!content) {
        messagesDiv.innerHTML = '<div style="color: red; margin-bottom: 15px;">Debes incluir contenido para el texto.</div>';
        return;
      }
      
      if (isPublic && categoryId === '0') {
        messagesDiv.innerHTML = '<div style="color: red; margin-bottom: 15px;">Debes seleccionar una categoría para el texto público.</div>';
        return;
      }

      // Generar título automático si no se proporcionó
      let finalTitle = title;
      if (!finalTitle && content) {
        const words = content.split(/\s+/).slice(0, 3);
        finalTitle = words.join(' ').replace(/[^\w\s-]/g, '').substring(0, 50);
        if (!finalTitle) {
          finalTitle = 'Texto sin título';
        }
      }

      formData.append('title', finalTitle);
      formData.append('content', content);
      formData.append('category_id', categoryId);
      if (isPublic) {
        formData.append('is_public', '1');
      }

      try {
        const response = await fetch('ajax_upload_text.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        const messagesDiv = document.getElementById('upload-messages');

        if (data.success) {
          messagesDiv.innerHTML = '<div style="color: green; margin-bottom: 15px;">' + data.message + '. Redirigiendo...</div>';
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          messagesDiv.innerHTML = '<div style="color: red; margin-bottom: 15px;">' + data.message + '</div>';
        }
      } catch (error) {
        const messagesDiv = document.getElementById('upload-messages');
        messagesDiv.innerHTML = '<div style="color: red; margin-bottom: 15px;">Error de conexión. Inténtalo de nuevo.</div>';
      }
    });

    // Cargar práctica si estamos en modo práctica
    if (window.location.search.includes('practice=1')) {
      // Esperar a que se cargue practice-functions.js
      if (typeof window.loadPracticeMode === 'function') {
        window.loadPracticeMode();
      } else {
        // Si no está cargado, esperar un poco y reintentar
        setTimeout(() => {
          if (typeof window.loadPracticeMode === 'function') {
            window.loadPracticeMode();
          } else {
            const practiceContent = document.getElementById('practice-content');
            if (practiceContent) {
              practiceContent.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                  <h3 style="color: #dc2626;">Error de carga</h3>
                  <p>No se pudieron cargar los ejercicios. Recarga la página.</p>
                  <button onclick="window.location.reload()" class="nav-btn">Recargar</button>
                </div>
              `;
            }
          }
        }, 100);
      }
    }

    // Esta función ya no se usa - ahora está en practice-functions.js
    function initializePractice(words) {
      // No hacer nada - la lógica está en practice-functions.js
      return;
    }
    
    // Función obsoleta - conservo solo la estructura para evitar errores
    function initializePracticeOld(words) {
      const practiceHTML = `
        <style>
            .mode-selector {
                display: flex;
                gap: 15px;
                margin-bottom: 30px;
                justify-content: center;
            }
            
            .mode-btn {
                padding: 12px 24px;
                border: 2px solid #3b82f6;
                border-radius: 8px;
                background: white;
                color: #3b82f6;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s;
            }
            
            .mode-btn.active {
                background: #3b82f6;
                color: white;
            }
            
            .mode-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            }
            
            .exercise-card {
                background: white;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }
            
            .sentence {
                font-size: 20px;
                line-height: 1.6;
                margin-bottom: 25px;
                text-align: center;
                color: #1f2937;
            }
            
            .options {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-bottom: 25px;
            }
            
            .option-btn {
                padding: 12px 20px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                background: white;
                color: #374151;
                cursor: pointer;
                font-size: 16px;
                transition: all 0.3s;
            }
            
            .option-btn:hover {
                border-color: #3b82f6;
                background: #f0f9ff;
            }
            
            .option-btn.selected {
                background: #3b82f6;
                color: white;
                border-color: #3b82f6;
            }
            
            .option-btn.correct {
                background: #dc2626;
                color: white;
                border-color: #dc2626;
            }
            
            .option-btn.incorrect {
                background: #60a5fa;
                color: white;
                border-color: #60a5fa;
            }
            
            .write-input {
                width: 100%;
                padding: 15px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                font-size: 18px;
                text-align: center;
                margin-bottom: 20px;
            }
            
            .write-input:focus {
                outline: none;
                border-color: #3b82f6;
            }
            
            .check-btn {
                background: #3b82f6;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .check-btn:hover {
                background: #2563eb;
                transform: translateY(-2px);
            }
            
            .feedback {
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                text-align: center;
                font-weight: bold;
            }
            
            .feedback.correct {
                background: #dbeafe;
                color: #1e40af;
                border: 2px solid #3b82f6;
            }
            
            .feedback.incorrect {
                background: #dbeafe;
                color: #1d4ed8;
                border: 2px solid #60a5fa;
            }
            
            .next-btn {
                background: #3b82f6;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                margin-top: 15px;
                transition: all 0.3s;
            }
            
            .next-btn:hover {
                background: #2563eb;
                transform: translateY(-2px);
            }
            
            .progress {
                background: #f3f4f6;
                border-radius: 8px;
                height: 8px;
                margin-bottom: 20px;
            }
            
            .progress-bar {
                background: #3b82f6;
                height: 100%;
                border-radius: 8px;
                transition: width 0.3s;
            }
            
            .stats {
                display: flex;
                justify-content: space-around;
                margin-top: 20px;
                text-align: center;
                font-size: 14px;
                opacity: 0.7;
            }
            
            .stat {
                background: #f8fafc;
                padding: 10px;
                border-radius: 8px;
                flex: 1;
                margin: 0 5px;
            }
            
            .stat-number {
                font-size: 18px;
                font-weight: bold;
                color: #3b82f6;
            }
        </style>
        
        <div class="mode-selector">
            <button class="mode-btn active" onclick="setPracticeMode('selection')">
                📝 Selección múltiple
            </button>
            <button class="mode-btn" onclick="setPracticeMode('writing')">
                ✍️ Escribir palabra
            </button>
        </div>

        <div class="progress">
            <div class="progress-bar" id="practice-progress-bar" style="width: 0%"></div>
        </div>

        <div class="exercise-card" id="practice-exercise-card">
            <!-- El ejercicio se cargará aquí dinámicamente -->
        </div>

        <div class="stats" id="practice-stats-container">
            <div class="stat">
                <div class="stat-number" id="practice-current-question">0</div>
                <div>Completadas</div>
            </div>
            <div class="stat">
                <div class="stat-number" id="practice-total-questions">${words.length}</div>
                <div>Total</div>
            </div>
            <div class="stat">
                <div class="stat-number" id="practice-correct-count">0</div>
                <div>Correctas</div>
            </div>
            <div class="stat">
                <div class="stat-number" id="practice-incorrect-count">0</div>
                <div>Fallos</div>
            </div>
        </div>
    `;

      const practiceContent = document.getElementById('practice-content');
      if (practiceContent) {
        practiceContent.innerHTML = practiceHTML;
      }

      // Inicializar variables de práctica
      window.practiceWords = [...words]; // Copia del array original
      window.practiceRemainingWords = [...words]; // Palabras que faltan por responder correctamente
      window.practiceCurrentMode = 'selection';
      window.practiceCurrentQuestionIndex = 0;
      window.practiceCorrectAnswers = 0;
      window.practiceIncorrectAnswers = 0;
      window.practiceAnswered = false;
      window.practiceCurrentSentenceData = null;

      loadPracticeQuestion();
    }

    // Funciones globales de práctica
    window.setPracticeMode = function(mode) {
      window.practiceCurrentMode = mode;
      document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');

      // Reiniciar ejercicio
      window.practiceRemainingWords = [...window.practiceWords];
      window.practiceCurrentQuestionIndex = 0;
      window.practiceCorrectAnswers = 0;
      window.practiceIncorrectAnswers = 0;
      window.practiceAnswered = false;
      updatePracticeStats();
      loadPracticeQuestion();
    }

    window.loadPracticeQuestion = function() {
      if (window.practiceRemainingWords.length === 0) {
        showPracticeResults();
        return;
      }

      // Ocultar header durante el ejercicio
      const header = document.querySelector('header');
      if (header) {
        header.style.display = 'none';
      }

      // Seleccionar palabra aleatoria de las palabras restantes
      const randomIndex = Math.floor(Math.random() * window.practiceRemainingWords.length);
      const currentWord = window.practiceRemainingWords[randomIndex];
      window.practiceCurrentWordIndex = randomIndex;

      window.practiceCurrentSentenceData = generatePracticeSentence(currentWord.word);
      window.practiceAnswered = false;

      const instruction = window.practiceCurrentMode === 'selection' ? 'Elige la palabra correcta:' : 'Escribe la palabra correcta:';
      let html = `
        <div style="text-align: center; margin-bottom: 15px; font-weight: bold; color: #374151;">
            ${instruction}
        </div>
        <div class="sentence" id="english-sentence">${window.practiceCurrentSentenceData.en}</div>
        <div class="spanish-translation" id="spanish-translation">
        </div>
    `;

      if (window.practiceCurrentMode === 'selection') {
        const distractors = generatePracticeDistractors(currentWord.word);
        const allOptions = [...distractors, currentWord.word];

        // Mezclar opcionefs 
        for (let i = allOptions.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [allOptions[i], allOptions[j]] = [allOptions[j], allOptions[i]];
        }

        html += '<div class="options">';
        // Botón de pista al principio
        html += `<button class="option-btn" onclick="showPracticeHint('${currentWord.word}')" style="background: #f3f4f6; color: #6b7280; border-color: #d1d5db;">💡 Pista</button>`;
        allOptions.forEach(option => {
          html += `<button class="option-btn" onclick="playClickSound(); selectPracticeOption('${option}', '${currentWord.word}')">${option}</button>`;
        });
        html += '</div>';
      } else {
        html += `
            <input type="text" class="write-input" id="practice-write-answer" placeholder="Escribe la palabra que falta..." onkeypress="if(event.key==='Enter') checkPracticeWriteAnswer('${currentWord.word}')">
            <div style="text-align: center;">
                <button class="check-btn" onclick="checkPracticeWriteAnswer('${currentWord.word}')">Verificar respuesta</button>
                <button class="check-btn" onclick="showPracticeHint('${currentWord.word}')" style="background: #f3f4f6; color: #6b7280; border: 2px solid #d1d5db; margin-left: 10px;">💡 Pista</button>
            </div>
        `;
      }

      const practiceExerciseCard = document.getElementById('practice-exercise-card');
      if (practiceExerciseCard) {
        practiceExerciseCard.innerHTML = html;
      }

      if (window.practiceCurrentMode === 'writing') {
        document.getElementById('practice-write-answer')?.focus();
      }

      // Agregar listener para mostrar header al hacer clic fuera de botones
      setTimeout(() => {
        const practiceCard = document.getElementById('practice-exercise-card');
        if (practiceCard) {
          practiceCard.addEventListener('click', function(e) {
            // Si el clic no es en un botón o input, mostrar header
            if (!e.target.matches('button, input, .option-btn, .check-btn')) {
              const header = document.querySelector('header');
              if (header) {
                header.style.display = '';
              }
            }
          });
        }
      }, 100);
    }

    function generatePracticeSentence(word) {
      // Buscar la palabra con su contexto
      const practiceWord = window.practiceWords.find(w => w.word === word);
      if (!practiceWord) {
        return {
          en: `The ${word} is important.`,
          es: `El ${word} es importante.`
        };
      }

      const translation = practiceWord.translation;
      const context = practiceWord.context;

      // Si hay contexto real, usarlo
      if (context && context.trim().length > 0 && context !== `The ${word} is important.`) {
        // Usar la frase real donde aparece la palabra
        const sentenceWithGap = context.replace(new RegExp(`\\b${word}\\b`, 'gi'), '___');

        // Preparar la traducción con la palabra resaltada
        const highlightedTranslation = `<span style="background: #ff6f0074; padding: 2px 3px; border-radius: 4px; font-weight: bold; color: #92400e;">${translation}</span>`;

        return {
          en: sentenceWithGap,
          es: `Traduciendo frase...`, // Temporal
          original_en: context,
          word: word,
          translation: translation,
          needsTranslation: true,
          highlightedWord: highlightedTranslation
        };
      }

      // Fallback a plantillas genéricas si no hay contexto
      const templates = [{
          en: `I can see the ${word} from here.`,
          es: `Puedo ver ${translation} desde aquí.`
        },
        {
          en: `The ${word} is very important today.`,
          es: `${translation} es muy importante hoy.`
        },
        {
          en: `This ${word} helps me learn English.`,
          es: `Este ${translation} me ayuda a aprender inglés.`
        }
      ];

      const selectedTemplate = templates[Math.floor(Math.random() * templates.length)];

      return {
        en: selectedTemplate.en.replace(word, '___'),
        es: selectedTemplate.es,
        original_en: selectedTemplate.en,
        word: word,
        translation: translation
      };
    }

    function translateContextAndCreateExercise(originalContext, sentenceWithGap, word, translation) {
      // Por ahora, crear una traducción temporal mientras se traduce en background
      const tempTranslation = `Frase: ${translation} aparece aquí.`;

      const exerciseData = {
        en: sentenceWithGap,
        es: tempTranslation,
        original_en: originalContext,
        word: word,
        translation: translation,
        isTranslating: true
      };

      // Traducir la frase completa de forma asíncrona
      fetch('traduciones/translate.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'word=' + encodeURIComponent(originalContext)
        })
        .then(res => res.json())
        .then(data => {
          if (data.translation) {
            // Resaltar la palabra traducida en la frase traducida
            const translatedSentence = data.translation;
            const highlightedTranslation = translatedSentence.replace(
              new RegExp(`\\b${translation}\\b`, 'gi'),
              `<span style="background: #ff6f0074; padding: 2px 3px; border-radius: 4px; font-weight: bold; color: #92400e;">${translation}</span>`
            );

            // Actualizar la frase en la interfaz si aún está visible
            setTimeout(() => {
              const translationElement = document.querySelector('.practice-spanish-sentence');
              if (translationElement) {
                translationElement.innerHTML = highlightedTranslation;
              }
            }, 100);
          }
        })
        .catch(() => {
          // Error al traducir contexto
        });

      return exerciseData;
    }

    function translatePracticeSentence(originalSentence, wordTranslation) {
      fetch('traduciones/translate.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'word=' + encodeURIComponent(originalSentence)
        })
        .then(res => res.json())
        .then(data => {
          if (data.translation) {
            // Resaltar la palabra traducida en la frase traducida
            let translatedSentence = data.translation;

            // Buscar y resaltar la palabra traducida (puede aparecer en diferentes formas)
            const highlightedTranslation = translatedSentence.replace(
              new RegExp(`\\b${wordTranslation}\\b`, 'gi'),
              `<span style="background: #ff6f0074; padding: 2px 3px; border-radius: 4px; font-weight: bold; color: #92400e;">${wordTranslation}</span>`
            );

            // Actualizar la traducción en la interfaz
            const translationElement = document.getElementById('spanish-translation');
            if (translationElement) {
              translationElement.innerHTML = highlightedTranslation;
            }
          }
        })
        .catch(() => {
          const translationElement = document.getElementById('spanish-translation');
          if (translationElement) {
            translationElement.innerHTML = `Frase que contiene "<span style="background: #ff6f0074; padding: 2px 3px; border-radius: 4px; font-weight: bold; color: #92400e;">${wordTranslation}</span>".`;
          }
        });
    }

    function generatePracticeDistractors(correctWord) {
      const allWords = window.practiceWords.filter(w => w.word !== correctWord).map(w => w.word);
      const commonWords = ['house', 'book', 'time', 'water', 'good', 'work', 'think', 'know', 'want', 'say'];

      let distractors = [];

      // Usar palabras del usuario primero (aleatorias)
      const shuffledWords = [...allWords].sort(() => Math.random() - 0.5);
      for (let i = 0; i < Math.min(3, shuffledWords.length); i++) {
        distractors.push(shuffledWords[i]);
      }

      // Completar con palabras comunes si es necesario
      while (distractors.length < 3) {
        const commonWord = commonWords[Math.floor(Math.random() * commonWords.length)];
        if (!distractors.includes(commonWord) && commonWord !== correctWord) {
          distractors.push(commonWord);
        }
      }

      return distractors;
    }

    // Función para reproducir sonido
    function playClickSound() {
      // Crear un sonido de clic suave
      const audioContext = new(window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
      gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.1);
    }

    window.selectPracticeOption = function(selected, correct) {
      if (window.practiceAnswered) return;

      window.practiceAnswered = true;
      const buttons = document.querySelectorAll('.option-btn');
      let selectedButton = null;

      buttons.forEach(btn => {
        btn.onclick = null; // Deshabilitar clicks
        if (btn.textContent === correct) {
          btn.classList.add('correct');
        } else if (btn.textContent === selected && selected !== correct) {
          btn.classList.add('incorrect');
        }

        if (btn.textContent === selected) {
          selectedButton = btn;
        }
      });

      const isCorrect = selected === correct;

      // Mostrar la traducción al responder
      showTranslationAfterAnswer();

      showQuickFeedback(selectedButton, isCorrect, correct);
    }

    window.checkPracticeWriteAnswer = function(correct) {
      if (window.practiceAnswered) return;

      const inputElement = document.getElementById('practice-write-answer');
      const userAnswer = inputElement.value.trim().toLowerCase();
      const isCorrect = userAnswer === correct.toLowerCase();

      window.practiceAnswered = true;
      inputElement.disabled = true;

      // Mostrar la traducción al responder
      showTranslationAfterAnswer();

      // Usar el mismo sistema de tooltip para modo escritura
      showQuickFeedback(inputElement, isCorrect, correct);
    }

    function showTranslationAfterAnswer() {
      const translationDiv = document.getElementById('spanish-translation');
      if (!translationDiv) return;

      // Mostrar el div de traducción
      translationDiv.style.display = 'block';
      translationDiv.innerHTML = 'Traduciendo...';

      // Si tenemos los datos de la frase, traducir
      if (window.practiceCurrentSentenceData && window.practiceCurrentSentenceData.needsTranslation) {
        translatePracticeSentence(
          window.practiceCurrentSentenceData.original_en,
          window.practiceCurrentSentenceData.translation
        );
      } else if (window.practiceCurrentSentenceData && window.practiceCurrentSentenceData.es) {
        // Si ya tenemos la traducción, mostrarla con la palabra resaltada
        const translation = window.practiceCurrentSentenceData.es;
        const wordTranslation = window.practiceCurrentSentenceData.translation;

        const highlightedTranslation = translation.replace(
          new RegExp(`\\b${wordTranslation}\\b`, 'gi'),
          `<span style="background: #ff6f0074; padding: 2px 3px; border-radius: 4px; font-weight: bold; color: #92400e;">${wordTranslation}</span>`
        );

        translationDiv.innerHTML = highlightedTranslation;
      }
    }

    // Nueva función de feedback rápido como tooltip
    function showQuickFeedback(buttonElement, isCorrect, correctWord) {
      const currentWord = window.practiceRemainingWords[window.practiceCurrentWordIndex];

      if (isCorrect) {
        window.practiceCorrectAnswers++;
        // Solo remover la palabra si es correcta
        window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1);
      } else {
        window.practiceIncorrectAnswers++;
        // Para respuestas incorrectas, mantener la palabra para repetir más tarde
        // Mover la palabra al final del array para que aparezca de nuevo
        const wordToRepeat = window.practiceRemainingWords.splice(window.practiceCurrentWordIndex, 1)[0];
        window.practiceRemainingWords.push(wordToRepeat);
      }

      // Crear tooltip encima del botón
      const tooltip = document.createElement('div');
      const rect = buttonElement.getBoundingClientRect();

      tooltip.style.cssText = `
        position: fixed;
        top: ${rect.top - 10}px;
        left: ${rect.left + (rect.width / 2)}px;
        transform: translateX(-50%) translateY(-100%);
        background: ${isCorrect ? '#ca123b' : '#2563eb'};
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        white-space: nowrap;
        pointer-events: none;
        animation: fadeInUp 0.3s ease;
    `;

      tooltip.innerHTML = `
        <div>${isCorrect ? '✓ Correcto' : '✗ Incorrecto'}</div>
        <div style="font-size: 12px; margin-top: 2px; opacity: 0.9;">
            "${correctWord}" → "${currentWord.translation}"
        </div>
    `;

      document.body.appendChild(tooltip);

      // Mostrar traducción completa simplificada
      setTimeout(() => {
        showSimplifiedTranslation(currentWord);
        document.body.removeChild(tooltip);
      }, 2000);

      updatePracticeStats();
    }

    function showSimplifiedTranslation(currentWord) {
      // Usar la traducción ya preparada en practiceCurrentSentenceData
      const spanishSentence = window.practiceCurrentSentenceData.es;

      const simplifiedFeedback = `
        <div style="margin-top: 20px; text-align: center;">
            <button class="next-btn" onclick="nextPracticeQuestion()" onkeydown="if(event.key==='Enter') nextPracticeQuestion()">Siguiente</button>
        </div>
    `;

      const practiceExerciseCard = document.getElementById('practice-exercise-card');
      if (practiceExerciseCard) {
        practiceExerciseCard.innerHTML += simplifiedFeedback;
      }

      // Agregar listener para Enter
      document.addEventListener('keydown', function practiceEnterHandler(e) {
        if (e.key === 'Enter') {
          nextPracticeQuestion();
          document.removeEventListener('keydown', practiceEnterHandler);
        }
      });
    }

    // CSS para animación
    const style = document.createElement('style');
    style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-90%);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(-100%);
        }
    }
`;
    document.head.appendChild(style);

    window.showPracticeHint = function(correctWord) {
      const hint = correctWord.substring(0, 2) + '...';

      // Mostrar pista en un elemento temporal
      const hintElement = document.createElement('div');
      hintElement.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #3b82f6;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        font-size: 18px;
        font-weight: bold;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
      hintElement.textContent = `Pista: ${hint}`;

      document.body.appendChild(hintElement);

      // Remover pista después de 2 segundos
      setTimeout(() => {
        document.body.removeChild(hintElement);
      }, 2000);
    }

    window.nextPracticeQuestion = function() {
      window.practiceCurrentQuestionIndex++;
      loadPracticeQuestion();
    }

    function updatePracticeStats() {
      const totalWords = window.practiceWords.length;
      const wordsCompleted = totalWords - window.practiceRemainingWords.length;

      document.getElementById('practice-current-question').textContent = wordsCompleted;
      document.getElementById('practice-correct-count').textContent = window.practiceCorrectAnswers;
      document.getElementById('practice-incorrect-count').textContent = window.practiceIncorrectAnswers;

      const progress = (wordsCompleted / totalWords) * 100;
      document.getElementById('practice-progress-bar').style.width = progress + '%';
    }

    function showPracticeResults() {
      // Mostrar header al finalizar
      const header = document.querySelector('header');
      if (header) {
        header.style.display = '';
      }

      const resultHtml = `
        <div style="text-align: center; padding: 40px;">
            <h3>🎉 ¡Ejercicio completado!</h3>
            <div style="font-size: 32px; margin: 20px 0; font-weight: bold; color: #3b82f6;">
                ${window.practiceCorrectAnswers} palabras aprendidas
            </div>
            <p>¡Excelente trabajo! Has completado todas las palabras correctamente.</p>
            <div style="margin-top: 30px;">
                <button class="next-btn" onclick="restartPracticeExercise()" style="margin-right: 15px;">Repetir ejercicio</button>
                <a href="my_texts.php" class="nav-btn">Volver a mis textos</a>
            </div>
        </div>
    `;

      const practiceExerciseCard = document.getElementById('practice-exercise-card');
      if (practiceExerciseCard) {
        practiceExerciseCard.innerHTML = resultHtml;
      }
    }

    window.restartPracticeExercise = function() {
      // Restaurar todas las palabras para nueva práctica
      window.practiceRemainingWords = [...window.practiceWords];

      // Mezclar palabras para nueva práctica
      for (let i = window.practiceRemainingWords.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [window.practiceRemainingWords[i], window.practiceRemainingWords[j]] = [window.practiceRemainingWords[j], window.practiceRemainingWords[i]];
      }

      window.practiceCurrentQuestionIndex = 0;
      window.practiceCorrectAnswers = 0;
      window.practiceIncorrectAnswers = 0;
      window.practiceAnswered = false;
      updatePracticeStats();
      loadPracticeQuestion();
    }
  </script>

    <script src="js/common-functions.js"></script>
    <script src="js/lector.js?v=dev2"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.userLoggedIn && typeof window.initLector === 'function') {
          try {
            window.initLector();
          } catch (e) {
            console.error("Error al ejecutar initLector:", e);
          }
        } else if (!window.userLoggedIn) {
          console.log("Lector no inicializado: usuario no logueado.");
        }
      });
    </script>
    <!-- Motor de lectura simplificado -->
    <script src="js/reading-engine.js?v=1"></script>
    <script src="js/practice-functions.js"></script>
    <script src="js/text-management.js"></script>
    <script src="js/modal-functions.js"></script>
    <script src="js/floating-menu.js"></script>
    <script src="js/upload-form.js"></script>
    <script src="js/header-functions.js"></script>
    <script src="js/calendar-functions.js"></script>
    <script src="js/multi-word-selection.js"></script>
  <script src="js/title-translation-functions.js"></script>
  <script src="logueo_seguridad/password_visibility.js"></script>
  
  <!-- Sistema de Límite de Traducciones -->
  <?php include 'dePago/limit_modal.php'; ?>
  <script src="dePago/limit_modal.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        setupPasswordVisibilityToggle('login-password', 'togglePasswordLoginModal');
        setupPasswordVisibilityToggle('register-password', 'togglePasswordRegisterModal');
        setupPasswordVisibilityToggle('confirm-password', 'toggleConfirmPasswordRegisterModal');
    });

    // Función para recuperar contraseña
    function showForgotPassword() {
      const email = prompt('Introduce tu email para recuperar la contraseña:');
      if (email && email.includes('@')) {
        alert('Se ha enviado un enlace de recuperación a ' + email + '\n(Funcionalidad en desarrollo)');
      } else if (email) {
        alert('Por favor introduce un email válido');
      }
    }

    // Función para requerir login para subir texto
    function requireLoginForUpload() {
      // Mostrar modal personalizado para registro
      const loginModal = document.getElementById('login-modal');
      const loginTitle = loginModal.querySelector('h2');
      const loginForm = loginModal.querySelector('#login-form');
      
      // Cambiar el título del modal
      loginTitle.innerHTML = '📝 ¡Crea tu cuenta para subir textos!';
      
      // Agregar mensaje informativo
      const existingMessage = loginModal.querySelector('.upload-info-message');
      if (!existingMessage) {
        const infoMessage = document.createElement('div');
        infoMessage.className = 'upload-info-message';
        infoMessage.style.cssText = 'background: #e6f3ff; color: #0066cc; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;';
        infoMessage.innerHTML = '✨ Crea una cuenta gratuita para subir tus propios textos y practicar vocabulario personalizado';
        loginForm.parentNode.insertBefore(infoMessage, loginForm);
      }
      
      // Mostrar el modal
      loginModal.style.display = 'block';
    }

    // Función para salir de las pestañas
    window.exitTabs = function() {
      // Mostrar header
      if (typeof window.showHeader === 'function') {
        window.showHeader();
      }
      
      // Ir a la página principal
      window.location.href = 'index.php';
    };

    // Sistema de pestañas dinámicas
    window.loadTabContent = function(tab) {
      const tabContent = document.getElementById('tab-content');
      const tabButtons = document.querySelectorAll('.tab-btn');
      
      // Ocultar menú flotante al entrar en cualquier pestaña
      const floatingMenu = document.getElementById('floating-menu');
      if (floatingMenu) floatingMenu.style.display = 'none';
      
      // Actualizar estados visuales de los botones
      tabButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tab) {
          btn.classList.add('active');
        }
      });
      
      // Ocultar header cuando se entra en las pestañas
      if (typeof window.hideHeader === 'function') {
        window.hideHeader();
      }
      
      // Mostrar loading
      tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;"><div style="font-size: 2rem; margin-bottom: 10px;">⏳</div><p>Cargando...</p></div>';
      
      // Mapear pestañas a archivos AJAX
      const tabFiles = {
        'progress': 'ajax_progress_content.php',
        'my-texts': 'ajax_my_texts_content.php',
        'saved-words': 'ajax_saved_words_content.php',
        'practice': 'ajax_practice_content.php',
        'upload': 'ajax_upload_content.php'
      };
      
      const ajaxFile = tabFiles[tab];
      if (!ajaxFile) {
        tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;"><p>Error: Pestaña no encontrada</p></div>';
        return;
      }
      
      // Cargar contenido vía AJAX (sin caché para asegurar datos frescos)
      fetch(ajaxFile, {
        cache: 'no-store',
        headers: {
          'Cache-Control': 'no-cache'
        }
      })
        .then(response => response.text())
        .then(data => {
          tabContent.innerHTML = data;
          
          // Ejecutar scripts que puedan estar en el contenido cargado
          const scripts = tabContent.querySelectorAll('script');
          scripts.forEach(script => {
            if (script.innerHTML.trim()) {
              eval(script.innerHTML);
            }
          });
          
          // Inicializar traducciones si es necesario

          
          // NUEVO: Traducir contextos de palabras guardadas si es la pestaña correspondiente
          if (tab === 'saved-words' && typeof window.translateAllContextsForSavedWords === 'function') {
            setTimeout(window.translateAllContextsForSavedWords, 100);
          }
          
          // Configurar detección de clics fuera de las pestañas para mostrar header
          setupTabClickDetection();
        })
        .catch(error => {
          tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;"><p>Error cargando contenido. Por favor, intenta de nuevo.</p></div>';
        });
    }
    
    // Función para salir de las pestañas y mostrar el header
    function exitTabs() {
      // Mostrar header
      if (typeof window.showHeader === 'function') {
        window.showHeader();
      }
      // Mostrar menú flotante al salir de las pestañas
      const floatingMenu = document.getElementById('floating-menu');
      if (floatingMenu) floatingMenu.style.display = 'block';
    }
    
    // Función para detectar clics fuera de las pestañas y mostrar header
    function setupTabClickDetection() {
      // Remover listener anterior si existe
      document.removeEventListener('click', handleTabAreaClick);
      
      // Agregar nuevo listener
      document.addEventListener('click', handleTabAreaClick);
    }
    
    function handleTabAreaClick(event) {
      const header = document.getElementById('main-header');
      
      // Si el header está oculto
      if (header && header.classList.contains('hidden')) {
        const clickedElement = event.target;
        
        // Lista de elementos que NO deben mostrar el header (solo enlaces y botones)
        const ignoreElements = [
          'a',           // Enlaces
          'button',      // Botones
          'input',       // Inputs
          'select',      // Selects
          'textarea',    // Textareas
          'label',       // Labels (para accesibilidad)
          '[onclick]',   // Elementos con onclick
          '[role="button"]', // Elementos con role button
          '.clickable',  // Elementos con clase clickable
          '.nav-btn',    // Botones de navegación
          '.tab-btn',    // Botones de pestañas
          '.dropdown',   // Dropdowns
          '.dropdown-content', // Contenido de dropdowns
          '.text-checkbox', // Checkboxes
          '.delete-btn', // Botones de eliminar
          '.primary',    // Botones primarios
          '.secondary'   // Botones secundarios
        ];
        
        let shouldIgnore = false;
        for (const selector of ignoreElements) {
          if (clickedElement.matches(selector) || clickedElement.closest(selector)) {
            shouldIgnore = true;
            break;
          }
        }
        
        // Si el clic NO fue en un enlace o botón, mostrar header
        if (!shouldIgnore) {
          if (typeof window.showHeader === 'function') {
            window.showHeader();
          }
        }
      }
    }
  </script>

  <!-- Footer simple -->
  <footer>
    <p>
      © 2024 LeerEntender - Aprende inglés leyendo | 📧 info@idoneoweb.es
    </p>
  </footer>

  <!-- Scripts globales para las pestañas -->
  <script>
    // ===== FUNCIONES GLOBALES PARA PESTAÑAS =====
    
    // Funciones para pestaña de Textos
    function toggleDropdown() {
      const dropdown = document.querySelector('.dropdown');
      if (dropdown) {
        dropdown.classList.toggle("show");
      }
    }
    
    function updateBulkActions() {
      const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]:checked');
      const dropdownBtn = document.getElementById('dropdownBtn');
      
      if (!dropdownBtn) return;

      if (checkboxes.length > 0) {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = `Acciones (${checkboxes.length}) ▼`;
        dropdownBtn.style.background = '#4A90E2';
        dropdownBtn.style.color = 'white';
        dropdownBtn.style.opacity = '1';
        dropdownBtn.style.cursor = 'pointer';
      } else {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = 'Acciones en lote ▼';
        dropdownBtn.style.background = '#f3f4f6';
        dropdownBtn.style.color = '#6b7280';
        dropdownBtn.style.opacity = '0.7';
        dropdownBtn.style.cursor = 'default';
      }
    }
    
    function selectAllTexts() {
      const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]');
      checkboxes.forEach(cb => cb.checked = true);
      updateBulkActions();
    }
    
    function unselectAllTexts() {
      const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]');
      checkboxes.forEach(cb => cb.checked = false);
      updateBulkActions();
    }
    
    function performBulkAction(action) {
      const checkboxes = document.querySelectorAll('input[name="selected_texts[]"]:checked');

      if (checkboxes.length === 0) {
        alert('Por favor, selecciona al menos un texto.');
        return;
      }

      if (action === 'print') {
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        const printUrl = 'print_texts.php?ids=' + selectedIds.join(',');
        window.open(printUrl, '_blank');
        return;
      }

      let confirmMessage = '';
      if (action === 'delete') {
        confirmMessage = `¿Estás seguro de que quieres eliminar ${checkboxes.length} texto(s)?`;
      } else if (action === 'make_public') {
        confirmMessage = `¿Estás seguro de que quieres hacer públicos ${checkboxes.length} texto(s)?`;
      }

      if (confirm(confirmMessage)) {
        // Usar AJAX en lugar de form.submit()
        const formData = new FormData();
        formData.append('action', action);
        
        checkboxes.forEach(checkbox => {
          formData.append('selected_texts[]', checkbox.value);
        });

        // Mostrar mensaje de carga
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
          messagesContainer.innerHTML = '<div style="background: #e6f3ff; color: #0066cc; padding: 10px; border-radius: 4px; margin-bottom: 20px;">Procesando...</div>';
        }

        fetch('ajax_my_texts_content.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mostrar mensaje de éxito
            if (messagesContainer) {
              messagesContainer.innerHTML = `<div style="background: #d1fae5; color: #eaa827; padding: 10px; border-radius: 4px; margin-bottom: 20px;">✅ ${data.message}</div>`;
            }
            
            // Recargar la pestaña para mostrar los cambios
            setTimeout(() => {
              loadTabContent('my-texts');
            }, 1500);
          } else {
            // Mostrar mensaje de error
            if (messagesContainer) {
              messagesContainer.innerHTML = `<div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 4px; margin-bottom: 20px;">❌ ${data.message}</div>`;
            }
          }
        })
        .catch(error => {
          if (messagesContainer) {
            messagesContainer.innerHTML = '<div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 4px; margin-bottom: 20px;">❌ Error de conexión. Por favor, intenta de nuevo.</div>';
          }
        });
      }
    }
    
    // Funciones para pestaña de Palabras
    function selectAllWords() {
      const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
      checkboxes.forEach(cb => cb.checked = true);
      updateBulkActionsWords();
    }
    
    function unselectAllWords() {
      const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
      checkboxes.forEach(cb => cb.checked = false);
      updateBulkActionsWords();
    }
    
    function updateBulkActionsWords() {
      const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');
      const dropdownBtn = document.getElementById('dropdownBtn');
      
      if (!dropdownBtn) return;

      if (checkboxes.length > 0) {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = `Acciones (${checkboxes.length}) ▼`;
        dropdownBtn.style.background = '#4A90E2';
        dropdownBtn.style.color = 'white';
        dropdownBtn.style.opacity = '1';
        dropdownBtn.style.cursor = 'pointer';
      } else {
        dropdownBtn.disabled = false;
        dropdownBtn.textContent = 'Acciones en lote ▼';
        dropdownBtn.style.background = '#f3f4f6';
        dropdownBtn.style.color = '#6b7280';
        dropdownBtn.style.opacity = '0.7';
        dropdownBtn.style.cursor = 'default';
      }
    }
    
    function toggleGroup(checkbox, groupId) {
      const group = document.getElementById(groupId);
      const groupCheckboxes = group.querySelectorAll('input[name="selected_words[]"]');
      
      groupCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
      });
      
      updateBulkActionsWords();
    }
    
    function performBulkActionWords(action) {
      const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');

      if (checkboxes.length === 0) {
        alert('Por favor, selecciona al menos una palabra.');
        return;
      }

      if (action === 'delete') {
        if (confirm(`¿Estás seguro de que quieres eliminar ${checkboxes.length} palabra(s)?`)) {
          // Usar AJAX en lugar de form.submit()
          const formData = new FormData();
          formData.append('action', action);
          
          checkboxes.forEach(checkbox => {
            formData.append('selected_words[]', checkbox.value);
          });

          // Mostrar mensaje de carga
          const messagesContainer = document.querySelector('.tab-content-wrapper');
          if (messagesContainer) {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loading-message';
            loadingDiv.style.cssText = 'background: #e6f3ff; color: #0066cc; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;';
            loadingDiv.textContent = 'Procesando...';
            messagesContainer.insertBefore(loadingDiv, messagesContainer.firstChild);
          }

          fetch('ajax_saved_words_content.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(html => {
            // Recargar la pestaña para mostrar los cambios
            loadTabContent('saved-words');
          })
          .catch(error => {
            const loadingDiv = document.getElementById('loading-message');
            if (loadingDiv) {
              loadingDiv.style.cssText = 'background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;';
              loadingDiv.textContent = '❌ Error de conexión. Por favor, intenta de nuevo.';
            }
          });
        }
      }
    }
    
    // Inicializar eventos para dropdowns cuando se carga contenido AJAX
    function initializeTabEvents() {
      // Cerrar dropdown al hacer clic fuera
      document.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.dropdown');
        
        if (dropdown && !dropdown.contains(event.target)) {
          dropdown.classList.remove('show');
        }
      });
    }
    
    // Llamar a initializeTabEvents cuando se carga una pestaña
    const originalLoadTabContent = window.loadTabContent;
    window.loadTabContent = function(tab) {
      originalLoadTabContent(tab);
      
      // Esperar a que se cargue el contenido y luego inicializar eventos
      setTimeout(() => {
        initializeTabEvents();
        if (document.querySelectorAll('input[name="selected_texts[]"]').length > 0) {
          updateBulkActions();
        }
        if (document.querySelectorAll('input[name="selected_words[]"]').length > 0) {
          updateBulkActionsWords();
        }
      }, 100);
    };
  </script>

  <!-- Al final del body, antes de cerrar -->
  <script src="js/public-texts-dropdown.js"></script>

  <!-- Función para traducir contextos de palabras guardadas -->
  <script>
    window.translateAllContextsForSavedWords = function() {
      document.querySelectorAll('.word-context').forEach(function(span) {
        const context = span.getAttribute('data-context');
        const translationDiv = span.nextElementSibling;
        if (context && translationDiv && translationDiv.classList.contains('context-translation')) {
          fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'text=' + encodeURIComponent(context) + '&target_lang=es'
          })
          .then(response => response.json())
          .then(data => {
            if (data && data.translation) {
              translationDiv.textContent = data.translation;
            } else {
              translationDiv.textContent = '[No se pudo traducir]';
            }
          })
          .catch(() => {
            translationDiv.textContent = '[Error de traducción]';
          });
        }
      });
    }
  </script>

  <?php if (isset($_GET['text_id']) || isset($_GET['public_text_id'])): ?>
    <!-- Sidebar de explicaciones -->
    <div class="explain-sidebar" id="explainSidebar">
      <div class="sidebar-header">
        <button class="close-sidebar" id="closeSidebar">×</button>
        <div class="word-info">
          <div class="word-display">
            <button class="pronounce-btn">🔊</button>
            <span class="selected-word" id="selectedWord">palabra</span>
            <span class="word-translation" id="wordTranslation">- traducción</span>
          </div>
        </div>
      </div>
      <div class="sidebar-content">
        <div class="explanation-section">
          <div class="section-header">
            <span class="section-icon">💡</span>
            <h3>Información</h3>
          </div>
          <div class="explanation-text" id="explanationText">
            <p>Haz clic en una palabra para ver su información.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Botón flotante de explicar -->
    <button class="explain-floating-btn" id="explainFloatingBtn">
      <span class="explain-icon">?</span>
    </button>

    <!-- Overlay para cerrar sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <link rel="stylesheet" href="css/explain-sidebar.css?v=2">
    <script src="js/explain-sidebar.js"></script>
  <?php endif; ?>
</body>

</html>
