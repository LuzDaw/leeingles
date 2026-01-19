// ============================================
// FUNCIONES DE GESTIÓN DE TEXTOS
// ============================================

// Variables globales
window.isCurrentlyReading = false;
window.isCurrentlyPaused = false;
window.lastReadParagraphIndex = 0;
window.lastReadPageIndex = 0;

// Guardar palabra traducida
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

// Mostrar botón flotante
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

// Ocultar botón flotante
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

// Actualizar botón flotante
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

// Continuar desde el último párrafo
window.continueFromLastParagraph = function() {
    if (typeof window.startReadingFromParagraph === 'function') {
        window.startReadingFromParagraph(window.lastReadParagraphIndex, window.lastReadPageIndex);
    } else {
        // No iniciar automáticamente; el usuario usará el botón de play
    }
}

// Cargar textos públicos
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

// Devuelve el número de palabras de un texto dado
window.countWordsInText = function(text) {
    return TextUtils.countWords(text);
};

// Devuelve el número de letras (caracteres alfabéticos) de un texto dado
window.countLettersInText = function(text) {
    return TextUtils.countLetters(text);
};
