<?php
session_start();
require_once 'db/connection.php';
require_once 'includes/content_functions.php';

// Obtener y extraer datos de la página
extract(get_index_page_data($conn));
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
  <link rel="stylesheet" href="lectura/css/floating-menu.css">
  <link rel="stylesheet" href="lectura/css/reading-styles.css">
  <link rel="stylesheet" href="practicas/css/practice-styles.css">
  <link rel="stylesheet" href="css/modal-styles.css">
  <link rel="stylesheet" href="css/tab-system.css">
  <link rel="stylesheet" href="css/mobile-ready.css">
  <link rel="stylesheet" href="css/landing-page.css">
  <link rel="stylesheet" href="css/index-page.css">
  <link rel="stylesheet" href="css/calendar-styles.css">
  <link rel="stylesheet" href="css/dispositivo.css">
  <link rel="stylesheet" href="logueo_seguridad\cuenta\cuenta.css"
  

  <!-- Favicon -->
  <link rel="icon" href="img/aprender_ingles.gif" type="image/gif">
  <link href="https://fonts.googleapis.com/css2?family=Gruppo&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Sistema de voz ResponsiveVoice unificado -->
    <script>
      window.userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
</head>

<body>

  <?php if (empty($text)): ?>
  <header class="header" id="main-header">
    <div class="nav-container">
      <div class="nav-left">
        <div class="brand-container">
          <a href="./" class="logo">
            <img src="img/aprendiendoIngles.png" alt="Logo" class="logo-img">
          </a>

        </div>
        <div class="brand-text">
          <h1>LeeInglés</h1>
          <div class="slogan">
            
          </div>
        </div>

      </div>


      <div class="nav-right" id="nav-menu">
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="user-dropdown">
            <button class="user-dropdown-btn">
              <span class="user-greeting">Hola <?= htmlspecialchars($_SESSION['username']) ?></span>
              <span class="dropdown-arrow">▼</span>
            </button>
            <div class="user-dropdown-menu">
              <a href="#" class="dropdown-item" onclick="loadTabContent('account'); toggleMobileMenu(); ">
                <span class="dropdown-icon">👤</span>
                Mi cuenta
              </a>
              <a href="/logueo_seguridad/logout.php" class="dropdown-item">
                <span class="dropdown-icon">🚪</span>
                Cerrar sesión
              </a>
            </div>
          </div>
        <?php else: ?>
          <a href="#caracteristicas" class="nav-btn">📚 Características</a>
          <button onclick="showUploadFormWithLogin()" class="nav-btn primary">⬆ Subir texto</button>
          <button id="login-btn" class="nav-btn">Cuenta</button>
        <?php endif; ?>
      </div>

      <button class="mobile-menu-toggle" id="mobile-toggle">
        <?php if (isset($_SESSION['user_id'])): ?>
          <span class="material-icons">account_circle</span>
        <?php else: ?>
          ☰
        <?php endif; ?>
      </button>
    </div>
  </header>
  <?php endif; ?>


<div class="main-container">
 <div class="main-containerdos">
    <div id="text" class="reading-area" data-text-id="<?php if (isset($text_id)) { echo $text_id; } elseif (isset($public_id)) { echo $public_id; } else { echo ''; } ?>">

      <?php if (empty($text)): ?>
        <?php if (isset($_GET['practice']) && isset($_SESSION['user_id'])): ?>
          <!-- Modo práctica -->
          <div id="practice-container">
            <div class="practice-header">
              <h3>🎯 Practicar Vocabulario</h3>
              <a href="./" class="nav-btn no-underline">✖️ Salir</a>
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
              <a href="?practice=1" class="nav-btn primary p-10-20 no-underline">
                🧠 Reforzar Palabras Leídas
              </a>
            </div>
          <?php endif; ?>

          <?php if (!empty($public_titles)): ?>
            <ul class="text-list">
              <?php foreach ($public_titles as $pt): ?>
                <li class="text-item">
                  <div class="text-item-container">
                    <a href="?public_text_id=<?= $pt['id'] ?>" class="text-title">
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
                    <a href="?public_text_id=1" class="btn-secondary">👁️ Ver Demo</a>
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

        <?php else: ?>
          <!-- Dashboard de usuario logueado -->
          <div class="user-dashboard">
            <!-- Navegación de pestañas -->
            <div class="tab-navigation tab-nav-container">

            
              <button onclick="loadTabContent('progress')" class="tab-btn" data-tab="progress">
                📊 Progreso
              </button>
              <button onclick="loadTabContent('my-texts')" class="tab-btn active" data-tab="my-texts">
                📋 Biblioteca
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
                <button onclick="window.location.href='admin/admin_categories.php'" class="tab-btn admin-tab-btn" data-tab="admin-categories">
                  ⚙️ Admin
                </button>
              <?php endif; ?>
              <div class="flex-1"></div>
            </div>

            <!-- Contenedor dinámico para pestañas -->
            <div id="tab-content">
              <div class="empty-state">
                <div style="padding: 40px; color: #64748b;">Cargando contenido...</div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <?= render_text_clickable($text, $current_text_title ?? '', $current_text_translation ?? '') ?>
      <?php endif; ?>

    </div>
  </div>
 </div>

  <div id="end-message" class="end-message"></div>

  <!-- MODAL DE AUTENTICACIÓN UNIFICADO (Login / Registro) -->
  <div id="authModal" class="auth-modal" aria-hidden="true" role="dialog">
    <div class="auth-modal__backdrop" id="authBackdrop"></div>
    <div class="auth-modal__panel" role="document">
      <button class="auth-close" id="authClose" aria-label="Cerrar">&times;</button>
      
      <div class="auth-modal__brand">
        <img src="img/aprendiendoIngles.png" alt="Logo" class="auth-modal__logo">
        <span class="auth-modal__app-name">LeeInglés</span>
      </div>
      <h2 id="authTitle" style="display:none;">Acceder / Registrar</h2>

      <div class="auth-tabs">
        <button class="auth-tab active" data-view="loginView">Iniciar sesión</button>
        <button class="auth-tab" data-view="registerView">Crear cuenta</button>
      </div>

      <!-- Vista de Login -->
      <div id="loginView" class="auth-view">
        <form id="login-form" autocomplete="on" novalidate>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="tu@email.com" required>
          </div>
          <div class="field password-field">
            <label>Contraseña</label>
            <input type="password" name="password" id="login-password" placeholder="••••••••" required>
            <button type="button" class="toggle-password" id="togglePasswordLogin" aria-label="Mostrar/Ocultar contraseña">
              <span class="eye-icon">👁️</span>
            </button>
          </div>
          <div class="flex-between items-center mb-15">
            <label class="mb-0 fw-normal"><input type="checkbox" name="remember_me"> Recordarme</label>
            <a href="#" onclick="showForgotPasswordModal(); return false;" class="forgot-link">¿Olvidaste tu contraseña?</a>
          </div>
          <div id="login-error" class="auth-msg error" aria-live="polite"></div>
          <button type="submit" class="auth-btn">Entrar</button>
        </form>
      </div> 

      <!-- Vista de Registro -->
      <div id="registerView" class="auth-view" style="display:none">
        <form id="register-form" autocomplete="on" novalidate>
          <div class="field">
            <label>Nombre de usuario</label>
            <input type="text" name="username" placeholder="Ej: JuanPerez" required minlength="2" maxlength="50">
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="tu@email.com" required>
          </div>
          <div class="field password-field">
            <label>Contraseña</label>
            <input type="password" name="password" id="register-password" placeholder="Mínimo 8 caracteres" required minlength="8">
            <button type="button" class="toggle-password" id="togglePasswordRegister" aria-label="Mostrar/Ocultar contraseña">
              <span class="eye-icon">👁️</span>
            </button>
          </div>
          <div id="register-error" class="auth-msg error" aria-live="polite"></div>
          <button type="submit" class="auth-btn">Crear cuenta</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal de Olvidé mi Contraseña -->
  <div id="forgot-password-modal" class="modal-overlay">
    <div class="modal-container forgot-password-modal-container">
      <button id="close-forgot-password-modal" class="modal-close-x">✕</button>
      <h2 class="modal-title fs-22">Recuperar Contraseña</h2>
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

    <script src="js/global-state.js"></script>
    <script src="js/common-functions.js"></script>
    <script src="js/index-tabs.js"></script>
    <script src="js/index-functions.js"></script>
    <script src="js/index-init.js"></script>
    <script src="js/modal-functions.js"></script>
    <script src="js/header-functions.js"></script>
    <script src="js/calendar-functions.js"></script>
    <script src="js/upload-form.js"></script>
    <script src="js/bulk-actions.js"></script>
    
    <!-- Scripts de Lectura -->
    <script src="lectura/js/text-management.js"></script>
    <script src="lectura/js/electron-voice-integration.js"></script>
    <script src="lectura/js/lector.js"></script>
    <script src="lectura/js/floating-menu.js"></script>
    <script src="lectura/js/multi-word-selection.js"></script>
    
    <!-- Scripts de Práctica -->
    <script src="practicas/js/practice-functions.js"></script>
  <script src="logueo_seguridad/password_visibility.js"></script>
  
  <!-- Sistema de Límite de Traducciones -->
  <?php include 'dePago/limit_modal.php'; ?>
  <script src="dePago/limit_modal.js"></script>

  <!-- SDK de PayPal (Cargado globalmente para compatibilidad con pestañas AJAX) -->
  <script src="https://www.paypal.com/sdk/js?client-id=ATfzdeOVWZvM17U3geOdl_yV513zZfX7oCm_wa0wqog2acHfSIz846MkdZnpu7oCdWFzqdMn0NEN0xSM&vault=true&intent=subscription" data-sdk-integration-source="button-factory"></script>

  <?php include 'includes/footer.php'; ?>

  <!-- Al final del body, antes de cerrar -->
  <script src="lectura/js/public-texts-dropdown.js"></script>

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

    <!-- Overlay para cerrar sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <link rel="stylesheet" href="lectura/css/explain-sidebar.css?v=2">
    <script src="lectura/js/explain-sidebar.js"></script>
  <?php endif; ?>
</body>

</html>
