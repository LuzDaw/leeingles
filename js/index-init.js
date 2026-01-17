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

    if (resetToken) {
        setTimeout(() => {
            if (typeof window.showResetPasswordModal === 'function') {
                window.showResetPasswordModal(resetToken);
            }
        }, 500);
    } else if (tab === 'texts') {
        if (typeof window.loadTabContent === 'function') window.loadTabContent('my-texts');
    } else if (showUpload === '1') {
        if (typeof window.loadTabContent === 'function') window.loadTabContent('upload');
    } else if (tab && ['progress','my-texts','saved-words','practice','upload','account'].includes(tab)) {
        const scroll = urlParams.get('scroll');
        if (typeof window.loadTabContent === 'function') window.loadTabContent(tab, paymentSuccess === '1', scroll);
    } else {
        const isViewingText = window.location.search.includes('text_id=') || window.location.search.includes('public_text_id=');
        if (window.userLoggedIn && !isViewingText) {
            if (typeof window.loadTabContent === 'function') window.loadTabContent('progress');
        }
    }

    // Inicializar visibilidad de contraseñas
    if (typeof window.setupPasswordVisibilityToggle === 'function') {
        window.setupPasswordVisibilityToggle('login-password', 'togglePasswordLoginModal');
        window.setupPasswordVisibilityToggle('register-password', 'togglePasswordRegisterModal');
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
