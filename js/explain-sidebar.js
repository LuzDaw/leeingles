/**
 * SISTEMA DE EXPLICACIONES CON SIDEBAR
 * Similar a la imagen de referencia - despliega sidebar desde la derecha
 */

class ExplainSidebar {
    constructor() {
        this.isOpen = false;
        this.currentWord = '';
        this.sidebar = null;
        this.overlay = null;
        this.explainBtn = null;
        this.closeBtn = null;
        this.floatingBtn = null;
        
        this.init();
    }
    
    init() {
        // Obtener elementos
        this.sidebar = document.getElementById('explainSidebar');
        this.overlay = document.getElementById('sidebarOverlay');
        this.explainBtn = document.getElementById('explainBtn');
        this.closeBtn = document.getElementById('closeSidebar');
        this.floatingBtn = document.getElementById('explainFloatingBtn');
        
        // Event listeners
        if (this.explainBtn) {
            this.explainBtn.addEventListener('click', this.toggleSidebar.bind(this));
        }
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', this.closeSidebar.bind(this));
        }
        if (this.overlay) {
            this.overlay.addEventListener('click', this.closeSidebar.bind(this));
        }
        if (this.floatingBtn) {
            this.floatingBtn.addEventListener('click', () => {
                this.showHighlightedWordExplanation();
            });
        }
        
        // Event listener para el botón de pronunciación
        document.addEventListener('click', (event) => {
            if (event.target.closest('.pronounce-btn')) {
                this.pronounceWord();
            }
        });
        
        // Event listeners para palabras destacadas
        document.addEventListener('click', this.onWordClick.bind(this));
        
        // Cerrar con ESC
        document.addEventListener('keydown', this.onKeyDown.bind(this));
        
        // Mostrar botón flotante cuando hay texto
        this.showFloatingButton();
    }
    
    onWordClick(event) {
        const highlightedWord = event.target.closest('.highlighted-word');
        const clickableWord = event.target.closest('.clickable-word');
        const wordHighlighted = event.target.closest('.word-highlighted');
        
        if (highlightedWord) {
            if (window.pauseReading) window.pauseReading('word-click');
            const word = highlightedWord.getAttribute('data-word') || highlightedWord.textContent.trim();
            this.showExplanation(word, highlightedWord);
        } else if (clickableWord) {
            if (window.pauseReading) window.pauseReading('word-click');
            const word = clickableWord.textContent.trim();
            this.showExplanation(word, clickableWord);
        } else if (wordHighlighted) {
            if (window.pauseReading) window.pauseReading('word-click');
            const word = wordHighlighted.textContent.trim();
            this.showExplanation(word, wordHighlighted);
        }
    }
    
    onKeyDown(event) {
        if (event.key === 'Escape' && this.isOpen) {
            this.closeSidebar();
        }
    }
    
    toggleSidebar() {
        if (this.isOpen) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
    
    openSidebar() {
        this.isOpen = true;
        this.sidebar.classList.add('open');
        this.overlay.classList.add('active');
        if (this.explainBtn) {
            this.explainBtn.classList.add('active');
        }
        if (this.floatingBtn) {
            this.floatingBtn.classList.add('active');
        }
        
        // Ajustar contenido principal
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('sidebar-open');
        }
        // Limpiar estado de hover (si lo hubiera) y pausar lectura al abrir
        if (typeof window.clearHoverState === 'function') window.clearHoverState();
        // Asegurar que 'word-click' no quede activo como motivo de pausa
        try { if (window.ReadingPauseReasons) window.ReadingPauseReasons.delete('word-click'); } catch(e) {}
        if (window.pauseReading) window.pauseReading('explain');
        // Marcar explícitamente hover como no activo
        try { window._hoverPaused = false; } catch(e) {}
        
    }
    
    closeSidebar() {
        this.isOpen = false;
        this.sidebar.classList.remove('open');
        this.overlay.classList.remove('active');
        if (this.explainBtn) {
            this.explainBtn.classList.remove('active');
        }
        if (this.floatingBtn) {
            this.floatingBtn.classList.remove('active');
        }
        
        // Restaurar contenido principal
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.remove('sidebar-open');
        }
        
        // Limpiar destacado de palabra al cerrar sidebar
        if (window.clearWordHighlight) {
            window.clearWordHighlight();
        }
        // Reanudar lectura automática exactamente donde se detuvo
        if (typeof window.clearHoverState === 'function') window.clearHoverState();
        // Limpiar todas las razones de pausa relacionadas con el sidebar
        try { 
            if (window.ReadingPauseReasons) {
                window.ReadingPauseReasons.delete('explain');
                window.ReadingPauseReasons.delete('word-click');
                window.ReadingPauseReasons.delete('word-hover');
            }
        } catch(e) {}
        if (window.resumeReading) window.resumeReading({ reason: 'explain', force: true });
        
    }
    
    async showExplanation(word, element = null) {
        this.currentWord = word;
        
        // Abrir sidebar si no está abierto
        if (!this.isOpen) {
            this.openSidebar();
        }
        
        // Actualizar palabra en el sidebar (sin traducción por ahora)
        this.updateWordDisplay(word);
        
        // Mostrar loading
        this.showLoading();
        
        try {
            // Obtener datos del diccionario híbrido
            const data = await this.fetchWordData(word);
            
            // Si hay definición en inglés, traducirla al español
            if (data.definition) {
                try {
                    const definitionTranslation = await this.getWordTranslation(data.definition);
                    data.definition_es = definitionTranslation;
                } catch (translationError) {
                    data.definition_es = '';
                }
            }
            
            await this.updateSidebarContent(data);
            
            // Actualizar la traducción de la palabra usando el sistema existente
            this.getWordTranslation(word).then(translation => {
                this.updateWordDisplay(word, translation);
            });
        } catch (error) {
            this.showError(word);
        }
    }
    
    showFloatingButton() {
        // Mostrar botón flotante si hay texto en la página
        const textContainer = document.querySelector('.reading-area, #text');
        if (textContainer && textContainer.textContent.trim().length > 0) {
            if (this.floatingBtn) {
                setTimeout(() => {
                    this.floatingBtn.classList.add('show');
                }, 500);
            }
        }
        
        // También verificar si hay palabras clickeables
        const clickableWords = document.querySelectorAll('.clickable-word');
        if (clickableWords.length > 0 && this.floatingBtn) {
            setTimeout(() => {
                this.floatingBtn.classList.add('show');
            }, 500);
        }
    }
    
    // Función para mostrar explicación de la palabra destacada
    showHighlightedWordExplanation() {
        if (window.currentHighlightedWord) {
            const { element, word } = window.currentHighlightedWord;
            this.showExplanation(word, element);
        } else {
            // Si no hay palabra destacada, abrir sidebar vacío
            this.openSidebar();
            this.updateWordDisplay('Selecciona una palabra');
            this.showError('No hay palabra seleccionada');
        }
    }
    
    hideFloatingButton() {
        if (this.floatingBtn) {
            this.floatingBtn.classList.remove('show');
        }
    }
    
    updateWordDisplay(word, translation = null) {
        const selectedWordElement = document.getElementById('selectedWord');
        const translationElement = document.getElementById('wordTranslation');
        
        if (selectedWordElement) {
            selectedWordElement.textContent = word;
        }
        
        if (translationElement) {
            if (translation) {
                translationElement.textContent = '- ' + translation;
            } else {
                // Fallback a traducciones básicas si no hay traducción de la API
                translationElement.textContent = '- ' + this.getTranslation(word);
            }
        }
    }
    
    async getWordTranslation(word) {
        try {
            const response = await fetch('translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'word=' + encodeURIComponent(word)
            });
            
            const data = await response.json();
            
            if (data.translation) {
                return data.translation;
            } else {
                // Fallback a traducciones básicas si no hay traducción
                return this.getTranslation(word);
            }
        } catch (error) {
            // Fallback a traducciones básicas
            return this.getTranslation(word);
        }
    }
    

    
    getTranslation(word) {
        // Traducciones básicas - aquí podrías integrar con tu sistema de traducción
        const translations = {
            'network': 'red',
            'computer': 'computadora',
            'system': 'sistema',
            'management': 'gestión',
            'planning': 'planificación',
            'services': 'servicios',
            'internet': 'internet',
            'safety': 'seguridad',
            'availability': 'disponibilidad',
            'technology': 'tecnología',
            'information': 'información',
            'development': 'desarrollo',
            'learning': 'aprendizaje',
            'education': 'educación',
            'business': 'negocio',
            'thanks': 'gracias',
            'machine': 'máquina'
        };
        
        return translations[word.toLowerCase()] || 'traducción';
    }
    
    showLoading() {
        const explanationText = document.getElementById('explanationText');
        
        if (explanationText) {
            explanationText.innerHTML = '<p>Cargando explicación...</p>';
        }
    }
    
    async fetchWordData(word) {
        try {
            // Usar el nuevo sistema de diccionario Merriam-Webster
            const response = await fetch(`diccionario.php?palabra=${encodeURIComponent(word)}`);
            const data = await response.json();
            
            if (!data.definicion) {
        
                return this.getMockData(word);
            }
            
            // Procesar la respuesta del diccionario
            return this.parseApiResponse(data);
            
        } catch (error) {
            return this.getMockData(word);
        }
    }
    
    parseApiResponse(data) {
        const result = {
            word: data.word || '',
            definition: '',
            definition_es: '',
            grammatical_info: '',
            usage_notes: '',
            synonyms: [],
            synonyms_es: [],
            antonyms: [],
            antonyms_es: [],
            examples: [],
            examples_es: [],
            pronunciacion: '',
            audio: ''
        };
        
        // Procesar datos del nuevo endpoint de diccionario
        result.definition = data.definicion || '';
        result.grammatical_info = data.categoria || '';
        result.synonyms = data.sinonimos || [];
        result.antonyms = data.antonimos || [];
        result.examples = data.ejemplos || [];
        result.examples_es = data.ejemplos_es || [];
        result.pronunciacion = data.pronunciacion || '';
        result.audio = data.audio || '';
        
        // Para las traducciones al español, usar el sistema existente
        // getWordTranslation() se encargará de traducir la definición
        if (result.definition) {
            // La definición en español se obtendrá después via translate.php
            result.definition_es = '';
        }
        

        return result;
    }
    
    getMockData(word) {
        // NO MÁS DATOS MOCK - SOLO DATOS REALES DE LA API
        return {
            definition: '',
            definition_es: '',
            usage_notes: '',
            synonyms: [],
            synonyms_es: [],
            examples: [],
            examples_es: []
        };
    }
    
    async updateSidebarContent(data) {
        const explanationText = document.getElementById('explanationText');
        
        let explanationHTML = '';
        
        // Significado (traducción) - SOLO si hay datos reales
        if (data.definition_es && data.definition_es.trim()) {
            explanationHTML += `<div class="explain-section significado-section"><div class="section-title"><strong>Significado</strong></div><div class="section-content">${data.definition_es}</div></div>`;
        }
        
                // Definición en inglés - SOLO si hay datos reales
        if (data.definition && data.definition.trim() && data.definition !== data.definition_es) {
            explanationHTML += `<div class="explain-section definicion-section"><div class="section-title"><strong>En inglés</strong></div><div class="section-content">${data.definition}</div></div>`;
        }
        
        // Pronunciación y audio - SOLO si están disponibles
        if (data.pronunciacion || data.audio) {
            let audioHTML = '';
            if (data.pronunciacion) {
                audioHTML += `<span class="pronunciacion">${data.pronunciacion}</span>`;
            }
            if (data.audio) {
                audioHTML += `<button class="audio-btn" onclick="new Audio('${data.audio}').play()" title="Reproducir pronunciación">🔊</button>`;
            }
            explanationHTML += `<div class="explain-section audio-section"><div class="section-title"><strong>Pronunciación</strong></div><div class="section-content">${audioHTML}</div></div>`;
        }
        
        // Categoría gramatical - SOLO si hay datos reales
        if (data.grammatical_info && data.grammatical_info.trim()) {
            explanationHTML += `<div class="explain-section categoria-section"><div class="section-title"><strong>Categoría</strong></div><div class="section-content">${data.grammatical_info}</div></div>`;
        }
        
        // Uso (información gramatical) - SOLO si hay datos reales
        if (data.usage_notes && data.usage_notes.trim()) {
            explanationHTML += `<div class="explain-section uso-section"><div class="section-title"><strong>Uso</strong></div><div class="section-content">${data.usage_notes}</div></div>`;
        }
        
        // Sinónimos - SOLO si hay datos reales
        if (data.synonyms && data.synonyms.length > 0) {
            // Obtener la traducción de la palabra principal para el título
            const mainWordTranslation = await this.getWordTranslation(this.currentWord);
            const titleText = mainWordTranslation ? 
                `Sinónimos de '${this.currentWord} - ${mainWordTranslation}'` : 
                `Sinónimos de '${this.currentWord}'`;
            
            explanationHTML += `<div class="explain-section sinonimos-section"><div class="section-title"><strong>${titleText}</strong></div><div class="section-content sinonimos-list">`;
            explanationHTML += data.synonyms.map((s, i) => {
                return `<div class='sinonimo-item' data-sinonimo="${s}" data-index="${i}">
                    <span class='sinonimo-en'>${s}</span>
                    <span class='sinonimo-es' id="sinonimo-es-${i}">Traduciendo...</span>
                </div>`;
            }).join('');
            explanationHTML += `</div></div>`;
            
            // Traducir todos los sinónimos en paralelo
            this.translateSynonyms(data.synonyms);
        }
        
        // Antónimos - SOLO si hay datos reales
        if (data.antonyms && data.antonyms.length > 0) {
            explanationHTML += `<div class="explain-section antonimos-section"><div class="section-title"><strong>Antónimos</strong></div><div class="section-content antonimos-list">`;
            explanationHTML += data.antonyms.map((a, i) => {
                return `<div class='antonimo-item' data-antonimo="${a}" data-index="${i}">
                    <span class='antonimo-en'>${a}</span>
                    <span class='antonimo-es' id="antonimo-es-${i}">Traduciendo...</span>
                </div>`;
            }).join('');
            explanationHTML += `</div></div>`;
            
            // Traducir todos los antónimos en paralelo
            this.translateAntonyms(data.antonyms);
        }
        
        // Ejemplos de uso - SOLO si hay datos reales
        if (data.examples && data.examples.length > 0) {
            explanationHTML += `<div class="explain-section ejemplos-section"><div class="section-title"><strong>Ejemplos</strong></div><div class="section-content ejemplos-list">`;
            explanationHTML += data.examples.map((ex, i) => {
                return `<div class='ejemplo-item' data-ejemplo="${ex}" data-index="${i}">
                    <span class='ejemplo-en'>${ex}</span>
                    <span class='ejemplo-es' id="ejemplo-es-${i}">Traduciendo...</span>
                </div>`;
            }).join('');
            explanationHTML += `</div></div>`;
            
            // Traducir todos los ejemplos en paralelo
            this.translateExamples(data.examples);
        }
        
        // Si no hay ningún dato real, mostrar mensaje
        if (!explanationHTML.trim()) {
            explanationHTML = `<div class="explain-section"><div class="section-title"><strong>Información</strong></div><div class="section-content">No se encontró información para esta palabra en las APIs.</div></div>`;
        }
        
        // Añadir información de la fuente API
        if (data.source) {
            explanationHTML += `<div class="explain-section fuente-section"><div class="section-title"><strong>Fuente</strong></div><div class="section-content">${data.source}</div></div>`;
        }
        

        explanationText.innerHTML = explanationHTML;
    }
    
    showError(word) {
        const explanationText = document.getElementById('explanationText');
        
        if (explanationText) {
            explanationText.innerHTML = `<p>No se pudo obtener la explicación para "${word}".</p>`;
        }
    }
    
    // Función para traducir ejemplos usando el sistema híbrido
    async translateExamples(examples) {
        const promises = examples.map(async (example, index) => {
            try {
                const response = await fetch('translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'word=' + encodeURIComponent(example)
                });
                
                const data = await response.json();
                const translationElement = document.getElementById(`ejemplo-es-${index}`);
                
                if (translationElement && data.translation) {
                    translationElement.textContent = data.translation;
                    translationElement.style.color = '#1976d2';
                } else if (translationElement) {
                    translationElement.textContent = 'No se pudo traducir';
                    translationElement.style.color = '#999';
                }
            } catch (error) {
                const translationElement = document.getElementById(`ejemplo-es-${index}`);
                if (translationElement) {
                    translationElement.textContent = 'Error en traducción';
                    translationElement.style.color = '#f44336';
                }
            }
        });
        
        // Esperar a que todas las traducciones se completen
        await Promise.all(promises);
    }
    
    // Función para traducir sinónimos usando el sistema híbrido
    async translateSynonyms(synonyms) {
        const promises = synonyms.map(async (synonym, index) => {
            try {
                const response = await fetch('translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'word=' + encodeURIComponent(synonym)
                });
                
                const data = await response.json();
                const translationElement = document.getElementById(`sinonimo-es-${index}`);
                
                if (translationElement && data.translation) {
                    translationElement.textContent = data.translation;
                    translationElement.style.color = '#1976d2';
                } else if (translationElement) {
                    translationElement.textContent = 'No se pudo traducir';
                    translationElement.style.color = '#999';
                }
            } catch (error) {
                const translationElement = document.getElementById(`sinonimo-es-${index}`);
                if (translationElement) {
                    translationElement.textContent = 'Error en traducción';
                    translationElement.style.color = '#f44336';
                }
            }
        });
        
        // Esperar a que todas las traducciones se completen
        await Promise.all(promises);
    }

    // Función para traducir antónimos usando el sistema híbrido
    async translateAntonyms(antonyms) {
        const promises = antonyms.map(async (antonym, index) => {
            try {
                const response = await fetch('translate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'word=' + encodeURIComponent(antonym)
                });
                
                const data = await response.json();
                const translationElement = document.getElementById(`antonimo-es-${index}`);
                
                if (translationElement && data.translation) {
                    translationElement.textContent = data.translation;
                    translationElement.style.color = '#1976d2';
                } else if (translationElement) {
                    translationElement.textContent = 'No se pudo traducir';
                    translationElement.style.color = '#999';
                }
            } catch (error) {
                const translationElement = document.getElementById(`antonimo-es-${index}`);
                if (translationElement) {
                    translationElement.textContent = 'Error en traducción';
                    translationElement.style.color = '#f44336';
                }
            }
        });
        
        // Esperar a que todas las traducciones se completen
        await Promise.all(promises);
    }
    
    pronounceWord() {
        if (!this.currentWord) {
            return;
        }
        
        // Cancelar cualquier pronunciación anterior (como se hace en lector.js)
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        
        // Crear utterance para la palabra (basado en lector.js)
        const utterance = new SpeechSynthesisUtterance(this.currentWord);
        utterance.lang = 'en-US';
        utterance.rate = 0.8; // Velocidad ligeramente más lenta para claridad
        
        // Reproducir la pronunciación
        window.speechSynthesis.speak(utterance);
        
        // Feedback visual sutil
        const pronounceBtn = document.querySelector('.pronounce-btn');
        if (pronounceBtn) {
            pronounceBtn.style.transform = 'scale(0.9)';
            setTimeout(() => {
                pronounceBtn.style.transform = 'scale(1)';
            }, 150);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.explainSidebar = new ExplainSidebar();
    
    // Verificar si hay texto después de un tiempo para mostrar el botón flotante
    setTimeout(() => {
        if (window.explainSidebar) {
            window.explainSidebar.showFloatingButton();
        }
    }, 1000);
});

// Exportar para uso global
window.ExplainSidebar = ExplainSidebar; 