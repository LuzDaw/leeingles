// ============================================
// FUNCIONES DE GESTIÓN DE TEXTOS
// ============================================

// Variables globales (AppState centralizado en global-state.js)

/**
 * Guarda una palabra traducida en la base de datos del usuario.
 *
 * @param {string} word - La palabra original en inglés.
 * @param {string} translation - La traducción de la palabra.
 * @param {string} [sentence=''] - La oración de contexto donde se encontró la palabra.
 * @returns {Promise<boolean>} Una promesa que se resuelve a `true` si la palabra se guardó correctamente, `false` en caso contrario.
 */
window.saveTranslatedWord = async function(word, translation, sentence = '') {
    try {
        let textId = window.AppState && window.AppState.currentTextId;
        if (!textId) {
            // Buscar en el DOM un atributo data-text-id
            const textContainer = document.getElementById('text') || document.querySelector('[data-text-id]') || document.querySelector('#pages-container');
            if (textContainer && textContainer.dataset.textId) {
                textId = textContainer.dataset.textId;
            }
        }
        const formData = new FormData();
        formData.append('word', word);
        formData.append('translation', translation);
        formData.append('context', sentence);
        if (textId) {
            formData.append('text_id', textId);
        }
        const response = await fetch('traduciones/save_translated_word.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            return true;
        } else {
            return false;
        }
    } catch (error) {
        return false;
    }
};

/**
 * Muestra los botones flotantes de menú y de reproducción/pausa.
 *
 * Aplica estilos para hacer visibles los elementos con una transición suave.
 */
window.showFloatingButton = function() {
    const floatingMenu = document.getElementById('floating-menu');
    if (floatingMenu) {
        floatingMenu.style.display = 'block';
        setTimeout(() => {
            floatingMenu.style.opacity = '1';
            floatingMenu.style.transform = 'translateY(0)';
        }, 100);
        const continueBtn = document.getElementById('continue-btn-container');
        if (continueBtn && window.lastReadParagraphIndex > 0) {
            continueBtn.style.opacity = '1';
            continueBtn.style.transform = 'translateY(0)';
        }
    }
    // Mostrar el botón de play flotante
    const floatingPlay = document.getElementById('floating-play');
    if (floatingPlay) {
        floatingPlay.style.display = 'block';
        setTimeout(() => {
            floatingPlay.style.opacity = '1';
            floatingPlay.style.transform = 'translateY(0)';
        }, 100);
    }
}

/**
 * Oculta los botones flotantes de menú y de reproducción/pausa.
 *
 * Establece el estilo `display: 'none'` para ocultar los elementos.
 */
window.hideFloatingButton = function() {
    const floatingMenu = document.getElementById('floating-menu');
    if (floatingMenu) {
        floatingMenu.style.display = 'none';

    }
    // Ocultar el botón de play flotante
    const floatingPlay = document.getElementById('floating-play');
    if (floatingPlay) {
        floatingPlay.style.display = 'none';
    }
}

/**
 * Actualiza el texto y el título del botón flotante de reproducción/pausa
 * basándose en el estado actual de la lectura (leyendo, pausado, detenido).
 */
window.updateFloatingButton = function() {
    const floatingBtn = document.getElementById('floating-btn');
    if (!floatingBtn) return;
    
    if (window.isCurrentlyReading && !window.isCurrentlyPaused) {
        floatingBtn.textContent = '⏸️';
        floatingBtn.title = 'Pausar lectura';
    } else {
        floatingBtn.textContent = '▶️';
        floatingBtn.title = window.isCurrentlyPaused ? 'Continuar lectura' : 'Iniciar lectura';
    }
}

/**
 * Intenta continuar la lectura desde el último párrafo leído.
 *
 * Si la función `startReadingFromParagraph` está disponible, la invoca con los índices guardados.
 */
window.continueFromLastParagraph = function() {
    if (typeof window.startReadingFromParagraph === 'function') {
        window.startReadingFromParagraph(window.lastReadParagraphIndex, window.lastReadPageIndex);
    } else {
        // No iniciar automáticamente; el usuario usará el botón de play
    }
}

/**
 * Carga los textos públicos en el contenedor `public-texts-container`.
 *
 * Realiza una petición AJAX a `index.php` con el parámetro `show_public_texts=1`
 * y actualiza el contenido del contenedor.
 */
function loadPublicTexts() {
    fetch('index.php?show_public_texts=1')
        .then(response => response.text())
        .then(html => {
            document.getElementById('public-texts-container').innerHTML = html;
        })
        .catch(error => {
            // Error silencioso al cargar textos públicos
        });
}

// Auto-actualizar elementos UI
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const elementos = [
            'upload-text-btn',
            'my-texts-btn',
            'public-texts-btn'
        ];

        elementos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.style.opacity = '1';
                elemento.style.transform = 'translateY(0)';
            }
        });

        const continueBtn = document.getElementById('continue-btn-container');
        if (continueBtn) {
            continueBtn.style.opacity = '1';
            continueBtn.style.transform = 'translateY(0)';
        }
    }, 600);
});

// Funcionalidad del formulario de subir texto
document.getElementById('upload-text-btn')?.addEventListener('click', function() {
    actionAfterLogin = 'showUploadForm';
    showUploadForm();
});

document.getElementById('upload-text-btn-user')?.addEventListener('click', function() {
    showUploadForm();
});

document.getElementById('back-to-list')?.addEventListener('click', function() {
    document.getElementById('upload-form-container').style.display = 'none';
    loadUserTexts();
});

// Exportar funciones principales
window.loadPublicTexts = loadPublicTexts;

/**
 * Cuenta el número de palabras en un texto dado.
 *
 * Utiliza la utilidad `TextUtils.countWords`.
 *
 * @param {string} text - El texto a analizar.
 * @returns {number} El número de palabras en el texto.
 */
window.countWordsInText = function(text) {
    return TextUtils.countWords(text);
};

/**
 * Cuenta el número de caracteres alfabéticos (letras) en un texto dado.
 *
 * Utiliza la utilidad `TextUtils.countLetters`.
 *
 * @param {string} text - El texto a analizar.
 * @returns {number} El número de letras en el texto.
 */
window.countLettersInText = function(text) {
    return TextUtils.countLetters(text);
};
