/**
 * Funciones para el formulario de subir texto
 * Nota: La lógica principal ahora reside en ajax_upload_content.php
 */

// Función para mostrar el formulario de subida (ahora redirige a la pestaña correspondiente)
function showUploadForm() {
    if (typeof loadTabContent === 'function') {
        loadTabContent('upload');
    } else {
        window.location.href = 'index.php?show_upload=1';
    }
}

// Función para ocultar el formulario de subida (obsoleta, mantenida por compatibilidad)
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
