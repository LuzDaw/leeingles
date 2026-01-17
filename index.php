<?php
session_start();
require_once 'db/connection.php';
require_once 'includes/content_functions.php';

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
  <title>LeeInglés - Aprende inglés leyendo y entendiéndolo</title>
  <!-- CSS Principal -->
  <link rel="stylesheet" href="css/common-styles.css">
  <link rel="stylesheet" href="css/modern-styles.css">
  <link rel="stylesheet" href="css/color-theme.css">
  <link rel="stylesheet" href="css/header-redesign.css">
  <link rel="stylesheet" href="css/text-styles.css">
  <link rel="stylesheet" href="css/floating-menu.css">
  <link rel="stylesheet" href="css/reading-styles.css">
  <link rel="stylesheet" href="practicas/css/practice-styles.css">
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
          <h1>LeeInglés</h1>
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
        <?php if (isset($_GET['practice']) && isset($_SESSION['user_id'])): ?>
          <!-- Modo práctica -->
          <div id="practice-container">
            <div class="practice-header">
              <h3>🎯 Practicar Vocabulario</h3>
              <a href="index.php" class="nav-btn no-underline">✖️ Salir</a>
            </div>
            <div id="practice-content">
              <div class="empty-state">
                <div>Cargando ejercicios...</div>
              </div>
            </div>
          </div>
        <?php elseif (isset($_GET['show_public_texts'])): ?>
          <h3><span class="color-blue">📖</span> Todos los Textos Públicos</h3>

          <?php if (isset($_SESSION['user_id'])): ?>
            <div class="public-texts-header">
              <a href="index.php?practice=1" class="nav-btn primary p-10-20 no-underline">
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
                      <span class="color-gray">📄</span>
                      <span class="title-english"><?= htmlspecialchars($pt['title']) ?></span>
                      <?php if (!empty($pt['title_translation'])): ?>
                        <span class="title-spanish color-orange fs-0-9 ml-8 fw-500">• <?= htmlspecialchars($pt['title_translation']) ?></span>
                      <?php else: ?>
                        <span class="title-spanish color-gray fs-0-9 ml-8"></span>
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
                  <div class="text-center mt-15">
                    <a href="my_texts.php" class="nav-btn">Ver todos mis textos →</a>
                  </div>
                <?php else: ?>
                  <p class="color-gray text-center p-20">
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
                  <div class="text-center mt-15">
                    <a href="saved_words.php" class="nav-btn">Ver todas las palabras →</a>
                  </div>
                <?php else: ?>
                  <p class="color-gray text-center p-20">
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
              <div class="text-center mt-25">
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
                  <h1 class="hero-title">Aprende Inglés <br><span class="hero-gradient-text">Naturalmente</span></h1>
                  <p class="hero-subtitle">Lee en inglés, entiende en español. 
                    Sin pausas.<br> Traducción instantánea mientras lees.</p>

                  <div class="hero-buttons">
                    <button id="login-btn-hero" class="btn-primary">Comenzar a Aprender Gratis</button>
                    <a href="demo.php" class="btn-secondary">👁️ Ver Demo</a>
                  </div>
                </div>
                
                <div class="hero-advertising">
                  <div class="ad-space-main">
                   <strong class="ad-space-text">El inglés que se queda contigo</strong><br>
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
              <h2 class="process-title">Cómo funciona LeeInglés</h2>
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
              <p class="testimonials-subtitle">Únete a miles de estudiantes exitosos que han transformado sus habilidades lingüísticas con LeeInglés.</p>

              <div class="testimonials-grid">
                <div class="testimonial-card">
                  <div class="testimonial-stars">★★★★★</div>
                  <p class="testimonial-text">"LeeInglés me ayudó a hablar Inglés en solo 3 meses. La función de lectura interactiva es un cambio absoluto de juego!"</p>
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
                <h3>🌟LeeInglés</h3>
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
            <div class="tab-navigation tab-nav-container">
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
              <button onclick="loadTabContent('account')" class="tab-btn" data-tab="account">
                👤 Cuenta
              </button>
              <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <button onclick="window.location.href='admin_categories.php'" class="tab-btn admin-tab-btn" data-tab="admin-categories">
                  ⚙️ Admin
                </button>
              <?php endif; ?>
              <div class="flex-1"></div>
              <button onclick="exitTabs()" class="exit-tab-btn" title="Salir de las pestañas">☰</button>
            </div>

            <!-- Contenedor dinámico para pestañas -->
            <div id="tab-content">
              <div class="tab-header">
                <h2 id="main-title">Subir nuevo texto</h2>
              </div>
              <?php if (!empty($user_titles)): ?>
                <div class="recent-texts-summary">
                  <div class="summary-text">
                    <span class="summary-count"><?= count($user_titles) ?></span> textos recientes
                  </div>
                  <div>
                    <button onclick="loadTabContent('my-texts')" class="nav-btn fs-0-9 p-8-16">
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
                          <span class="title-spanish color-orange fs-0-9 ml-8 fw-500">• <?= htmlspecialchars($ut['title_translation']) ?></span>
                        <?php else: ?>
                          <span class="title-spanish color-gray fs-0-9 ml-8"></span>
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
                <div class="empty-state">
                  <div class="empty-state-icon">📚</div>
                  <h3 class="empty-state-title">No has subido ningún texto todavía</h3>
                  <p class="mb-30">¡Comienza tu viaje de aprendizaje subiendo tu primer texto!</p>
                  <button onclick="loadTabContent('upload')" class="nav-btn primary p-15-30">
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

  <div id="end-message" class="end-message"></div>

  <div id="login-modal" class="modal-overlay">
    <div class="modal-container login-modal-container">
      <button id="close-login-modal" class="modal-close-x">&times;</button>
      <h2 class="modal-title">🔐 Iniciar sesión</h2>
      <form id="login-form">
        <div class="mb-15">
          <label class="modal-label">📧 Email:</label>
          <input type="email" name="email" required class="modal-input-field login-input">
        </div>
        <div class="mb-20">
          <label class="modal-label">🔒 Contraseña:</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="login-password" required class="modal-input-field w-90 login-input">
            <span id="togglePasswordLoginModal" class="password-toggle-icon"></span>
          </div>
          <div class="text-right mt-5">
            <a href="#" onclick="showForgotPasswordModal(); return false;" class="color-blue fs-14 forgot-password-link">¿Olvidaste tu contraseña?</a>
          </div>
        </div>
        <button type="submit" class="modal-submit-btn">Entrar</button>
        <div id="login-error" class="modal-error-msg"></div>
      </form>
      <hr class="mt-15 mb-15">
      <p class="text-center mt-10 mb-10 color-gray">¿No tienes cuenta?</p>
      <a href="#" onclick="showRegisterModal(); return false;" class="block text-center bg-blue color-white p-10 rounded-8 mt-10 register-btn-link">Registrarse</a>
    </div>
  </div>

  <!-- Modal de Olvidé mi Contraseña -->
  <div id="forgot-password-modal" class="modal-overlay">
    <div class="modal-container forgot-password-modal-container">
      <button id="close-forgot-password-modal" class="modal-close-x">✕</button>
      <h2 class="modal-title fs-22">❓ Olvidé mi Contraseña</h2>
      <form id="forgot-password-form">
        <div class="mb-12">
          <label class="modal-label">📧 Email:</label>
          <input type="email" name="email" required class="modal-input-field fs-14">
        </div>
        <button type="submit" class="modal-submit-btn">Enviar enlace de restablecimiento</button>
      </form>
      <div id="forgot-password-messages" class="modal-error-msg"></div>
    </div>
  </div>

  <!-- Modal de restablecer contraseña (nuevo) -->
  <div id="reset-password-modal" class="modal-overlay">
    <div class="modal-container login-modal-container">
      <button id="close-reset-password-modal" class="modal-close-x">&times;</button>
      <div id="reset-password-modal-content">
        <!-- El contenido de restablecer_contrasena.php se cargará aquí -->
      </div>
    </div>
  </div>

  <!-- Modal de registro -->
  <div id="register-modal" class="modal-overlay">
    <div class="modal-container register-modal-container">
      <button id="close-register-modal" class="modal-close-x">✕</button>
      <h2 class="modal-title fs-22">📝 Crear cuenta</h2>
      <form id="register-form">
        <div class="mb-12">
          <label class="modal-label">👤 Usuario:</label>
          <input type="text" name="username" required class="modal-input-field fs-14">
        </div>
        <div class="mb-12">
          <label class="modal-label">📧 Email:</label>
          <input type="email" name="email" required class="modal-input-field fs-14">
        </div>
        <div class="mb-12">
          <label class="modal-label">🔒 Contraseña:</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="register-password" required class="modal-input-field w-90 fs-14">
            <span id="togglePasswordRegisterModal" class="password-toggle-icon"></span>
          </div>
        </div>
        <button type="submit" class="modal-submit-btn">Crear cuenta</button>
      </form>
      <div id="register-error" class="modal-error-msg"></div>
      <div id="register-success" class="register-success-tooltip"></div>
    </div>
  </div>

    <script src="js/global-state.js"></script>
    <script src="js/common-functions.js"></script>
    <script src="js/index-tabs.js"></script>
    <script src="js/bulk-actions.js"></script>
    <script src="js/index-functions.js"></script>
    <script src="js/index-init.js"></script>
    <script src="js/lector.js?v=dev2"></script>
    <!-- Motor de lectura simplificado -->
    <script src="js/reading-engine.js?v=1"></script>
    <script src="practicas/js/practice-functions.js"></script>
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

  <!-- SDK de PayPal (Cargado globalmente para compatibilidad con pestañas AJAX) -->
  <script src="https://www.paypal.com/sdk/js?client-id=ATfzdeOVWZvM17U3geOdl_yV513zZfX7oCm_wa0wqog2acHfSIz846MkdZnpu7oCdWFzqdMn0NEN0xSM&vault=true&intent=subscription" data-sdk-integration-source="button-factory"></script>

  <!-- Footer simple -->
  <footer class="footer-main">
    <p>
      © <span id="year"></span> LeeInglés - Aprende inglés leyendo y entendiendolo | 📧 info@leeingles.com
    </p>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>

  <!-- Al final del body, antes de cerrar -->
  <script src="js/public-texts-dropdown.js"></script>

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
