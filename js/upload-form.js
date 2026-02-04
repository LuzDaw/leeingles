/**
 * Funciones para el formulario de subir texto
 * Nota: La lógica principal ahora reside en ajax_upload_content.php
 */

/**
 * Muestra el formulario de subida de texto.
 *
 * Si la función `loadTabContent` está disponible (es decir, el usuario está en el dashboard),
 * carga la pestaña 'upload'. De lo contrario, redirige a `index.php` con el parámetro `show_upload=1`.
 */
function showUploadForm() {
    if (typeof loadTabContent === 'function') {
        loadTabContent('upload');
    } else {
        window.location.href = 'index.php?show_upload=1';
    }
}

/**
 * Oculta el formulario de subida de texto.
 *
 * Esta función está obsoleta en el flujo actual de pestañas, pero se mantiene por compatibilidad.
 * Si `loadTabContent` está disponible, carga la pestaña 'my-texts'. De lo contrario, redirige a `index.php`.
 */
function hideUploadForm() {
    if (typeof loadTabContent === 'function') {
        loadTabContent('my-texts');
    } else {
        window.location.href = 'index.php';
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Si hay un parámetro show_upload en la URL y estamos logueados, 
    // asegurar que se cargue la pestaña de subida
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_upload') === '1' && window.userLoggedIn) {
        if (typeof loadTabContent === 'function') {
            loadTabContent('upload');
        }
    }
});
