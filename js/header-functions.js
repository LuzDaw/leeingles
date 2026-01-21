// Funciones del header responsivo y ocultamiento

// Variables globales - asegurar que estén en window
window.headerVisible = true;
window.isReading = false;
window.isInPractice = false;

let headerVisible = window.headerVisible;
let isReading = window.isReading;
let isInPractice = window.isInPractice;

// Función para ocultar header
function hideHeader() {
    const header = document.getElementById('main-header');
    if (header && headerVisible) {
        header.classList.add('hidden');
        document.body.classList.add('header-hidden');
        headerVisible = false;
        window.headerVisible = false;
    }
}

// Función para mostrar header
function showHeader() {
    const header = document.getElementById('main-header');
    if (header && !headerVisible) {
        header.classList.remove('hidden');
        document.body.classList.remove('header-hidden');
        headerVisible = true;
        window.headerVisible = true;
    }
}

// Función para toggle del menú móvil
function toggleMobileMenu() {
    const navMenu = document.getElementById('nav-menu');
    const toggle = document.getElementById('mobile-toggle');
    
    if (navMenu && toggle) {
        navMenu.classList.toggle('active');
        toggle.textContent = navMenu.classList.contains('active') ? '✕' : '☰';
    }
}

// Detectar cuando se inicia la lectura
function onReadingStart() {
    isReading = true;
    // NO ocultar el header automáticamente al iniciar la lectura
    // El header se ocultará solo cuando el usuario haga clic en elementos interactivos
}

// Detectar cuando se para la lectura
function onReadingStop() {
    isReading = false;
    showHeader();
}

// Detectar cuando se inicia práctica
function onPracticeStart() {
    isInPractice = true;
    // El header ahora se mantiene visible en las pestañas de práctica
    showHeader();
}

// Detectar cuando se termina práctica
function onPracticeEnd() {
    isInPractice = false;
    showHeader();
}

// Detectar clics fuera de botones para mostrar header
function handleDocumentClick(event) {
    // Si estamos en lectura o práctica y hacemos clic fuera de botones/controles
    if ((isReading || isInPractice) && !headerVisible) {
        const clickedElement = event.target;
        // Lista de elementos que NO deben mostrar el header
        const ignoreElements = [
            '.clickable-word',
            '#floating-menu',
            '#submenu',
            '#floating-btn',
            '#menu-btn',
            '.submenu-button',
            '.practice-option',
            '.practice-button',
            'input',
            'button',
            '.modal-content',
            '.nav-btn',
            '.tooltip',
            '.translation-tooltip'
        ];
        let shouldIgnore = false;
        for (const selector of ignoreElements) {
            if (clickedElement.closest(selector)) {
                shouldIgnore = true;
                break;
            }
        }
        if (!shouldIgnore) {
            showHeader();
        }
    }
}

// Función específica para mostrar header en página de lectura
function showHeaderOnReadingPage() {
    if (isReading && !headerVisible) {
        showHeader();
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que el header esté visible por defecto
    const header = document.getElementById('main-header');
    if (header) {
        // Remover cualquier clase que pueda estar ocultando el header
        header.classList.remove('hidden');
        document.body.classList.remove('header-hidden');
        headerVisible = true;
        isReading = false;
        isInPractice = false;
        if (typeof window.isCurrentlyReading !== 'undefined') {
            window.isCurrentlyReading = false;
        }
        if (typeof window.isCurrentlyPaused !== 'undefined') {
            window.isCurrentlyPaused = false;
        }
    }
    
    // Configurar toggle del menú móvil
    const mobileToggle = document.getElementById('mobile-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }
    
    // Configurar detección de clics para mostrar header
    document.addEventListener('click', handleDocumentClick);
    
    // Listener específico para página de lectura
    const textContainer = document.getElementById('text');
    if (textContainer) {
        textContainer.addEventListener('click', function(e) {
            // Si el clic no es en elementos interactivos, mostrar header
            if (!e.target.closest('.clickable-word, #floating-menu, #submenu, .tooltip, .translation-tooltip, button, input')) {
                showHeaderOnReadingPage();
            }
        });
    }
    
    // Cerrar menú móvil al hacer clic en enlaces
    const navLinks = document.querySelectorAll('.nav-right .nav-btn');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const navMenu = document.getElementById('nav-menu');
            const toggle = document.getElementById('mobile-toggle');
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                if (toggle) toggle.textContent = '☰';
            }
        });
    });
    
    // Configurar eventos de lectura si las funciones existen
    setTimeout(() => {
        if (typeof window.startReading !== 'undefined') {
            const originalStartReading = window.startReading;
            // Exportar la función original para uso directo
            window.originalStartReading = originalStartReading;
            // NO redefinir startReading para evitar doble ejecución
        }
    }, 1000);
    
    // Configurar detección de fin de lectura
    /*
    if (window.speechSynthesis) {
        const originalCancel = window.speechSynthesis.cancel;
        window.speechSynthesis.cancel = function() {
            onReadingStop();
            return originalCancel.apply(this, arguments);
        };
    }
    */
    
    // Detectar si estamos en modo práctica
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('practice') === '1') {
        onPracticeStart();
    }
});

// Detectar cambios de URL para práctica
window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('practice') === '1') {
        onPracticeStart();
    } else {
        onPracticeEnd();
    }
});

// Exportar funciones para uso externo
window.hideHeader = hideHeader;
window.showHeader = showHeader;
window.onReadingStart = onReadingStart;
window.onReadingStop = onReadingStop;
window.onPracticeStart = onPracticeStart;
window.onPracticeEnd = onPracticeEnd;
window.showHeaderOnReadingPage = showHeaderOnReadingPage;
