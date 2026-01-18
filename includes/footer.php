<?php
// Determinar si estamos en modo lectura
$is_reading_mode = isset($_GET['text_id']) || isset($_GET['public_text_id']);
?>

<?php if (!$is_reading_mode): ?>
  <!-- Footer Completo (Oculto en modo lectura) -->
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
          <li><a href="?show_public_texts=1">Idiomas</a></li>
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
<?php endif; ?>

<!-- Footer simple (Siempre visible) -->
<footer class="footer-main">
  <p>
    © <span id="year-footer"></span> LeeInglés - Aprende inglés leyendo y entendiendolo | 📧 info@leeingles.com
  </p>
</footer>

<script>
  const yearSpan = document.getElementById('year-footer');
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }
</script>
