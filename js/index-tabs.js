/**
 * Sistema de pestañas dinámicas para index.php
 */

/**
 * Alias para la función `loadTabContent`, manteniendo compatibilidad con llamadas antiguas.
 * Si la pestaña es 'textos', la convierte a 'my-texts'.
 *
 * @param {string} tab - El nombre de la pestaña a cargar.
 */
window.cambiarPestana = function(tab) {
    if (tab === 'textos') tab = 'my-texts';
    window.loadTabContent(tab);
};

/**
 * Carga el contenido de una pestaña específica en el dashboard del usuario.
 *
 * Si el usuario no está logueado o no hay un contenedor de pestañas, redirige a la página principal
 * con los parámetros de la pestaña. De lo contrario, realiza una petición AJAX para cargar el contenido
 * dinámicamente, actualiza el estado visual de los botones de pestaña y ejecuta scripts incrustados.
 * También maneja el desplazamiento a un objetivo específico y la traducción de contextos de palabras guardadas.
 *
 * @param {string} tab - El identificador de la pestaña a cargar (ej. 'progress', 'my-texts').
 * @param {boolean} [isPaymentSuccess=false] - Indica si la carga se debe a un éxito de pago.
 * @param {string|null} [scrollTarget=null] - Un ID de elemento al que desplazar la vista después de cargar el contenido.
 */
window.loadTabContent = function(tab, isPaymentSuccess = false, scrollTarget = null) {
    const tabContent = document.getElementById('tab-content');
    
    // Si no estamos en la vista de dashboard (no hay tab-content), redirigir
    if (!tabContent) {
        // SEGURIDAD: No redirigir si el usuario no está logueado (evita bucles)
        if (!window.userLoggedIn) {
            console.warn("Intento de cargar pestaña sin estar logueado:", tab);
            return;
        }
        let url = `index.php?tab=${tab}`;
        if (isPaymentSuccess) url += '&payment_success=1';
        if (scrollTarget) url += `&scroll=${scrollTarget}`;
        window.location.href = url;
        return;
    }

    // Guardar el objetivo de scroll globalmente para que el wrapper lo use
    window._pendingScrollTarget = scrollTarget;

    const tabButtons = document.querySelectorAll('.tab-btn');
    
    // Ocultar menú flotante al entrar en cualquier pestaña
    const floatingMenu = document.getElementById('floating-menu');
    if (floatingMenu) floatingMenu.style.display = 'none';
    
    // Actualizar estados visuales de los botones
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tab) {
            btn.classList.add('active');
        }
    });
    
    // Asegurar que el header sea visible al entrar en las pestañas (excepto si es lectura)
    if (typeof window.showHeader === 'function') {
        window.showHeader();
    }
    
    // Mapear pestañas a archivos AJAX
    const tabFiles = {
        'progress': 'lectura/ajax/ajax_progress_content.php',
        'my-texts': 'ajax/ajax_my_texts_content.php',
        'saved-words': 'ajax/ajax_saved_words_content.php',
        'practice': 'practicas/ajax_practice_content.php',
        'upload': 'ajax/ajax_upload_content.php',
        'account': 'logueo_seguridad/cuenta/ajax_account_content.php'
    };
    
    let ajaxFile = tabFiles[tab];
    if (!ajaxFile) {
        tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #ff8a00;"><p>Error: Pestaña no encontrada</p></div>';
        return;
    }

    // Si es éxito de pago, añadir parámetro al archivo AJAX
    if (tab === 'account' && isPaymentSuccess) {
        ajaxFile += '?payment_success=1';
    }
    
    // Cargar contenido vía AJAX (sin caché para asegurar datos frescos)
    fetch(ajaxFile, {
        cache: 'no-store',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.text())
    .then(data => {
        // SEGURIDAD: Si la respuesta contiene el encabezado completo, es una redirección errónea a index.php
        if (data.includes('<header') || data.includes('<!DOCTYPE')) {
            console.error("Error: Se recibió la página completa en lugar del fragmento AJAX para:", ajaxFile);
            tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #ff8a00;"><p>Error cargando contenido (Redirección detectada). Por favor, recarga la página.</p></div>';
            return;
        }

        // Desplazar al principio al cargar nueva pestaña (a menos que haya un scrollTarget)
        if (!scrollTarget && !new URLSearchParams(window.location.search).get('scroll')) {
            window.scrollTo(0, 0);
        }

        tabContent.innerHTML = data;
        
        // Ejecutar scripts que puedan estar en el contenido cargado
        const scripts = tabContent.querySelectorAll('script');
        scripts.forEach(script => {
            if (script.innerHTML.trim()) {
                eval(script.innerHTML);
            }
        });
        
        // NUEVO: Traducir contextos de palabras guardadas si es la pestaña correspondiente
        if (tab === 'saved-words' && typeof window.translateAllContextsForSavedWords === 'function') {
            setTimeout(window.translateAllContextsForSavedWords, 100);
        }
        
        // Configurar detección de clics fuera de las pestañas para mostrar header
        setupTabClickDetection();

        // Inicializar eventos de pestañas y acciones en lote
        if (typeof window.initializeTabEvents === 'function') {
            window.initializeTabEvents();
        }
        if (typeof window.updateBulkActions === 'function' && document.querySelectorAll('input[name="selected_texts[]"]').length > 0) {
            window.updateBulkActions();
        }
        if (typeof window.updateBulkActionsWords === 'function' && document.querySelectorAll('input[name="selected_words[]"]').length > 0) {
            window.updateBulkActionsWords();
        }

        // Manejar scroll si se solicita
        const target = scrollTarget || new URLSearchParams(window.location.search).get('scroll');
        if (target) {
            setTimeout(() => {
                let elementId = target;
                if (target === 'plans') elementId = 'subscription-plans-section';
                if (target === 'one-time') elementId = 'one-time-payment-section';
                if (target === 'payment-options') elementId = 'payment-options-section';
                
                const el = document.getElementById(elementId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth' });
                }
            }, 800);
        }
    })
    .catch(error => {
        tabContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #ff8a00;"><p>Error cargando contenido. Por favor, intenta de nuevo.</p></div>';
    });
};

/**
 * Sale del modo de pestañas y restaura la vista principal de la aplicación.
 *
 * Muestra el encabezado y el menú flotante, y redirige a la página de inicio
 * si la URL actual contiene parámetros de pestaña.
 */
window.exitTabs = function() {
    // Mostrar header
    if (typeof window.showHeader === 'function') {
        window.showHeader();
    }
    // Mostrar menú flotante al salir de las pestañas
    const floatingMenu = document.getElementById('floating-menu');
    if (floatingMenu) floatingMenu.style.display = 'block';
    
    // Si estamos en index.php con parámetros de pestaña, limpiar la URL
    if (window.location.search.includes('tab=')) {
        window.location.href = 'index.php';
    }
};

/**
 * Configura la detección de clics en el documento para controlar la visibilidad del encabezado
 * cuando el usuario está en el modo de pestañas.
 *
 * Si el encabezado está oculto y el clic no se realiza en un elemento interactivo
 * (enlaces, botones, inputs, etc.), el encabezado se muestra.
 */
window.setupTabClickDetection = function() {
    // Remover listener anterior si existe
    document.removeEventListener('click', handleTabAreaClick);
    // Agregar nuevo listener
    document.addEventListener('click', handleTabAreaClick);
};

/**
 * Manejador de eventos para detectar clics fuera de las áreas interactivas de las pestañas.
 *
 * Si el encabezado está oculto y el clic no se realiza en un elemento que debería
 * mantener el encabezado oculto (como botones o enlaces), entonces muestra el encabezado.
 *
 * @param {Event} event - El objeto de evento del clic.
 */
function handleTabAreaClick(event) {
    const header = document.getElementById('main-header');
    
    // Si el header está oculto
    if (header && header.classList.contains('hidden')) {
        const clickedElement = event.target;
        
        // Lista de elementos que NO deben mostrar el header (solo enlaces y botones)
        const ignoreElements = [
            'a', 'button', 'input', 'select', 'textarea', 'label',
            '[onclick]', '[role="button"]', '.clickable', '.nav-btn',
            '.tab-btn', '.dropdown', '.dropdown-content', '.text-checkbox',
            '.delete-btn', '.primary', '.secondary'
        ];
        
        let shouldIgnore = false;
        for (const selector of ignoreElements) {
            if (clickedElement.matches(selector) || clickedElement.closest(selector)) {
                shouldIgnore = true;
                break;
            }
        }
        
        // Si el clic NO fue en un enlace o botón, mostrar header
        if (!shouldIgnore) {
            if (typeof window.showHeader === 'function') {
                window.showHeader();
            }
        }
    }
}

/**
 * Módulo para gestionar el menú móvil de pestañas.
 *
 * Este IIFE (Immediately Invoked Function Expression) se encarga de la lógica
 * para transformar la navegación de pestañas en un menú hamburguesa en pantallas pequeñas
 * y restaurarla en pantallas grandes. Incluye la creación dinámica de elementos,
 * el manejo de eventos de clic y el ajuste en cambios de tamaño de ventana.
 */
(function() {
    let closeMenuHandler = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        const tabNav = document.querySelector('.tab-navigation.tab-nav-container');
        if (tabNav) {
            let menuToggle = null;
            let buttonsContainer = null;

            /**
             * Configura el menú móvil de pestañas basándose en el ancho de la ventana.
             *
             * Si el ancho es <= 768px, crea un botón de alternancia (hamburguesa) y un contenedor
             * para los botones de las pestañas, moviendo los botones existentes a este contenedor.
             * Configura los listeners para abrir/cerrar el menú.
             * Si el ancho es > 768px, revierte los cambios y restaura la disposición original.
             */
            function setupMobileMenu() {
                // Limpiar listener anterior si existe
                if (closeMenuHandler) {
                    document.removeEventListener('click', closeMenuHandler);
                    closeMenuHandler = null;
                }

                // Limpiar elementos anteriores si existen
                const existingToggle = tabNav.querySelector('.mobile-tab-menu-toggle');
                const existingContainer = tabNav.querySelector('.tab-buttons-container');
                
                if (existingToggle) existingToggle.remove();
                if (existingContainer) {
                    // Si hay un contenedor existente, mover los botones de vuelta
                    const buttonsInContainer = existingContainer.querySelectorAll('.tab-btn');
                    buttonsInContainer.forEach(btn => {
                        tabNav.appendChild(btn);
                    });
                    existingContainer.remove();
                }

                if (window.innerWidth <= 768) {
                    // Guardar referencia a los botones originales
                    const originalButtons = Array.from(tabNav.querySelectorAll('.tab-btn'));
                    
                    // Crear el botón hamburguesa
                    menuToggle = document.createElement('button');
                    menuToggle.className = 'mobile-tab-menu-toggle';
                    menuToggle.innerHTML = '';
                    menuToggle.setAttribute('aria-label', 'Abrir menú de pestañas');
                    
                    // Crear contenedor para los botones
                    buttonsContainer = document.createElement('div');
                    buttonsContainer.className = 'tab-buttons-container';
                    
                    // Mover todos los botones de pestañas al contenedor (no clonar, mover)
                    originalButtons.forEach(btn => {
                        // Omitir el botón de salir y el spacer
                        if (!btn.classList.contains('exit-tab-btn') && !btn.classList.contains('flex-1')) {
                            buttonsContainer.appendChild(btn);
                        }
                    });
                    
                    // Agregar el contenedor al tabNav
                    tabNav.appendChild(buttonsContainer);
                    
                    // Agregar el botón toggle al inicio
                    tabNav.insertBefore(menuToggle, tabNav.firstChild);
                    
                    // Evento para toggle del menú
                    menuToggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        tabNav.classList.toggle('menu-open');
                        menuToggle.innerHTML = tabNav.classList.contains('menu-open') ? '✕' : '';
                        menuToggle.setAttribute('aria-label', tabNav.classList.contains('menu-open') ? 'Cerrar menú de pestañas' : 'Abrir menú de pestañas');
                    });

                    // Cerrar el menú al hacer clic en un botón de pestaña
                    const containerButtons = buttonsContainer.querySelectorAll('.tab-btn');
                    containerButtons.forEach(btn => {
                        btn.addEventListener('click', function() {
                            setTimeout(function() {
                                tabNav.classList.remove('menu-open');
                                if (menuToggle) {
                                    menuToggle.innerHTML = '';
                                    menuToggle.setAttribute('aria-label', 'Abrir menú de pestañas');
                                }
                            }, 100);
                        });
                    });

                    // Cerrar el menú al hacer clic fuera
                    closeMenuHandler = function(e) {
                        if (tabNav.classList.contains('menu-open') && 
                            !tabNav.contains(e.target) && 
                            e.target !== menuToggle) {
                            tabNav.classList.remove('menu-open');
                            if (menuToggle) {
                                menuToggle.innerHTML = '';
                                menuToggle.setAttribute('aria-label', 'Abrir menú de pestañas');
                            }
                        }
                    };
                    document.addEventListener('click', closeMenuHandler);
                } else {
                    // En pantallas grandes, remover elementos móviles y restaurar botones
                    tabNav.classList.remove('menu-open');
                    if (buttonsContainer) {
                        const buttonsInContainer = buttonsContainer.querySelectorAll('.tab-btn');
                        buttonsInContainer.forEach(btn => {
                            tabNav.appendChild(btn);
                        });
                        buttonsContainer.remove();
                    }
                }
            }

            // Configurar al cargar
            setupMobileMenu();

            // Manejar cambios de tamaño de ventana
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    setupMobileMenu();
                }, 250);
            });
        }
    });
})();
