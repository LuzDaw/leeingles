<?php
session_start();
require_once 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: logueo_seguridad/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener palabras guardadas del usuario, con título del texto
$stmt = $conn->prepare("SELECT sw.word, sw.translation, sw.context, sw.created_at, sw.text_id, t.title as text_title FROM saved_words sw LEFT JOIN texts t ON sw.text_id = t.id WHERE sw.user_id = ? ORDER BY t.title, sw.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$words = $result->fetch_all(MYSQLI_ASSOC);

// Agrupar palabras por texto
$words_by_text = [];
foreach ($words as $word) {
    $title = $word['text_title'] ?? 'Sin texto asociado';
    $words_by_text[$title][] = $word;
}

// Procesar acción en lote para eliminar palabras seleccionadas (antes de cerrar la conexión)
// (Eliminado: ya no se permite eliminar en lote desde la interfaz)
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
  <title>Mis palabras guardadas - LeerEntender</title>
  <!-- CSS Principal -->
  <link rel="stylesheet" href="css/modern-styles.css">
  <link rel="stylesheet" href="css/color-theme.css">
  <link rel="stylesheet" href="css/header-redesign.css">
  <!-- CSS de Componentes -->
  <link rel="stylesheet" href="css/text-styles.css">
  <link rel="stylesheet" href="css/floating-menu.css">
  <link rel="stylesheet" href="css/reading-styles.css">
  <link rel="stylesheet" href="practicas/css/practice-styles.css">
  <link rel="stylesheet" href="css/modal-styles.css">
  <!-- CSS Optimizado para Móvil -->
  <link rel="stylesheet" href="css/mobile-ready.css">
  <!-- CSS Landing Page -->
  <link rel="stylesheet" href="css/landing-page.css">
  <!-- Favicon -->
  <link rel="icon" href="img/aprender_ingles.gif" type="image/gif">
  <link rel="stylesheet" href="css/saved-words-styles.css">
</head>
<body>
  <!-- Header consistente con el resto de la aplicación -->
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
        <a href="my_texts.php" class="nav-btn">📚 Mis textos</a>
        <a href="index.php?show_progress=1" class="nav-btn">📊 Progreso</a>
        <a href="index.php?show_upload=1" class="nav-btn primary">⬆ Subir texto</a>
        <a href="logueo_seguridad/logout.php" class="user-name" title="Cerrar sesión">Hola <?= htmlspecialchars($_SESSION['username']) ?></a>
      </div>
      <button class="mobile-menu-toggle" id="mobile-toggle">☰</button>
    </div>
  </header>
  <main class="main-container" style="max-width: 900px; margin: 20px auto; padding: 0 20px;">
    <div class="reading-area" style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
      <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
        <a href="index.php?show_upload=1" class="nav-btn primary" style="text-decoration: none;">
          ⬆ Subir nuevo texto
        </a>
        <a href="my_texts.php" class="nav-btn" style="text-decoration: none;">
          📚 Mis textos
        </a>
        <a href="index.php?practice=1" class="nav-btn" style="text-decoration: none;">
          🎯 Practicar vocabulario
        </a>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="color: #6b7280; font-weight: 500;">
          <span style="color: #4A90E2; font-weight: 600;">
            <?php echo isset($words) ? count($words) : 0; ?>
          </span> palabras guardadas
        </div>
        <div class="bulk-actions">
          <div class="dropdown">
            <button class="nav-btn" id="dropdownBtn" onclick="toggleDropdown()" disabled style="background: #f3f4f6; color: #6b7280;">
              Acciones en lote ▼
            </button>
            <div class="dropdown-content" id="dropdownContent" style="background: white; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
              <button type="button" onclick="selectAllWords()">✓ Marcar todos</button>
              <button type="button" onclick="unselectAllWords()">✗ Desmarcar todos</button>
            </div>
          </div>
        </div>
      </div>
      <?php if (isset($success_message)): ?>
        <div class="message" style="background: #d1fae5; color: #065f46; border: 1px solid #ff8a0087; text-align:center; font-size:16px;">
          <?= htmlspecialchars($success_message) ?>
        </div>
      <?php elseif (isset($error_message)): ?>
        <div class="message" style="background: #fee2e2; color: #991b1b; border: 1px solid #f87171; text-align:center; font-size:16px;">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>
      <?php if (empty($words_by_text)): ?>
        <div style="text-align:center; color:#888; font-size:18px; margin:40px 0;">No tienes palabras guardadas aún.</div>
      <?php else: ?>
        <form method="post" id="words-list-form">
          <?php foreach ($words_by_text as $text_title => $words): ?>
            <div class="card" style="margin-bottom: 30px;">
              <div class="card-header" style="display:flex;align-items:center;gap:10px;">
                <input type="checkbox" class="text-checkbox" onclick="toggleGroup(this, 'group-<?= md5($text_title) ?>')">
                <span class="text-title" style="font-size:1.2rem; font-weight:600; color:#1B263B;">
                  <?= htmlspecialchars($text_title) ?>
                  <span style="font-size:0.9em; color:#64748b; font-weight:400; margin-left:8px;">(<?= count($words) ?>)</span>
                </span>
              </div>
              <ul class="text-list" id="group-<?= md5($text_title) ?>">
                <?php foreach ($words as $word): ?>
                  <li style="display: flex; flex-direction: column; align-items: flex-start; gap: 2px; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <input type="checkbox" class="word-checkbox" name="selected_words[]" value="<?= htmlspecialchars($word['word']) ?>|<?= intval($word['text_id']) ?>" onchange="updateBulkActions()">
                      <span class="word-english" style="font-size: 16px; font-weight: 500; color: #1f2937;"> <?= htmlspecialchars($word['word']) ?> </span>
                      <span class="word-spanish" style="font-size: 15px; color: #3b82f6; font-style: italic;"> <?= htmlspecialchars($word['translation']) ?> </span>
                      <?php if (!empty($word['context'])): ?>
                        <span class="word-context"> <?= htmlspecialchars(limitar_palabras($word['context'], 10)) ?> </span>
                      <?php endif; ?>
                      <span class="word-date"> <?= date('d/m/Y', strtotime($word['created_at'])) ?> </span>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        </form>
      <?php endif; ?>
    </div>
  </main>
  <!-- Footer -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section">
        <h3>🌟 LeerEnteder</h3>
        <p>La forma más efectiva de aprender idioma a través de lectura inmersiva.</p>
      </div>
      <div class="footer-section">
        <h3>Producto</h3>
        <ul>
          <li><a href="#caracteristicas">Características</a></li>
          <li><a href="index.php?show_public_texts=1">Idioma</a></li>
          <li><a href="#testimonios">Comunidad</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Soporte</h3>
        <ul>
          <li><a href="#">Centro de Ayuda</a></li>
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
      <p>Únete a más de 100,000 estudiantes que ya han descubierto el poder del aprendizaje inmersivo de idioma.</p>
    </div>
  </footer>

<script>
function toggleDropdown() {
  document.getElementById("dropdownContent").parentElement.classList.toggle("show");
}

function updateBulkActions() {
  const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');
  const dropdownBtn = document.getElementById('dropdownBtn');

  if (checkboxes.length > 0) {
    dropdownBtn.disabled = false;
    dropdownBtn.textContent = `Acciones (${checkboxes.length}) ▼`;
    dropdownBtn.style.background = '#4A90E2';
    dropdownBtn.style.color = 'white';
  } else {
    dropdownBtn.disabled = true;
    dropdownBtn.textContent = 'Acciones en lote ▼';
    dropdownBtn.style.background = '#f3f4f6';
    dropdownBtn.style.color = '#6b7280';
  }
}

function performBulkAction(action) {
  const checkboxes = document.querySelectorAll('input[name="selected_words[]"]:checked');

  if (checkboxes.length === 0) {
    alert('Por favor, selecciona al menos una palabra.');
    return;
  }

  let confirmMessage = '';
  if (action === 'delete') {
    confirmMessage = `¿Estás seguro de que quieres eliminar ${checkboxes.length} palabra(s)?`;
  }

  if (confirm(confirmMessage)) {
    document.getElementById('words-list-form').action = '';
    
    // Crear input hidden para la acción
    let actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    document.getElementById('words-list-form').appendChild(actionInput);
    
    document.getElementById('words-list-form').submit();
  }
}

function selectAllWords() {
  const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
  checkboxes.forEach(cb => cb.checked = true);
  updateBulkActions();
}

function unselectAllWords() {
  const checkboxes = document.querySelectorAll('input[name="selected_words[]"]');
  checkboxes.forEach(cb => cb.checked = false);
  updateBulkActions();
}

function toggleGroup(groupCheckbox, groupId) {
  const group = document.getElementById(groupId);
  const wordCheckboxes = group.querySelectorAll('input[name="selected_words[]"]');
  
  wordCheckboxes.forEach(cb => {
    cb.checked = groupCheckbox.checked;
  });
  
  updateBulkActions();
}

// Cerrar dropdown al hacer clic fuera
window.onclick = function(event) {
  if (!event.target.matches('#dropdownBtn')) {
    const dropdowns = document.getElementsByClassName("dropdown");
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].classList.remove('show');
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var mobileToggle = document.getElementById('mobile-toggle');
  if (mobileToggle) {
    mobileToggle.addEventListener('click', function() {
      var navMenu = document.getElementById('nav-menu');
      if (navMenu) navMenu.classList.toggle('mobile-active');
    });
  }
  var wordCheckboxes = document.querySelectorAll('input[name="selected_words[]"]');
  if (wordCheckboxes.length > 0) {
    wordCheckboxes.forEach(function(cb) {
      cb.addEventListener('change', updateBulkActions);
    });
  }
  updateBulkActions();
});
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();

// Al final del archivo o en un include, define la función de traducción (puede ser dummy o real)
function translate_title_to_spanish($title) {
    // Aquí deberías llamar a tu API de traducción o lógica real
    // Por ahora, solo devuelve el mismo título con un prefijo para pruebas
    return '[ES] ' . $title;
}

// Al final del archivo o en un include, define la función limitar_palabras
function limitar_palabras($texto, $max_palabras = 10) {
    $palabras = preg_split('/\s+/', trim($texto));
    if (count($palabras) > $max_palabras) {
        return implode(' ', array_slice($palabras, 0, $max_palabras)) . '...';
    }
    return $texto;
}
?>
