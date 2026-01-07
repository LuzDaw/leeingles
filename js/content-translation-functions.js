// ============================================
// FUNCIONES PARA TRADUCCIONES DE CONTENIDO
// ============================================

// Guardar traducción de contenido
window.saveContentTranslation = async function(textId, content, translation) {
    try {
        const formData = new FormData();
        formData.append('text_id', textId);
        formData.append('content', content);
        formData.append('translation', translation);
        
        const response = await fetch('traduciones/save_content_translation.php', {
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
}

// Obtener traducción de contenido desde la base de datos
window.getContentTranslation = async function(textId) {
    try {
        const isActiveReading = window.autoReading ? '1' : '0';
        const response = await fetch(`traduciones/get_content_translation.php?text_id=${textId}&active_reading=${isActiveReading}`);
        const data = await response.json();
        
        if (data.success && data.translation) {
            return {
                translation: data.translation,
                source: 'database'
            };
        } else {
            return {
                translation: null,
                needs_translation: true
            };
        }
    } catch (error) {

        return {
            translation: null,
            needs_translation: true
        };
    }
}

// Función mejorada para traducir contenido con caché en base de datos
window.translateContentWithCache = async function(englishElement, spanishElement, textId) {
    // Bloquear si se ha alcanzado el límite
    if (window.translationLimitReached) {
        spanishElement.textContent = '';
        return;
    }

    const englishContent = englishElement.textContent.trim();
    
    if (!englishContent) {
        spanishElement.textContent = '';
        return;
    }
    
    // Evitar procesar el mismo elemento múltiples veces
    if (spanishElement.dataset.translated === 'true') {
        return;
    }
    
    // Marcar como procesado
    spanishElement.dataset.translated = 'true';
    
    // Primero intentar obtener desde la base de datos
    const cachedTranslation = await getContentTranslation(textId);
    
    // Verificar límite en la respuesta de getContentTranslation
    if (window.LimitModal && window.LimitModal.checkResponse(cachedTranslation)) {
        spanishElement.textContent = '';
        return;
    }

    if (cachedTranslation.translation) {
        // Usar traducción de la base de datos
        spanishElement.textContent = cachedTranslation.translation;
        spanishElement.style.color = '#eaa827';
        spanishElement.style.fontWeight = '500';
        
        // Incrementar uso real aunque venga de caché
        if (window.incrementUsageOnly) {
            window.incrementUsageOnly(englishContent);
        }
        return;
    }
    
    // Si no hay traducción en BD, traducir y guardar
    try {
        const isActiveReading = window.autoReading ? '1' : '0';
        const response = await fetch('traduciones/translate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'word=' + encodeURIComponent(englishContent) + '&active_reading=' + isActiveReading
        });

        const data = await response.json();

        // Verificar límite en la respuesta de translate.php
        if (window.LimitModal && window.LimitModal.checkResponse(data)) {
            spanishElement.textContent = '';
            return;
        }

        if (data.translation) {
            // Mostrar traducción
            spanishElement.textContent = data.translation;
            spanishElement.style.color = '#eaa827';
            spanishElement.style.fontWeight = '500';
            
            // Guardar en base de datos
            if (textId) {
                await saveContentTranslation(textId, englishContent, data.translation);
            }
        } else {
            spanishElement.textContent = '';
        }
    } catch (error) {
        spanishElement.textContent = '';
    }
}

// Función para mostrar/ocultar traducción de contenido
window.toggleContentTranslation = async function(textId, englishElement, spanishElement) {
    if (!spanishElement) {
        return;
    }
    
    // Si ya está traducido, alternar visibilidad
    if (spanishElement.dataset.translated === 'true') {
        if (spanishElement.style.display === 'none') {
            spanishElement.style.display = 'block';
        } else {
            spanishElement.style.display = 'none';
        }
        return;
    }
    
    // Si no está traducido, traducir
    await translateContentWithCache(englishElement, spanishElement, textId);
}

// Función para inicializar traducciones de contenido en una página
window.initContentTranslations = async function() {
    // Bloquear si se ha alcanzado el límite
    if (window.translationLimitReached) {
        return;
    }

    const contentElements = document.querySelectorAll('[data-content-translation]');
    
    for (const element of contentElements) {
        const textId = element.dataset.textId;
        const spanishElement = document.querySelector(`[data-content-translation-target="${textId}"]`);
        
        if (textId && spanishElement) {
            // Verificar si ya hay traducción en BD
            const cachedTranslation = await getContentTranslation(textId);
            
            // Verificar límite (aunque en init suele ser seguro, mejor prevenir)
            if (window.LimitModal && window.LimitModal.checkResponse(cachedTranslation)) {
                continue;
            }

            if (cachedTranslation.translation) {
                spanishElement.textContent = cachedTranslation.translation;
                spanishElement.style.color = '#eaa827';
                spanishElement.style.fontWeight = '500';
                spanishElement.dataset.translated = 'true';
                
                // Incrementar uso real aunque venga de caché
                if (window.incrementUsageOnly) {
                    window.incrementUsageOnly(element.textContent.trim());
                }
            }
        }
    }
}
