/**
 * FUNCIONES COMUNES - Centralización de código duplicado
 * 
 * Este archivo contiene funciones que se repiten en múltiples archivos JavaScript
 * para evitar duplicación y mantener consistencia en toda la aplicación.
 */

/**
 * Utilidades para manipulación del DOM
 */
window.DOMUtils = {
    
    /**
     * Obtiene un elemento del DOM con validación
     * @param {string} id - ID del elemento
     * @param {boolean} required - Si es requerido, lanza error si no existe
     * @returns {HTMLElement|null} Elemento del DOM
     */
    getElement: function(id, required = false) {
        const element = document.getElementById(id);
        return element;
    },
    
    /**
     * Muestra un elemento del DOM
     * @param {string} id - ID del elemento
     * @param {string} display - Tipo de display (default: 'block')
     */
    showElement: function(id, display = 'block') {
        const element = this.getElement(id);
        if (element) {
            element.style.display = display;
        }
    },
    
    /**
     * Oculta un elemento del DOM
     * @param {string} id - ID del elemento
     */
    hideElement: function(id) {
        const element = this.getElement(id);
        if (element) {
            element.style.display = 'none';
        }
    },
    
    /**
     * Actualiza el contenido HTML de un elemento
     * @param {string} id - ID del elemento
     * @param {string} html - Contenido HTML
     */
    updateHTML: function(id, html) {
        const element = this.getElement(id);
        if (element) {
            element.innerHTML = html;
        }
    },
    
     /**
     * Actualiza el texto de un elemento
     * @param {string} id - ID del elemento
     * @param {string} text - Texto a mostrar
     */
    updateText: function(id, text) {
        const element = this.getElement(id);
        if (element) {
            element.textContent = text;
        }
    },
    
    /**
     * Actualiza el valor de un elemento
     * @param {string} id - ID del elemento
     * @param {string} value - Valor a establecer
     */
    updateValue: function(id, value) {
        const element = this.getElement(id);
        if (element) {
            element.value = value;
        }
    },
    
    /**
     * Actualiza el ancho de un elemento (para barras de progreso)
     * @param {string} id - ID del elemento
     * @param {number} percentage - Porcentaje (0-100)
     */
    updateProgress: function(id, percentage) {
        const element = this.getElement(id);
        if (element) {
            element.style.width = percentage + '%';
        }
    },
    
    /**
     * Enfoca un elemento del DOM
     * @param {string} id - ID del elemento
     */
    focusElement: function(id) {
        const element = this.getElement(id);
        if (element) {
            element.focus();
        }
    }
};

/**
 * Utilidades para peticiones HTTP
 */
window.HTTPUtils = {
    
    /**
     * Realiza una petición POST
     * @param {string} url - URL de destino
     * @param {Object|FormData} data - Datos a enviar
     * @param {Object} options - Opciones adicionales
     * @returns {Promise} Promise con la respuesta
     */
    post: async function(url, data, options = {}) {
        const config = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...options.headers
            },
            ...options
        };
        
        // Si es FormData, no establecer Content-Type
        if (data instanceof FormData) {
            delete config.headers['Content-Type'];
            config.body = data;
        } else if (typeof data === 'object') {
            config.body = new URLSearchParams(data).toString();
        } else {
            config.body = data;
        }
        
        try {
            const response = await fetch(url, config);
            return await response.json();
        } catch (error) {
            throw error;
        }
    },
    
    /**
     * Realiza una petición GET
     * @param {string} url - URL de destino
     * @param {Object} options - Opciones adicionales
     * @returns {Promise} Promise con la respuesta
     */
    get: async function(url, options = {}) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                ...options
            });
            return await response.json();
        } catch (error) {
            throw error;
        }
    },
    
    /**
     * Realiza una petición POST con FormData
     * @param {string} url - URL de destino
     * @param {FormData} formData - FormData a enviar
     * @returns {Promise} Promise con la respuesta
     */
    postFormData: async function(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            throw error;
        }
    }
};

/**
 * Utilidades para manejo de eventos
 */
window.EventUtils = {
    
    /**
     * Añade un event listener con validación
     * @param {string} id - ID del elemento
     * @param {string} event - Tipo de evento
     * @param {Function} handler - Función manejadora
     * @param {Object} options - Opciones del evento
     */
    addListener: function(id, event, handler, options = {}) {
        const element = DOMUtils.getElement(id);
        if (element) {
            element.addEventListener(event, handler, options);
        }
    },
    
    /**
     * Añade un event listener opcional (no falla si el elemento no existe)
     * @param {string} id - ID del elemento
     * @param {string} event - Tipo de evento
     * @param {Function} handler - Función manejadora
     * @param {Object} options - Opciones del evento
     */
    addOptionalListener: function(id, event, handler, options = {}) {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener(event, handler, options);
        }
    },
    
    /**
     * Añade un event listener para DOMContentLoaded
     * @param {Function} handler - Función manejadora
     */
    onDOMReady: function(handler) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', handler);
        } else {
            handler();
        }
    }
};

/**
 * Utilidades para manejo de mensajes
 */
window.MessageUtils = {
    
    /**
     * Muestra un mensaje de éxito
     * @param {string} id - ID del contenedor
     * @param {string} message - Mensaje a mostrar
     */
    showSuccess: function(id, message) {
        DOMUtils.updateHTML(id, `<p style="color: green;">${message}</p>`);
    },
    
    /**
     * Muestra un mensaje de error
     * @param {string} id - ID del contenedor
     * @param {string} message - Mensaje a mostrar
     * @param {number} duration - Duración en milisegundos (default: 3000)
     */
    showError: function(id, message, duration = 3000) {
        const element = DOMUtils.getElement(id);
        if (element) {
            DOMUtils.updateHTML(id, `<p style="color: #dc3545; margin: 0;">${message}</p>`);
            element.style.display = 'block';
            element.style.opacity = '1'; // Asegurar que sea visible para la transición

            setTimeout(() => {
                element.style.opacity = '0';
                setTimeout(() => {
                    element.style.display = 'none';
                    MessageUtils.clearMessages(id); // Limpiar el contenido después de ocultar
                }, 300); // Coincidir con la duración de la transición CSS
            }, duration);
        }
    },
    
    /**
     * Muestra un mensaje de información
     * @param {string} id - ID del contenedor
     * @param {string} message - Mensaje a mostrar
     */
    showInfo: function(id, message) {
        DOMUtils.updateHTML(id, `<p style="color: blue;">${message}</p>`);
    },
    
    /**
     * Limpia los mensajes de un contenedor
     * @param {string} id - ID del contenedor
     */
    clearMessages: function(id) {
        DOMUtils.updateHTML(id, '');
    }
};

/**
 * Utilidades para validación
 */
window.ValidationUtils = {
    
    /**
     * Valida que un campo no esté vacío
     * @param {string} value - Valor a validar
     * @param {string} fieldName - Nombre del campo para el mensaje
     * @returns {boolean} True si es válido
     */
    isNotEmpty: function(value, fieldName = 'Campo') {
        if (!value || value.trim() === '') {
            return false;
        }
        return true;
    },
    
     /**
     * Valida que dos contraseñas coincidan
     * @param {string} password - Contraseña
     * @param {string} confirmPassword - Confirmación de contraseña
     * @returns {boolean} True si coinciden
     */
    passwordsMatch: function(password, confirmPassword) {
        if (password !== confirmPassword) {
            return false;
        }
        return true;
    },
    
    /**
     * Valida que un valor sea un número
     * @param {*} value - Valor a validar
     * @returns {boolean} True si es un número válido
     */
    isNumber: function(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    },

    /**
     * Valida que un email sea válido
     * @param {string} email - Email a validar
     * @returns {boolean} True si es un email válido
     */
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
};

/**
 * Utilidades para navegación
 */
window.NavigationUtils = {
    
    /**
     * Redirige a una URL
     * @param {string} url - URL de destino
     * @param {number} delay - Delay en milisegundos (opcional)
     */
    redirect: function(url, delay = 0) {
        if (delay > 0) {
            setTimeout(() => {
                window.location.href = url;
            }, delay);
        } else {
            window.location.href = url;
        }
    },
    
    /**
     * Recarga la página
     * @param {number} delay - Delay en milisegundos (opcional)
     */
    reload: function(delay = 0) {
        if (delay > 0) {
            setTimeout(() => {
                location.reload();
            }, delay);
        } else {
            location.reload();
        }
    },
    
    /**
     * Obtiene parámetros de la URL
     * @param {string} param - Nombre del parámetro
     * @returns {string|null} Valor del parámetro
     */
    getURLParam: function(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    },
    
    /**
     * Añade parámetros a la URL actual
     * @param {Object} params - Parámetros a añadir
     */
    addURLParams: function(params) {
        const url = new URL(window.location);
        Object.keys(params).forEach(key => {
            url.searchParams.set(key, params[key]);
        });
        window.history.pushState({}, '', url);
    }
};

/**
 * Utilidades para procesamiento de texto
 */
window.TextUtils = {
    /**
     * Devuelve el número de palabras de un texto dado
     * @param {string} text - Texto a procesar
     * @returns {number} Número de palabras
     */
    countWords: function(text) {
        if (!text || typeof text !== 'string') return 0;
        return text.trim().split(/\s+/).filter(Boolean).length;
    },

    /**
     * Devuelve el número de letras (caracteres alfabéticos) de un texto dado
     * @param {string} text - Texto a procesar
     * @returns {number} Número de letras
     */
    countLetters: function(text) {
        if (!text || typeof text !== 'string') return 0;
        const matches = text.match(/[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ]/g);
        return matches ? matches.length : 0;
    }
};

/**
 * Utilidades para traducción
 */
window.TranslationUtils = {
    
    /**
     * Traduce una palabra usando la API
     * @param {string} word - Palabra a traducir
     * @param {string} from - Idioma origen
     * @param {string} to - Idioma destino
     * @returns {Promise} Promise con la traducción
     */
    translate: async function(word, from = 'en', to = 'es') {
        try {
            const response = await HTTPUtils.post('traduciones/translate.php', {
                text: word,
                from: from,
                to: to
            });
            return response.translation || response.text || word;
        } catch (error) {
            return word; // Retorna la palabra original si falla
        }
    }
};

/**
 * Utilidades para sonidos
 */
window.SoundUtils = {
    
    /**
     * Reproduce un sonido de éxito
     */
    playSuccess: function() {
        // Implementar cuando se añadan sonidos

    },
    
    /**
     * Reproduce un sonido de error
     */
    playError: function() {
        // Implementar cuando se añadan sonidos

    }
};

/**
 * Función para cambiar entre pestañas
 * @param {string} tabName - Nombre de la pestaña a mostrar
 */
window.switchToTab = function(tabName) {
    // Mapear nombres de pestañas a los nombres correctos del sistema
    const tabMapping = {
        'texts': 'my-texts',        // Mapear 'texts' a 'my-texts'
        'saved-words': 'saved-words' // Mantener 'saved-words' igual
    };
    
    // Obtener el nombre correcto de la pestaña
    const correctTabName = tabMapping[tabName] || tabName;
    
    // Usar el sistema de pestañas dinámico si está disponible
    if (typeof window.loadTabContent === 'function') {
        window.loadTabContent(correctTabName);
    } else {
        // Fallback: recargar la página si no está disponible el sistema dinámico
        window.location.href = `index.php?tab=${correctTabName}`;
    }
};

/**
 * Inicialización de funciones comunes
 */
EventUtils.onDOMReady(function() {
    // Log removido - sistema de logs eliminado
    
    // Configurar listeners globales si es necesario
    // Por ejemplo, para cerrar modales al hacer clic fuera
    
    document.addEventListener('click', function(e) {
        // Cerrar dropdowns al hacer clic fuera
        if (e.target.closest('.dropdown') === null) {
            const dropdowns = document.querySelectorAll('.dropdown.show');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DOMUtils: window.DOMUtils,
        HTTPUtils: window.HTTPUtils,
        EventUtils: window.EventUtils,
        MessageUtils: window.MessageUtils,
        ValidationUtils: window.ValidationUtils,
        NavigationUtils: window.NavigationUtils,
        TextUtils: window.TextUtils,
        TranslationUtils: window.TranslationUtils,
        SoundUtils: window.SoundUtils
    };
}
