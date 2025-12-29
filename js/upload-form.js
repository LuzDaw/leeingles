// Funciones para el formulario de subir texto
// Las funciones comunes están en common-functions.js

// Función para mostrar el formulario de subida
function showUploadForm() {
    if (typeof DOMUtils !== 'undefined') {
        DOMUtils.showElement('upload-form-container');
    } else {
        const container = document.getElementById('upload-form-container');
        if (container) container.style.display = 'block';
    }
    document.querySelector('.reading-area h2')?.scrollIntoView({ behavior: 'smooth' });
}

// Función para ocultar el formulario de subida
function hideUploadForm() {
    if (typeof DOMUtils !== 'undefined') {
        DOMUtils.hideElement('upload-form-container');
    } else {
        const container = document.getElementById('upload-form-container');
        if (container) container.style.display = 'none';
    }
}

// Función para validar y procesar el formulario de subida
function processUploadForm() {
    const form = typeof DOMUtils !== 'undefined' ? DOMUtils.getElement('upload-form') : document.getElementById('upload-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const content = formData.get('content');
        
        // Validar contenido
        if (typeof ValidationUtils !== 'undefined' && !ValidationUtils.isNotEmpty(content, 'Contenido')) {
            if (typeof MessageUtils !== 'undefined') {
                MessageUtils.showError('upload-messages', 'Debes incluir contenido para el texto.');
            }
            return;
        }
        
        try {
            // Enviar formulario
            let data;
            if (typeof HTTPUtils !== 'undefined') {
                data = await HTTPUtils.postFormData('ajax_upload_text.php', formData);
            } else {
                const response = await fetch('ajax_upload_text.php', {
                    method: 'POST',
                    body: formData
                });
                data = await response.json();
            }
            
            if (data.success) {
                if (typeof MessageUtils !== 'undefined') {
                    MessageUtils.showSuccess('upload-messages', 'Texto subido correctamente. Redirigiendo...');
                }
                if (typeof NavigationUtils !== 'undefined') {
                    NavigationUtils.redirect('index.php', 1500);
                } else {
                    setTimeout(() => window.location.href = 'index.php', 1500);
                }
            } else {
                if (typeof MessageUtils !== 'undefined') {
                    MessageUtils.showError('upload-messages', `Error: ${data.error || 'Error desconocido'}`);
                }
            }
        } catch (error) {
            if (typeof MessageUtils !== 'undefined') {
                MessageUtils.showError('upload-messages', 'Error al procesar la solicitud');
            }
        }
    });
}

// Inicializar cuando el DOM esté listo
if (typeof EventUtils !== 'undefined') {
    EventUtils.onDOMReady(function() {
        // Mostrar formulario si hay parámetro en URL
        if (typeof NavigationUtils !== 'undefined' && NavigationUtils.getURLParam('show_upload') === '1') {
            showUploadForm();
        }
        
        // Procesar formulario
        processUploadForm();
    });
} else {
    // Fallback si EventUtils no está disponible
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar formulario si hay parámetro en URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('show_upload') === '1') {
            showUploadForm();
        }
        
        // Procesar formulario
        processUploadForm();
    });
}
