/**
 * Sistema de pestañas dinámicas para index.php
 */

// Alias para compatibilidad
window.cambiarPestana = function(tab) {
    if (tab === 'textos') tab = 'my-texts';
    window.loadTabContent(tab);
};

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

// Función para salir de las pestañas y mostrar el header
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

// Función para detectar clics fuera de las pestañas y mostrar header
window.setupTabClickDetection = function() {
    // Remover listener anterior si existe
    document.removeEventListener('click', handleTabAreaClick);
    // Agregar nuevo listener
    document.addEventListener('click', handleTabAreaClick);
};

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
