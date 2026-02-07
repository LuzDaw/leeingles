// Funciones del header responsivo y ocultamiento

// Variables globales - asegurar que estén en window
window.headerVisible = true;
window.isReading = false;
window.isInPractice = false;

let headerVisible = window.headerVisible;
let isReading = window.isReading;
let isInPractice = window.isInPractice;

/**
 * Oculta el encabezado principal de la aplicación.
 *
 * Añade las clases 'hidden' al encabezado y 'header-hidden' al cuerpo del documento
 * para ocultar visualmente el encabezado y ajustar el diseño de la página.
 * Actualiza la variable global `headerVisible` a `false`.
 */
function hideHeader() {
    const header = document.getElementById('main-header');
    if (header && headerVisible) {
        header.classList.add('hidden');
        document.body.classList.add('header-hidden');
        headerVisible = false;
        window.headerVisible = false;
    }
}

/**
 * Muestra el encabezado principal de la aplicación.
 *
 * Elimina las clases 'hidden' del encabezado y 'header-hidden' del cuerpo del documento
 * para hacer visible el encabezado y restaurar el diseño de la página.
 * Actualiza la variable global `headerVisible` a `true`.
 */
function showHeader() {
    const header = document.getElementById('main-header');
    if (header && !headerVisible) {
        header.classList.remove('hidden');
        document.body.classList.remove('header-hidden');
        headerVisible = true;
        window.headerVisible = true;
    }
}

/**
 * Alterna la visibilidad del menú móvil.
 *
 * Si el usuario está logueado, alterna la clase 'active' en el menú desplegable del usuario
 * y cambia el icono del botón de alternancia. Si no está logueado, alterna la clase 'active'
 * en el menú de navegación principal y cambia el texto del botón de alternancia.
 */
function toggleMobileMenu() {
    const navMenu = document.getElementById('nav-menu'); // This is the .nav-right container
    const mobileToggle = document.getElementById('mobile-toggle');
    const userDropdown = document.querySelector('.user-dropdown'); // The container for the user dropdown

    if (!mobileToggle) return;

    // Check if the user is logged in (based on the global variable set in index.php)
    if (window.userLoggedIn) {
        if (userDropdown) {
            // Ensure navMenu is active so userDropdown can be positioned correctly within it
            if (navMenu && !navMenu.classList.contains('active')) {
                navMenu.classList.add('active');
            }
            userDropdown.classList.toggle('active'); // Toggle active class on the user dropdown
            const iconSpan = mobileToggle.querySelector('.material-icons');
            if (iconSpan) {
                iconSpan.textContent = userDropdown.classList.contains('active') ? 'close' : 'account_circle';
            }
        }
    } else {
        // If not logged in, behave as before (toggle main nav menu)
        if (navMenu) {
            navMenu.classList.toggle('active');
            mobileToggle.textContent = navMenu.classList.contains('active') ? '✕' : '☰';
        }
    }
}

/**
 * Maneja el inicio de una sesión de lectura.
 *
 * Establece la bandera `isReading` a `true` y oculta el encabezado principal.
 */
function onReadingStart() {
    isReading = true;
    // Ocultar el header principal automáticamente al iniciar la lectura
    hideHeader();
}

/**
 * Maneja la detención de una sesión de lectura.
 *
 * Establece la bandera `isReading` a `false` y muestra el encabezado principal.
 */
function onReadingStop() {
    isReading = false;
    showHeader();
}

/**
 * Maneja el inicio de una sesión de práctica.
 *
 * Establece la bandera `isInPractice` a `true` y asegura que el encabezado esté visible.
 */
function onPracticeStart() {
    isInPractice = true;
    // El header ahora se mantiene visible en las pestañas de práctica
    showHeader();
}

/**
 * Maneja el final de una sesión de práctica.
 *
 * Establece la bandera `isInPractice` a `false` y muestra el encabezado principal.
 */
function onPracticeEnd() {
    isInPractice = false;
    showHeader();
}

/**
 * Maneja los clics en el documento para controlar la visibilidad del encabezado.
 *
 * Si el usuario está en modo lectura o práctica y hace clic en un área no interactiva,
 * el encabezado se muestra. Ignora clics en elementos específicos como palabras clickeables,
 * menús flotantes, botones, inputs y tooltips.
 *
 * @param {Event} event - El objeto de evento del clic.
 */
function handleDocumentClick(event) {
    // Sincronizar variables locales con globales por si acaso
    isReading = window.isReading || (window.isCurrentlyReading && !window.isCurrentlyPaused);
    isInPractice = window.isInPractice;

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

/**
 * Muestra el encabezado específicamente en la página de lectura si está oculto y el usuario está leyendo.
 */
function showHeaderOnReadingPage() {
    if (isReading && !headerVisible) {
        showHeader();
    }
}

/**
 * Inicializa las funciones del encabezado cuando el DOM está completamente cargado.
 *
 * Asegura que el encabezado esté visible por defecto, configura los listeners para
 * el menú móvil y la detección de clics en el documento, y maneja la lógica
 * de inicio/fin de lectura y práctica basada en la URL.
 */
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
