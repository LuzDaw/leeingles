// Funciones para modales de login y registro
// Las funciones comunes están en common-functions.js

// Variables globales para modal
let actionAfterLogin = null;

// Eventos de cierre de modales
EventUtils.addOptionalListener('close-login-modal', 'click', () => {
    DOMUtils.hideElement('login-modal');
    DOMUtils.hideElement('login-error');
    DOMUtils.updateText('login-error', '');
});

// Funciones para modal de registro
window.showRegisterModal = function() {
    console.log('showRegisterModal called');
    DOMUtils.hideElement('login-modal');
    DOMUtils.showElement('register-modal');
    console.log('login-modal display:', DOMUtils.getElement('login-modal').style.display);
    console.log('register-modal display:', DOMUtils.getElement('register-modal').style.display);
}

// Funciones para modal de "Olvidé mi Contraseña"
window.showForgotPasswordModal = function() {
    DOMUtils.hideElement('login-modal');
    DOMUtils.showElement('forgot-password-modal');
    DOMUtils.hideElement('forgot-password-messages');
    DOMUtils.updateText('forgot-password-messages', '');
}

// Funciones para modal de restablecer contraseña
window.showResetPasswordModal = async function(token) {
    DOMUtils.hideElement('login-modal');
    DOMUtils.hideElement('forgot-password-modal');
    DOMUtils.showElement('reset-password-modal');
    DOMUtils.updateHTML('reset-password-modal-content', '<div style="text-align: center; padding: 20px;"><p>Cargando formulario...</p></div>');

    try {
        const response = await fetch(`logueo_seguridad/restablecer_contrasena.php?token=${token}`);
        const htmlContent = await response.text();
        DOMUtils.updateHTML('reset-password-modal-content', htmlContent);
        
        // Ejecutar scripts dentro del contenido cargado (password_visibility.js y el script inline)
        const scripts = DOMUtils.getElement('reset-password-modal-content').querySelectorAll('script');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            document.body.appendChild(newScript);
            script.remove(); // Eliminar el script original para evitar duplicados
        });

    } catch (error) {
        console.error('Error al cargar el formulario de restablecimiento:', error);
        DOMUtils.updateHTML('reset-password-modal-content', '<div class="message error">Error al cargar el formulario de restablecimiento. Por favor, inténtalo de nuevo.</div>');
    }
}

EventUtils.onDOMReady(() => {
    EventUtils.addOptionalListener('close-register-modal', 'click', () => {
        DOMUtils.hideElement('register-modal');
    });

    EventUtils.addOptionalListener('close-forgot-password-modal', 'click', () => {
        DOMUtils.hideElement('forgot-password-modal');
        DOMUtils.hideElement('forgot-password-messages');
        DOMUtils.updateText('forgot-password-messages', '');
    });

    EventUtils.addOptionalListener('close-reset-password-modal', 'click', () => {
        DOMUtils.hideElement('reset-password-modal');
        DOMUtils.updateHTML('reset-password-modal-content', ''); // Limpiar contenido al cerrar
    });

    const registerForm = DOMUtils.getElement('register-form');
    if (registerForm) {
        registerForm.onsubmit = async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            if (!ValidationUtils.passwordsMatch(formData.get('password'), formData.get('confirm_password'))) {
                MessageUtils.showError('register-error', 'Las contraseñas no coinciden');
                return;
            }
            
            try {
                const data = await HTTPUtils.postFormData('logueo_seguridad/ajax_register.php', formData);
                
                if (data.success) {
                    const registerSuccessElement = DOMUtils.getElement('register-success');
                    const registerFormButton = registerForm.querySelector('button[type="submit"]');

                    console.log('registerSuccessElement:', registerSuccessElement);
                    if (!registerSuccessElement) {
                        console.error('Error: register-success element not found.');
                        return;
                    }

                    // Construir el HTML completo del mensaje de éxito
                    const userEmail = formData.get('email');
                    const successMessageHtml = `
                        <div style="text-align: center;">
                            <p style="color: #ffffff; margin: 0 0 8px 0; font-weight: 500;">✓ ¡Registro exitoso!</p>
                            <p style="color: #ffffff; margin: 0 0 8px 0; font-weight: 500;">Te hemos enviado un email para activar tu cuenta.</p>
                            <p style="color: #e6e6e6; margin: 0 0 12px 0; font-size: 0.9em;">Revisa tu bandeja de entrada. Si no lo ves, revisa spam o promociones.</p>
                            <a href="mailto:${userEmail}" style="display: inline-block; padding: 8px 15px; background: #0066ffa2; color: white; border-radius: 5px; text-decoration: none; font-weight: 500; transition: background 0.2s;">✉️ Abrir correo</a>
                            <p style="color: #d0d0d0; margin: 10px 0 0 0; font-size: 0.85em;">Email: ${userEmail}</p>
                        </div>
                    `;
                    
                    // Limpiar y preparar el elemento
                    registerSuccessElement.innerHTML = '';
                    registerSuccessElement.className = 'register-success-tooltip';
                    registerSuccessElement.innerHTML = successMessageHtml;
                    
                    // Asegurar que el elemento sea visible para calcular dimensiones
                    registerSuccessElement.style.display = 'block';
                    registerSuccessElement.style.visibility = 'hidden'; // Oculto pero ocupando espacio para calcular
                    registerSuccessElement.style.opacity = '0';

                    console.log('registerSuccessElement display after setting to block:', registerSuccessElement.style.display);
                    console.log('registerSuccessElement innerHTML length:', registerSuccessElement.innerHTML.length);


                    if (registerFormButton) {
                        const rect = registerFormButton.getBoundingClientRect();
                        // Seleccionar el div principal del modal de registro - buscar por posición relativa dentro del modal
                        const registerModal = DOMUtils.getElement('register-modal');
                        const registerModalContent = registerModal ? registerModal.querySelector('div[style*="background"]') : null;
                        
                        if (registerModalContent) {
                            const modalRect = registerModalContent.getBoundingClientRect();
                            
                            // Calcular posición relativa al contenedor del modal
                            const calculatedLeft = (rect.left + rect.width / 2 - modalRect.left);
                            // Posicionar encima del botón con un margen
                            const tooltipHeight = registerSuccessElement.offsetHeight || 120; // Altura estimada si aún no está renderizado
                            const calculatedTop = (rect.top - tooltipHeight - 20 - modalRect.top);

                            registerSuccessElement.style.left = calculatedLeft + 'px';
                            registerSuccessElement.style.top = calculatedTop + 'px';
                            registerSuccessElement.style.transform = 'translateX(-50%)';
                            registerSuccessElement.style.position = 'absolute';

                            console.log('registerFormButton rect:', rect);
                            console.log('registerModalContent rect:', modalRect);
                            console.log('Calculated left:', calculatedLeft, 'px');
                            console.log('Calculated top:', calculatedTop, 'px');
                            console.log('Tooltip height:', tooltipHeight);
                        } else {
                            console.error('registerModalContent not found, using fallback positioning');
                            // Posicionamiento de respaldo: centrado sobre el botón
                            const calculatedLeft = rect.left + rect.width / 2;
                            const calculatedTop = rect.top - 150;
                            registerSuccessElement.style.left = calculatedLeft + 'px';
                            registerSuccessElement.style.top = calculatedTop + 'px';
                            registerSuccessElement.style.transform = 'translateX(-50%)';
                            registerSuccessElement.style.position = 'fixed';
                        }
                    } else {
                        console.warn('registerFormButton not found, tooltip positioning might be off.');
                    }

                    // Hacer visible y mostrar con fade-in
                    setTimeout(() => {
                        registerSuccessElement.style.visibility = 'visible';
                        registerSuccessElement.style.opacity = '1';
                        console.log('registerSuccessElement opacity after setting to 1 (fade in):', registerSuccessElement.style.opacity);
                        console.log('registerSuccessElement computed style display:', window.getComputedStyle(registerSuccessElement).display);
                        console.log('registerSuccessElement computed style visibility:', window.getComputedStyle(registerSuccessElement).visibility);
                    }, 50); // Aumentar ligeramente el delay para asegurar que el DOM está listo

                    // Ocultar el tooltip después de 2 segundos y recargar página
                    setTimeout(() => {
                        registerSuccessElement.style.opacity = '0';
                        setTimeout(() => {
                            // Recargar página para que el usuario esté logueado
                            location.reload();
                        }, 300);
                    }, 2000);

                    DOMUtils.hideElement('register-error');
                    // No recargar la página para que el usuario pueda ver el mensaje y el botón
                } else {
                    MessageUtils.showError('register-error', data.error);
                }
            } catch (error) {
                MessageUtils.showError('register-error', 'Error del servidor');
            }
        };
    }

    // Manejar formulario de "Olvidé mi Contraseña"
    const forgotPasswordForm = DOMUtils.getElement('forgot-password-form');
    if (forgotPasswordForm) {
        forgotPasswordForm.onsubmit = async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const email = formData.get('email');
            const messagesDiv = DOMUtils.getElement('forgot-password-messages');

            if (!email || !ValidationUtils.isValidEmail(email)) {
                MessageUtils.showError('forgot-password-messages', 'Por favor, introduce un email válido.');
                return;
            }

            try {
                console.log('Enviando solicitud de restablecimiento con email:', email);
                const response = await fetch('logueo_seguridad/solicitar_restablecimiento_contrasena.php', {
                    method: 'POST',
                    body: formData
                });
                console.log('Response status:', response.status);
                const text = await response.text();
                console.log('Response text:', text);
                const data = JSON.parse(text);
                console.log('Respuesta recibida:', data);
                
                if (data.success) {
                    const messagesDiv = DOMUtils.getElement('forgot-password-messages');
                    messagesDiv.textContent = data.message;
                    messagesDiv.style.color = '#28a745';
                    messagesDiv.style.display = 'block';
                    // Opcional: cerrar modal después de un tiempo
                    setTimeout(() => {
                        DOMUtils.hideElement('forgot-password-modal');
                        // Si el email se envió con éxito, mostrar el modal de restablecimiento de contraseña
                        // Esto es para el caso en que el usuario haga clic en el enlace del email
                        // y la página se recargue con el token en la URL.
                        // Aquí, simplemente cerramos el modal de "olvidé mi contraseña".
                        // La lógica para abrir el modal de restablecimiento se manejará en el DOMContentLoaded
                        // si la URL contiene un token.
                    }, 3000); // Cerrar después de 3 segundos
                } else {
                    const messagesDiv = DOMUtils.getElement('forgot-password-messages');
                    messagesDiv.textContent = data.message || 'Error al solicitar restablecimiento de contraseña.';
                    messagesDiv.style.color = '#dc3545';
                    messagesDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error en solicitud de restablecimiento:', error);
                const messagesDiv = DOMUtils.getElement('forgot-password-messages');
                messagesDiv.textContent = 'Error del servidor al solicitar restablecimiento.';
                messagesDiv.style.color = '#dc3545';
                messagesDiv.style.display = 'block';
            }
        };
    }
});

// Manejar formulario de login
EventUtils.addOptionalListener('login-form', 'submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const data = await HTTPUtils.postFormData('logueo_seguridad/ajax_login.php', formData);
        
        if (data.success) {
            // Si hay una acción pendiente después del login, ejecutarla
            if (actionAfterLogin === 'showUploadForm') {
                NavigationUtils.redirect('index.php?show_upload=1');
            } else {
                NavigationUtils.reload();
            }
        } else {
            const loginErrorElement = DOMUtils.getElement('login-error');
            const passwordInput = DOMUtils.getElement('login-password');
            
            // Verificar si es una cuenta pendiente de verificación
            if (data.pendingVerification && data.email) {
                // Crear HTML del tooltip con enlace para abrir correo
                const tooltipHtml = `
                    <div style="text-align: center;">
                        <p style="margin: 0 0 10px 0; color: #ffffff;">Tu cuenta está pendiente de activación.</p>
                        <p style="margin: 0 0 15px 0; color: #e6e6e6; font-size: 0.95em;">Revisa tu email para el enlace de activación:</p>
                        <p style="margin: 0 0 15px 0; color: #ffffff; font-weight: bold; word-break: break-all;">${data.email}</p>
                        <a href="mailto:" style="display: inline-block; padding: 8px 15px; background: #0066ffa2; color: white; border-radius: 5px; text-decoration: none; margin-bottom: 8px;">✉️ Abrir correo</a>
                        <p style="margin: 10px 0 0 0; color: #d0d0d0; font-size: 0.9em;">Si no ves el email, revisa spam o promociones.</p>
                    </div>
                `;
                loginErrorElement.innerHTML = tooltipHtml;
                
                // Usar clase especial para este tipo de tooltip
                loginErrorElement.classList.add('login-pending-verification-tooltip');
                loginErrorElement.classList.remove('login-error-tooltip');
            } else {
                // Tooltip normal de error
                DOMUtils.updateText('login-error', data.message || 'has introducido mal el usuario o contraseña o usuario no existe');
                loginErrorElement.classList.add('login-error-tooltip');
                loginErrorElement.classList.remove('login-pending-verification-tooltip');
            }
            
            loginErrorElement.style.display = 'block'; // Mostrar para calcular posición
            
            // Posicionar el tooltip encima del campo de contraseña
            if (passwordInput) {
                const rect = passwordInput.getBoundingClientRect();
                const modalRect = DOMUtils.getElement('login-modal').querySelector('.bg-white').getBoundingClientRect(); // Obtener el contenedor del modal
                
                // Calcular posición relativa al contenedor del modal
                loginErrorElement.style.left = (rect.left + rect.width / 2 - modalRect.left) + 'px';
                loginErrorElement.style.top = (rect.top - loginErrorElement.offsetHeight - 15 - modalRect.top) + 'px'; // 15px de margen + altura de la flecha
                loginErrorElement.style.transform = 'translateX(-50%)'; // Centrar horizontalmente
            }
            
            // Mostrar con opacidad
            setTimeout(() => {
                loginErrorElement.style.opacity = '1';
            }, 10); // Pequeño retraso para que la transición CSS funcione
            
            // Si es pendiente de verificación, mostrar más tiempo; si es error, 3 segundos
            const hideTimeout = data.pendingVerification ? 8000 : 3000;
            
            setTimeout(() => {
                loginErrorElement.style.opacity = '0';
                setTimeout(() => {
                    loginErrorElement.style.display = 'none';
                    loginErrorElement.classList.remove('login-error-tooltip');
                    loginErrorElement.classList.remove('login-pending-verification-tooltip');
                }, 300); // Esperar a que termine la transición de opacidad
            }, hideTimeout);
            
            if (passwordInput) {
                passwordInput.value = ''; // Limpiar contraseña
                passwordInput.focus(); // Poner foco en la contraseña
            }
        }
    } catch (error) {
        const loginErrorElement = DOMUtils.getElement('login-error');
        const passwordInput = DOMUtils.getElement('login-password');

        DOMUtils.updateText('login-error', 'Error del servidor');
        loginErrorElement.classList.add('login-error-tooltip');
        loginErrorElement.classList.remove('login-pending-verification-tooltip');
        loginErrorElement.style.display = 'block';

        if (passwordInput) {
            const rect = passwordInput.getBoundingClientRect();
            const modalRect = DOMUtils.getElement('login-modal').querySelector('.bg-white').getBoundingClientRect();
            loginErrorElement.style.left = (rect.left + rect.width / 2 - modalRect.left) + 'px';
            loginErrorElement.style.top = (rect.top - loginErrorElement.offsetHeight - 15 - modalRect.top) + 'px';
            loginErrorElement.style.transform = 'translateX(-50%)';
        }

        setTimeout(() => {
            loginErrorElement.style.opacity = '1';
        }, 10);

        setTimeout(() => {
            loginErrorElement.style.opacity = '0';
            setTimeout(() => {
                loginErrorElement.style.display = 'none';
                loginErrorElement.classList.remove('login-error-tooltip');
                loginErrorElement.classList.remove('login-pending-verification-tooltip');
            }, 300);
        }, 3000);
    }
});

// Funciones de acceso que requieren login
function requireLogin(action) {
    if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
        actionAfterLogin = action;
        DOMUtils.showElement('login-modal');
        return false;
    }
    return true;
}

function showUploadFormWithLogin() {
    if (requireLogin('showUploadForm')) {
        showUploadForm();
    }
}
