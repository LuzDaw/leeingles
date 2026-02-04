// Funciones del menú flotante de lectura

// Variables globales para controlar visibilidad de traducciones y estado de lectura
// Variables globales (AppState centralizado en global-state.js)
window.translationsVisible = true;

// Parar lectura al salir de la página
window.addEventListener('beforeunload', function() {
    if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
});

/**
 * Alterna la visibilidad de las traducciones simultáneas en el área de lectura.
 *
 * Añade o elimina la clase 'hide-translations' del contenedor principal de páginas
 * y actualiza el texto del botón de alternancia.
 */
window.toggleTranslations = function() {
    const container = document.getElementById('pages-container');
    const button = document.getElementById('toggle-translations-btn');
    
    if (window.translationsVisible) {
        // Ocultar traducciones usando CSS
        if (container) container.classList.add('hide-translations');
        button.textContent = '👁️ Lectura normal';
        window.translationsVisible = false;
    } else {
        // Volver a modo lectura normal
        if (container) container.classList.remove('hide-translations');
        button.textContent = '👁️ Ocultar Traducciones';
        window.translationsVisible = true;
    }
    
    // Asegurar que la variable global sin window también se actualice si existe
    if (typeof translationsVisible !== 'undefined') {
        translationsVisible = window.translationsVisible;
    }
}

/**
 * Alterna la visibilidad del menú flotante de herramientas de lectura.
 *
 * Controla la opacidad, transformación y eventos de puntero del submenú.
 */
let menuOpen = false;
window.toggleFloatingMenu = function() {
    const submenu = document.getElementById('submenu');
    if (!submenu) return;
    
    // Si el menú está abierto, cerrarlo
    if (menuOpen) {
        submenu.style.opacity = '0';
        submenu.style.transform = 'translateY(-10px)';
        submenu.style.pointerEvents = 'none';
        menuOpen = false;
    } else {
        // Si está cerrado, abrirlo
        submenu.style.opacity = '1';
        submenu.style.transform = 'translateY(0)';
        submenu.style.pointerEvents = 'auto';
        menuOpen = true;
    }
};

/**
 * Cierra el menú flotante de herramientas de lectura.
 *
 * Establece la opacidad a 0, aplica una transformación para ocultarlo
 * y desactiva los eventos de puntero.
 */
function closeMenu() {
    const submenu = document.getElementById('submenu');
    if (submenu) {
        submenu.style.opacity = '0';
        submenu.style.transform = 'translateY(-10px)';
        submenu.style.pointerEvents = 'none';
        menuOpen = false;
    }
}

/**
 * Abre el menú flotante de herramientas de lectura.
 *
 * Establece la opacidad a 1, elimina la transformación para mostrarlo
 * y activa los eventos de puntero.
 */
function openMenu() {
    const submenu = document.getElementById('submenu');
    if (submenu) {
        submenu.style.opacity = '1';
        submenu.style.transform = 'translateY(0)';
        submenu.style.pointerEvents = 'auto';
        menuOpen = true;
    }
}

/**
 * Actualiza el texto y el título del botón flotante de reproducción/pausa
 * basándose en el estado actual de la lectura.
 */
window.updateFloatingButton = function() {
    const floatingBtn = document.getElementById('floating-btn');
    if (!floatingBtn) return;

    // Si está leyendo, mostrar botón de detener; en cualquier otro caso, mostrar iniciar
    if (window.isCurrentlyReading) {
        floatingBtn.textContent = '⏹️';
        floatingBtn.title = 'Detener lectura';
    } else {
        floatingBtn.textContent = '▶️';
        floatingBtn.title = 'Iniciar lectura';
    }
}

/**
 * Alterna el estado de reproducción/pausa de la lectura automática.
 *
 * Detiene la lectura si está activa o la inicia si está detenida.
 * Incluye un bloqueo para evitar clics múltiples rápidos.
 *
 * @param {Event} event - El objeto de evento del clic.
 */
let _playPauseLock = false;
window.toggleFloatingPlayPause = function(event) {
    // Evitar que el clic se propague a otros listeners (como el de document en lector.js)
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }

    if (_playPauseLock) {
        return;
    }

    _playPauseLock = true;
    setTimeout(() => { _playPauseLock = false; }, 250);

    const btn = document.getElementById('floating-btn');
    if (!btn) {
        return;
    }

    // Verificación robusta del estado real
    const isActuallySpeaking = window.speechSynthesis && window.speechSynthesis.speaking;
    const shouldStop = window.isCurrentlyReading || isActuallySpeaking;

    if (shouldStop) {
        // Detener completamente
        if (typeof window.stopReading === 'function') {
            window.stopReading();
        } else {
            window.isCurrentlyReading = false;
            window.autoReading = false;
            if (window.speechSynthesis) window.speechSynthesis.cancel();
        }
        btn.textContent = '▶️';
        btn.title = 'Iniciar lectura';
    } else {
        // Iniciar lectura
        if (typeof window.startReading === 'function') {
            window.startReading();
        } else {
            window.isCurrentlyReading = true;
            window.autoReading = true;
        }
        btn.textContent = '⏹️';
        btn.title = 'Detener lectura';
    }
}

/**
 * Intenta continuar la lectura desde el último párrafo leído.
 *
 * Desplaza la vista a la página y párrafo correctos y, si la función
 * `startReadingFromIndex` está disponible, la invoca.
 */
window.continueFromLastParagraph = function() {
    if (window.lastReadParagraphIndex > 0) {
        const pages = document.querySelectorAll('.page');
        // Ir a la página correcta
        if (pages[window.lastReadPageIndex]) {
            pages.forEach((page, index) => {
                page.style.display = index === window.lastReadPageIndex ? '' : 'none';
            });
        }
        
        // Buscar el párrafo en la página actual y empezar desde ahí
        const currentPageParagraphs = document.querySelectorAll('.page[style=""] p');
        if (currentPageParagraphs[window.lastReadParagraphIndex]) {
            if (typeof window.startReadingFromIndex === 'function') {
                window.startReadingFromIndex(window.lastReadParagraphIndex);
            } else if (typeof startReading === 'function') {
                // No auto-inicio silencioso; delegar al botón principal
            }
        }
    }
}

// Funciones para mostrar/ocultar el menú desplegable
let menuVisible = false;
let closeTimeout = null;

// Agregar eventos de hover al menú flotante
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const toolsContainer = document.querySelector('.menu-herramientas-contenedor');
        const submenu = document.getElementById('submenu');
        const menuBtn = document.getElementById('menu-btn');
        
        if (toolsContainer && submenu) {
            // Mostrar menú al hacer hover sobre el contenedor de herramientas
            toolsContainer.addEventListener('mouseenter', function() {
                clearTimeout(closeTimeout);
                openMenu();
            });
            
            // Cerrar menú 500ms después de salir del contenedor
            toolsContainer.addEventListener('mouseleave', function() {
                closeTimeout = setTimeout(() => {
                    closeMenu();
                }, 500);
            });
            
            // Mantener menú visible si hacemos hover sobre él
            submenu.addEventListener('mouseenter', function() {
                clearTimeout(closeTimeout);
                openMenu();
            });
            
            // Cerrar menú 500ms después de salir del submenú
            submenu.addEventListener('mouseleave', function() {
                closeTimeout = setTimeout(() => {
                    closeMenu();
                }, 500);
            });
        }
        
        // Cerrar menú al hacer clic en cualquier botón dentro del submenú (capturing para evitar stopPropagation)
        if (submenu) {
            submenu.addEventListener('click', function(e) {
                const btn = e.target && e.target.closest('button');
                if (btn) {
                    // Cerrar inmediatamente tras la acción
                    closeMenu();
                }
            }, true);
        }
        
        // Cerrar menú al hacer clic fuera del submenú y fuera del botón del menú
        document.addEventListener('click', function(e) {
            if (!menuOpen) return;
            const target = e.target;
            const clickedInsideSubmenu = submenu && submenu.contains(target);
            const clickedMenuBtn = menuBtn && menuBtn.contains(target);
            if (!clickedInsideSubmenu && !clickedMenuBtn) {
                closeMenu();
            }
        }, true);
    }, 600);
});

/**
 * Cierra completamente el menú flotante de herramientas y su submenú.
 *
 * Oculta el botón flotante y cierra el submenú si está abierto.
 */
window.closeFloatingMenu = function() {
    // Ocultar el menú flotante
    const floatingMenu = document.getElementById('floating-menu');
    if (floatingMenu) {
        floatingMenu.style.display = 'none';
    }
    // Cerrar el submenú si está abierto
    closeMenu();
    menuOpen = false;
};

/**
 * Sincroniza el estado visual del botón flotante de reproducción/pausa
 * con el estado real de la lectura de voz.
 *
 * Ajusta el texto y el título del botón para reflejar si la lectura está activa,
 * pausada o detenida, evitando cambios si hay una pausa temporal por interacción del usuario.
 */
window.syncButtonWithReadingState = function() {
    const floatingBtn = document.getElementById('floating-btn');
    if (!floatingBtn) return;

    // Si la lectura está desactivada explícitamente, forzamos el botón a Play
    if (window.autoReading === false && window.isCurrentlyReading === false) {
        if (floatingBtn.textContent === '⏹️') {
            floatingBtn.textContent = '▶️';
            floatingBtn.title = 'Iniciar lectura';
        }
        return;
    }

    const isActuallySpeaking = window.speechSynthesis && window.speechSynthesis.speaking;
    
    // Si hay una pausa temporal por clic, NO sincronizamos para evitar que el botón cambie a Play
    if (window._clickPaused || window._hoverPaused) return;

    // Si está hablando pero el botón muestra play, corregirlo a detener
    if (isActuallySpeaking && floatingBtn.textContent === '▶️') {
        floatingBtn.textContent = '⏹️';
        floatingBtn.title = 'Detener lectura';
        window.isCurrentlyReading = true;
        window.isCurrentlyPaused = false;
    }
    // Si no está hablando pero el botón muestra detener, corregirlo a iniciar
    // Solo si NO estamos en autoReading (que podría estar entre párrafos)
    else if (!isActuallySpeaking && floatingBtn.textContent === '⏹️' && !window.autoReading) {
        floatingBtn.textContent = '▶️';
        floatingBtn.title = 'Iniciar lectura';
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
    }
};

// Sincronizar cada 2 segundos para mantener consistencia
setInterval(window.syncButtonWithReadingState, 2000);
