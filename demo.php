<?php
session_start();
require_once 'db/connection.php';
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
  <title>LeerEntender - Demo</title>
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
  <link rel="icon" href="img/aprender_ingles.gif" type="image/gif">
  <link href="https://fonts.googleapis.com/css2?family=Gruppo&display=swap" rel="stylesheet">
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
          <div class="slogan">Leé en inglés y<br>comprendé en español al instante</div>
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
  <div class="landing-container" style="
    background: #457b9d00;
">
      <!-- Sección Hero -->
      <section class="hero-section">
        <div class="hero-content">
          <div class="hero-main" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:12px;">
            <video id="demo-video" controls preload="metadata" playsinline style="max-width: 960px; width: 100%; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); position: relative; z-index: 1;">
              <source src="leerentender.mp4" type="video/mp4">
              Tu navegador no soporta reproducción de video HTML5.
            </video>
            <div class="hero-buttons" style="display:flex; gap:10px;">
            <a href="index.php?show_public_texts=1" class="btn-secondary" style="
    background: #457b9dd4;
">👁️ Ver textos públicos</a>
            </div>
          </div>
          
          <div class="hero-advertising">
            <!-- //<h3 class="ad-title">Consume inglés</h3> -->
            <div class="ad-space-main" style="
    background: rgb(255, 255, 255);
    font-weight: 200;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.28), -1px -1px 2px rgba(255, 255, 255, 0.6);
    color: #457b9d;
">
             <strong style="  margin-bottom: -9px;">El inglés que se queda contigo</strong><br>
             "Muchos estudiantes se enfrentan a materiales solo en inglés, lo que les obliga a traducir continuamente y ralentiza su aprendizaje. Esta aplicación ofrece una exposición constante al idioma, pero comprensible en todo momento, lo que agiliza y facilita el proceso de aprendizaje."
            </div>
          </div>
        </div>

        <div class="hero-features">
          <div class="hero-feature">
            <span>✓</span> ----------------
          </div>
          <div class="hero-feature">
            <span>✓</span> -----------------
          </div>
          <div class="hero-feature">
            <span>✓</span> Cancela en cualquier momento
          </div>
        </div>
      </section>
    </div>
  </div>

  <script src="js/common-functions.js"></script>
  <script src="js/header-functions.js"></script>
</body>
</html>
