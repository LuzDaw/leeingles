// Funciones para modales de login y registro
// Las funciones comunes están en common-functions.js

// Variables globales para modal
let actionAfterLogin = null;

/**
 * Muestra el modal de autenticación y activa la vista de inicio de sesión.
 */
window.showLoginModal = function() {
    const modal = DOMUtils.getElement('authModal');
    if (modal) {
        modal.classList.add('show');
        switchAuthView('loginView');
    }
}

/**
 * Cambia la vista activa dentro del modal de autenticación (entre login y registro).
 *
 * Oculta todas las vistas de autenticación, muestra la vista especificada y
 * actualiza el estado activo de las pestañas correspondientes.
 *
 * @param {string} viewId - El ID de la vista a mostrar (ej. 'loginView', 'registerView').
 */
window.switchAuthView = function(viewId) {
    document.querySelectorAll('.auth-view').forEach(v => v.style.display = 'none');
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    
    const view = DOMUtils.getElement(viewId);
    if (view) view.style.display = 'block';
    
    const tab = document.querySelector(`[data-view="${viewId}"]`);
    if (tab) tab.classList.add('active');

    // Limpiar mensajes al cambiar
    document.querySelectorAll('.auth-msg').forEach(m => {
        m.classList.remove('show');
        m.innerHTML = '';
    });
}

// Eventos de cierre de modales 
EventUtils.addOptionalListener('authClose', 'click', () => {
    DOMUtils.getElement('authModal').classList.remove('show');
});

EventUtils.addOptionalListener('authBackdrop', 'click', () => {
    DOMUtils.getElement('authModal').classList.remove('show');
});

/**
 * Muestra el modal de autenticación y activa la vista de registro.
 */
window.showRegisterModal = function() {
    const modal = DOMUtils.getElement('authModal');
    if (modal) {
        modal.classList.add('show');
        switchAuthView('registerView');
    }
}

/**
 * Muestra el modal para restablecer la contraseña.
 *
 * Oculta el modal de autenticación si está visible y muestra el modal
 * de "Olvidé mi Contraseña", limpiando cualquier mensaje previo.
 */
window.showForgotPasswordModal = function() {
    const authModal = DOMUtils.getElement('authModal');
    if (authModal) authModal.classList.remove('show');
    
    DOMUtils.showElement('forgot-password-modal');
    DOMUtils.hideElement('forgot-password-messages');
    DOMUtils.updateText('forgot-password-messages', '');
}

/**
 * Muestra el modal para restablecer la contraseña, cargando el formulario dinámicamente.
 *
 * Oculta otros modales de autenticación y carga el contenido de `restablecer_contrasena.php`
 * en el modal, ejecutando los scripts necesarios.
 *
 * @param {string} token - El token de restablecimiento de contraseña.
 */
window.showResetPasswordModal = async function(token) {
    const authModal = DOMUtils.getElement('authModal');
    if (authModal) authModal.classList.remove('show');
    
    DOMUtils.hideElement('forgot-password-modal');
    DOMUtils.showElement('reset-password-modal');
    DOMUtils.updateHTML('reset-password-modal-content', '<div style="text-align: center; padding: 20px;"><p>Cargando formulario...</p></div>');

    try {
        const response = await fetch(`logueo_seguridad/restablecer_contrasena.php?token=${token}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const htmlContent = await response.text();
        DOMUtils.updateHTML('reset-password-modal-content', htmlContent);
        
        // Ejecutar scripts dentro del contenido cargado (password_visibility.js y el script inline)
        const modalContent = DOMUtils.getElement('reset-password-modal-content');
        const scripts = modalContent.querySelectorAll('script');
        
        scripts.forEach((script, index) => {
            const newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            newScript.type = 'text/javascript';
            document.body.appendChild(newScript);
            
            // Eliminar el script original después de crear el nuevo
            setTimeout(() => {
                if (script.parentNode) {
                    script.remove();
                }
            }, 100);
        });

    } catch (error) {
        DOMUtils.updateHTML('reset-password-modal-content', '<div class="message error" style="color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0;">Error al cargar el formulario de restablecimiento. Por favor, inténtalo de nuevo.</div>');
    }
}

EventUtils.onDOMReady(() => {
    // Manejar clics en pestañas
    document.querySelectorAll('.auth-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            switchAuthView(tab.dataset.view);
        });
    });

    // Inicializar toggles de contraseña (ojo)
    if (typeof setupPasswordVisibilityToggle === 'function') {
        setupPasswordVisibilityToggle('login-password', 'togglePasswordLogin');
        setupPasswordVisibilityToggle('register-password', 'togglePasswordRegister');
    }

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
            
            try {
                // Mostrar modal de carga antes de enviar la petición
                window.showLoadingRedirectModal('Registrando usuario...', 'Por favor, espera...');

                const data = await HTTPUtils.postFormData('logueo_seguridad/ajax_register.php', formData);
                
                // Ocultar modal de carga después de recibir la respuesta, independientemente del éxito o fracaso
                const loadingModal = DOMUtils.getElement('loading-redirect-modal');
                if (loadingModal) {
                    loadingModal.classList.remove('show');
                }

                if (data.success) {
                    const userEmail = formData.get('email');
                    
                    // Cerrar el modal de autenticación
                    const authModal = DOMUtils.getElement('authModal');
                    if (authModal) authModal.classList.remove('show');
                    
                    // Crear notificación flotante temporal
                    const toast = document.createElement('div');
                    toast.style.cssText = `
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 99999;
                        background: #fff;
                        padding: 20px;
                        border-radius: 12px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                        border: 1px solid #bfdbfe;
                        min-width: 300px;
                        animation: slideInDown 0.5s ease;
                    `;
                    
                    toast.innerHTML = `
                        <div style="text-align: center; font-family: sans-serif;">
                            <p style="color: #3B82F6; font-weight: bold; margin-bottom: 10px;">✓ ¡Registro exitoso!</p>
                            <p style="color: #1f2937; margin-bottom: 5px;">Tu cuenta está pendiente de activación.</p>
                            <p style="font-size: 0.85em; color: #4b5563; margin: 5px 0;">Revisa tu email: <strong>${userEmail}</strong></p>
                            <a href="mailto:${userEmail}" style="display: inline-block; margin-top: 10px; text-decoration: none; background: #2563eb; color: white; padding: 8px 20px; border-radius: 6px; font-size: 0.85em; font-weight: 600;">✉️ Abrir correo</a>
                            <button onclick="this.parentElement.parentElement.remove()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af;">&times;</button>
                        </div>
                    `;
                    
                    document.body.appendChild(toast);
                    
                    // Auto-eliminar después de 10 segundos
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.style.opacity = '0';
                            toast.style.transition = 'opacity 0.5s ease';
                            setTimeout(() => toast.remove(), 500);
                        }
                    }, 10000);

                    const errorMsg = DOMUtils.getElement('register-error');
                    if (errorMsg) errorMsg.classList.remove('show');
                } else {
                    const errorMsg = DOMUtils.getElement('register-error');
                    if (errorMsg) {
                        errorMsg.innerHTML = data.error;
                        errorMsg.classList.add('show');
                    }
                    // Limpiar todos los campos del formulario y re-habilitar el formulario en caso de error
                    DOMUtils.updateValue('register-username', '');
                    DOMUtils.updateValue('register-email', '');
                    DOMUtils.updateValue('register-password', '');
                    // Asegurarse de que el botón de envío no esté deshabilitado si se implementó alguna lógica para ello
                    const submitButton = registerForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            } catch (error) {
                // Ocultar modal de carga en caso de error
                const loadingModal = DOMUtils.getElement('loading-redirect-modal');
                if (loadingModal) {
                    loadingModal.classList.remove('show');
                }
                MessageUtils.showError('register-error', 'Error del servidor');
                // Limpiar todos los campos del formulario y re-habilitar el formulario en caso de error del servidor
                DOMUtils.updateValue('register-username', '');
                DOMUtils.updateValue('register-email', '');
                DOMUtils.updateValue('register-password', '');
                const submitButton = registerForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        };
    }
});

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

        // Ocultar el modal de "Olvidé mi Contraseña" y mostrar el modal de carga
        DOMUtils.hideElement('forgot-password-modal');
        // Mostrar el modal de carga con un delay mínimo de 1 segundo para el mensaje "Enviando email"
        window.showLoadingRedirectModal('Enviando email', 'Por favor, espera...', '', 1000); // Delay de 1 segundo, sin redirección automática

        try {
            const response = await fetch('logueo_seguridad/solicitar_restablecimiento_contrasena.php', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            const data = JSON.parse(text);
            
            // Ocultar el modal de carga (el de "Enviando email")
            const loadingModal = DOMUtils.getElement('loading-redirect-modal');
            if (loadingModal) {
                loadingModal.classList.remove('show');
            }

            if (data.success) {
                // Mostrar un modal de éxito con redirección, aumentando el delay a 5 segundos
                window.showLoadingRedirectModal(
                    'Email enviado',
                    'Revisa tu bandeja de entrada para restablecer tu contraseña.',
                    'index.php', // Redirigir a la página principal o a una página de confirmación
                    5000 // Redirigir después de 5 segundos
                );
            } else {
                // Si hay un error, mostrar el modal de "Olvidé mi Contraseña" de nuevo con el mensaje de error
                window.showForgotPasswordModal(); // Reabrir el modal
                const messagesDiv = DOMUtils.getElement('forgot-password-messages');
                if (messagesDiv) {
                    messagesDiv.textContent = data.message || 'Error al solicitar restablecimiento de contraseña.';
                    messagesDiv.style.color = '#dc3545';
                    messagesDiv.style.display = 'block';
                }
            }
        } catch (error) {
            // Ocultar el modal de carga en caso de error
            const loadingModal = DOMUtils.getElement('loading-redirect-modal');
            if (loadingModal) {
                loadingModal.classList.remove('show');
            }
            // Mostrar el modal de "Olvidé mi Contraseña" de nuevo con el mensaje de error
            window.showForgotPasswordModal(); // Reabrir el modal
            const messagesDiv = DOMUtils.getElement('forgot-password-messages');
            if (messagesDiv) {
                messagesDiv.textContent = 'Error del servidor al solicitar restablecimiento.';
                messagesDiv.style.color = '#dc3545';
                messagesDiv.style.display = 'block';
            }
        }
    };
}

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
            if (loginErrorElement) {
                if (data.pendingVerification && data.email) {
                    loginErrorElement.innerHTML = `
                        <div style="text-align: center; font-family: sans-serif;">
                            <p style="color: #3B82F6; font-weight: bold; margin-bottom: 10px;">Cuenta pendiente de activación</p>
                            <p style="color: #1f2937; margin-bottom: 5px;">Por favor, verifica tu email para continuar.</p>
                            <p style="font-size: 0.85em; color: #4b5563; margin: 5px 0;">Email: <strong>${data.email}</strong></p>
                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                <a href="mailto:${data.email}" style="display: inline-block; text-decoration: none; background: #2563eb; color: white; padding: 8px 20px; border-radius: 6px; font-size: 0.85em; font-weight: 600;">✉️ Abrir correo</a>
                                <button type="button" onclick="resendVerificationEmail('${data.email}')" style="display: inline-block; background: #64748b; color: white; padding: 8px 20px; border-radius: 6px; font-size: 0.85em; font-weight: 600; border: none; cursor: pointer;">🔄 Enviar email de nuevo</button>
                            </div>
                        </div>
                    `;
                    loginErrorElement.classList.add('show', 'info');
                    loginErrorElement.classList.remove('error');
                } else {
                    loginErrorElement.innerHTML = data.message || 'Email o contraseña incorrectos';
                    loginErrorElement.classList.add('show', 'error');
                    loginErrorElement.classList.remove('info');
                }
            }
            
            const passwordInput = DOMUtils.getElement('login-password');
            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.focus();
            }
        }
    } catch (error) {
        const loginErrorElement = DOMUtils.getElement('login-error');
        if (loginErrorElement) {
            loginErrorElement.innerHTML = 'Error del servidor';
            loginErrorElement.classList.add('show', 'error');
        }
    }
});

/**
 * Verifica si el usuario está logueado y, si no lo está, muestra el modal de login.
 *
 * Almacena la acción pendiente para ejecutarla después de un login exitoso.
 *
 * @param {string} action - La acción que se intentó realizar y que requiere autenticación.
 * @returns {boolean} `true` si el usuario está logueado, `false` si se mostró el modal de login.
 */
function requireLogin(action) {
    if (typeof window.userLoggedIn === 'undefined' || !window.userLoggedIn) {
        actionAfterLogin = action;
        showLoginModal();
        return false;
    }
    return true;
}

/**
 * Muestra el formulario de subida de texto, requiriendo que el usuario esté logueado.
 *
 * Si el usuario no está logueado, se le pedirá que inicie sesión primero.
 */
function showUploadFormWithLogin() {
    if (requireLogin('showUploadForm')) {
        showUploadForm();
    }
}

/**
 * Reenvía el email de verificación de cuenta a una dirección de correo específica.
 *
 * Muestra un modal de carga mientras se procesa la solicitud y luego un mensaje
 * de éxito o error.
 *
 * @param {string} email - La dirección de correo electrónico a la que reenviar el email de verificación.
 */
window.resendVerificationEmail = async function(email) {
    try {
        window.showLoadingRedirectModal('Enviando email...', 'Por favor, espera...');
        
        const formData = new FormData();
        formData.append('email', email);
        
        const data = await HTTPUtils.postFormData('logueo_seguridad/ajax_resend_verification.php', formData);
        
        const loadingModal = DOMUtils.getElement('loading-redirect-modal');
        if (loadingModal) loadingModal.classList.remove('show');

        if (data.success) {
            window.showLoadingRedirectModal(
                '✓ Email enviado',
                'Revisa tu bandeja de entrada para activar tu cuenta.',
                '',
                3000,
                true // Ocultar spinner
            );
            
            // Opcional: actualizar el mensaje de error para indicar que se ha reenviado
            const loginErrorElement = DOMUtils.getElement('login-error');
            if (loginErrorElement) {
                loginErrorElement.innerHTML = `
                    <div style="text-align: center; font-family: sans-serif;">
                        <p style="color: #059669; font-weight: bold; margin-bottom: 10px;">✓ ¡Email reenviado con éxito!</p>
                        <p style="color: #1f2937; margin-bottom: 5px;">Hemos enviado un nuevo enlace de activación.</p>
                        <p style="font-size: 0.85em; color: #4b5563; margin: 5px 0;">Revisa de nuevo: <strong>${email}</strong></p>
                        <a href="mailto:${email}" style="display: inline-block; margin-top: 10px; text-decoration: none; background: #2563eb; color: white; padding: 8px 20px; border-radius: 6px; font-size: 0.85em; font-weight: 600;">✉️ Abrir correo</a>
                    </div>
                `;
            }
        } else {
            MessageUtils.showError('login-error', data.error || 'Error al reenviar el email');
        }
    } catch (error) {
        const loadingModal = DOMUtils.getElement('loading-redirect-modal');
        if (loadingModal) loadingModal.classList.remove('show');
        MessageUtils.showError('login-error', 'Error del servidor al reenviar el email');
    }
};

/**
 * Muestra un modal de carga con un spinner y mensajes personalizables,
 * con la opción de redirigir a una URL después de un retraso.
 *
 * @param {string} mainMessage - El mensaje principal a mostrar en el modal (ej. "Lectura finalizada").
 * @param {string} subMessage - El mensaje secundario (ej. "Redirigiendo...").
 * @param {string} [redirectUrl=''] - La URL a la que se redirigirá después del `delay`. Si está vacío, no hay redirección automática.
 * @param {number} [delay=2000] - El tiempo en milisegundos antes de la redirección o el cierre del modal.
 * @param {boolean} [hideSpinner=false] - Si es `true`, el spinner de carga se ocultará.
 */
window.showLoadingRedirectModal = function(mainMessage, subMessage, redirectUrl, delay = 2000, hideSpinner = false) {
    let modal = DOMUtils.getElement('loading-redirect-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'loading-redirect-modal';
        modal.className = 'loading-redirect-modal'; // La clase inicial ya tiene display: none y opacity: 0
        modal.innerHTML = `
            <div class="loading-redirect-content">
                <div class="loading-redirect-spinner"></div>
                <div class="loading-redirect-main-message"></div>
                <div class="loading-redirect-sub-message"></div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Limpiar timeout previo si existe para evitar cierres o redirecciones prematuras
    if (modal.timeoutId) {
        clearTimeout(modal.timeoutId);
        modal.timeoutId = null;
    }

    const mainMessageElement = modal.querySelector('.loading-redirect-main-message');
    const subMessageElement = modal.querySelector('.loading-redirect-sub-message');
    const spinnerElement = modal.querySelector('.loading-redirect-spinner');

    // Mostrar u ocultar el spinner
    if (spinnerElement) {
        spinnerElement.style.display = hideSpinner ? 'none' : 'block';
    }

    // Actualizar el texto de los elementos del modal
    if (mainMessageElement) {
        mainMessageElement.innerHTML = mainMessage; // Usar innerHTML directamente
    }

    if (subMessageElement) {
        subMessageElement.innerHTML = subMessage; // Usar innerHTML directamente
    }

    // Asegurarse de que el modal esté visible
    if (!modal.classList.contains('show')) {
        void modal.offsetWidth; 
        modal.classList.add('show');
    }

    if (redirectUrl) { 
        // Si hay URL, redirigir tras el delay
        modal.timeoutId = setTimeout(() => {
            window.location.href = redirectUrl;
        }, delay);
    } else if (hideSpinner) {
        // Si ocultamos el spinner y no hay redirección, es un mensaje de éxito final, cerramos tras el delay
        modal.timeoutId = setTimeout(() => {
            modal.classList.remove('show');
        }, delay);
    }
};
