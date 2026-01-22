/**
 * Lógica de inicialización para index.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltip
    if (typeof window.createTooltip === 'function') {
        window.createTooltip();
    }

    // Mostrar botón flotante si hay texto con párrafos
    setTimeout(() => {
        const paragraphs = document.querySelectorAll('.paragraph');
        if (paragraphs.length > 0) {
            if (typeof window.showFloatingButton === 'function') window.showFloatingButton();
            if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
        }
    }, 500);

    // Detectar parámetros en la URL para cargar pestañas
    const urlParams = new URLSearchParams(window.location.search);
    let tab = urlParams.get('tab');
    const showUpload = urlParams.get('show_upload');
    const resetToken = urlParams.get('token');
    const paymentSuccess = urlParams.get('payment_success');
    const mensaje = urlParams.get('mensaje');

    if (mensaje) {
        // Limpiar la URL inmediatamente
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);

        // Mostrar notificación flotante para cualquier mensaje recibido
        const isError = mensaje.toLowerCase().includes('error') || 
                        mensaje.toLowerCase().includes('no es válido') || 
                        mensaje.toLowerCase().includes('expirado') ||
                        mensaje.toLowerCase().includes('ya ha sido utilizado');
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-left: 5px solid ${isError ? '#EF4444' : '#3B82F6'};
            font-family: sans-serif;
            animation: slideInDown 0.5s ease;
            max-width: 90%;
            text-align: center;
        `;
        
        const icon = isError ? '❌' : '✓';
        const iconColor = isError ? '#EF4444' : '#3B82F6';
        const displayMsg = mensaje === 'cuenta activada' ? 'Cuenta activada correctamente' : mensaje;

        toast.innerHTML = `<span style="color: ${iconColor}; font-weight: bold; margin-right: 8px;">${icon}</span> ${displayMsg}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    if (resetToken) {
        setTimeout(() => {
            if (typeof window.showResetPasswordModal === 'function') {
                window.showResetPasswordModal(resetToken);
            }
        }, 500);
    } else if (window.userLoggedIn) {
        // Solo cargar pestañas si el usuario está logueado
        if (tab === 'texts') {
            if (typeof window.loadTabContent === 'function') window.loadTabContent('my-texts');
        } else if (showUpload === '1') {
            if (typeof window.loadTabContent === 'function') window.loadTabContent('upload');
        } else if (tab && ['progress','my-texts','saved-words','practice','upload','account'].includes(tab)) {
            const scroll = urlParams.get('scroll');
            if (typeof window.loadTabContent === 'function') window.loadTabContent(tab, paymentSuccess === '1', scroll);
        } else {
            const isViewingText = window.location.search.includes('text_id=') || window.location.search.includes('public_text_id=');
            if (!isViewingText) {
                if (typeof window.loadTabContent === 'function') window.loadTabContent('progress');
            }
        }
    }

    // Inicializar visibilidad de contraseñas
    if (typeof window.setupPasswordVisibilityToggle === 'function') {
        window.setupPasswordVisibilityToggle('login-password', 'togglePasswordLogin');
        window.setupPasswordVisibilityToggle('register-password', 'togglePasswordRegister');
    }

    // Event listeners para botones de navegación
    document.getElementById('login-btn')?.addEventListener('click', () => {
        if (typeof window.showLoginModal === 'function') {
            window.showLoginModal();
        }
    });

    document.getElementById('login-btn-hero')?.addEventListener('click', () => {
        if (typeof window.showLoginModal === 'function') {
            window.showLoginModal();
        }
    });

    document.getElementById('public-texts-btn')?.addEventListener('click', () => {
        window.location.href = 'index.php?show_public_texts=1';
    });

    // Inicializar lector si el usuario está logueado
    if (window.userLoggedIn && typeof window.initLector === 'function') {
        try {
            window.initLector();
        } catch (e) {
            console.error("Error al ejecutar initLector:", e);
        }
    }

    // Actualizar año en el footer
    const yearEl = document.getElementById('year');
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }
});

// Parar lectura al salir de la página
window.addEventListener('beforeunload', function() {
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
});

// Cargar práctica si estamos en modo práctica
if (window.location.search.includes('practice=1')) {
    const loadPractice = () => {
        if (typeof window.loadPracticeMode === 'function') {
            window.loadPracticeMode();
        } else {
            setTimeout(loadPractice, 100);
        }
    };
    loadPractice();
}
