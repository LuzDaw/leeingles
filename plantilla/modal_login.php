<!-- BEGIN AUTH MODAL (insertar después del botón #botonLogin o antes de </body>) -->
<div id="authModal" class="auth-modal" aria-hidden="true" role="dialog" aria-labelledby="authTitle">
  <div class="auth-modal__backdrop" id="authBackdrop"></div>
  <div class="auth-modal__panel" role="document">
    <button class="auth-close" id="authClose" aria-label="Cerrar">&times;</button>
    <h2 id="authTitle">Acceder / Registrar</h2>

    <div class="auth-tabs">
      <button class="auth-tab active" data-view="loginView">Iniciar sesión</button>
      <button class="auth-tab" data-view="registerView">Crear cuenta</button>
    </div>

    <div id="loginView" class="auth-view">
      <form id="loginForm" autocomplete="on" novalidate>
        <div class="field"><label>Email o Nombre de usuario</label><input type="text" name="identifier" required></div>
        <div class="field password-field">
          <label>Contraseña</label>
          <input type="password" name="password" id="loginPassword" required>
          <button type="button" class="toggle-password" data-target="loginPassword" aria-label="Mostrar/Ocultar contraseña">
            <i class="material-icons visibility-icon">visibility_off</i>
            <i class="material-icons visibility-icon hidden">visibility</i>
          </button>
        </div>
        <div class="field"><label><input type="checkbox" name="remember"> Recordarme</label></div>
        <div id="loginMsg" class="auth-msg" aria-live="polite"></div>
        <button type="submit" class="auth-btn">Entrar</button>
      </form>
    </div> 

    <div id="registerView" class="auth-view" style="display:none">
      <form id="registerForm" autocomplete="on" novalidate>
      
        <div class="field"><label>Nombre de usuario</label><input type="text" name="username" required minlength="2" maxlength="50"></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field password-field">
          <label>Contraseña</label>
          <input type="password" name="password" id="registerPassword" required minlength="8">
          <button type="button" class="toggle-password" data-target="registerPassword" aria-label="Mostrar/Ocultar contraseña">
            <i class="material-icons visibility-icon">visibility_off</i>
            <i class="material-icons visibility-icon hidden">visibility</i>
          </button>
        </div>
        <div class="field"><label>Confirmar contraseña</label><input type="password" name="password_confirm" required></div>
        <div id="registerMsg" class="auth-msg" aria-live="polite"></div>
        <button type="submit" class="auth-btn">Crear cuenta</button>
      </form>
    </div>
  </div>
</div>
<!-- END AUTH MODALz -->
