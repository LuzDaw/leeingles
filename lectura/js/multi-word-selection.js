/**
 * SISTEMA DE SELECCIÓN MÚLTIPLE DE PALABRAS
 * Similar a Readlang - permite seleccionar múltiples palabras para traducir
 */

/**
 * @file Implementa un sistema de selección múltiple de palabras para traducción.
 * @class MultiWordSelector
 * @description Permite a los usuarios seleccionar una o varias palabras en el texto
 *              para obtener su traducción, similar a la funcionalidad de Readlang.
 */
class MultiWordSelector {
    /**
     * Crea una instancia de MultiWordSelector.
     * Inicializa las propiedades del estado y configura los event listeners.
     */
    constructor() {
        /** @property {boolean} isSelecting - Indica si el usuario está actualmente realizando una selección. */
        this.isSelecting = false;
        /** @property {Array<HTMLElement>} selectedWords - Un array de elementos DOM de las palabras seleccionadas. */
        this.selectedWords = [];
        /** @property {HTMLElement|null} startElement - El elemento DOM donde comenzó la selección. */
        this.startElement = null;
        /** @property {HTMLElement|null} endElement - El elemento DOM donde terminó la selección. */
        this.endElement = null;
        /** @property {HTMLElement|null} selectionHighlight - El elemento DOM que representa el resaltado de la selección. */
        this.selectionHighlight = null;
        /** @property {HTMLElement|null} tooltip - El elemento DOM del tooltip de traducción. */
        this.tooltip = null;
        /** @property {boolean} hasDragged - Indica si el usuario ha arrastrado el ratón durante la selección. */
        this.hasDragged = false;
        /** @property {object|null} startPosition - Las coordenadas (x, y) donde comenzó el evento mousedown. */
        this.startPosition = null;
        
        this.init();
    }
    
    /**
     * Inicializa los event listeners para los eventos de ratón (mousedown, mousemove, mouseup)
     * y para limpiar la selección al hacer clic fuera.
     */
    init() {
        // Event listeners para selección usando arrow functions para mantener el contexto
        document.addEventListener('mousedown', (e) => this.onMouseDown(e));
        document.addEventListener('mousemove', (e) => this.onMouseMove(e));
        document.addEventListener('mouseup', (e) => this.onMouseUp(e));
        
        // Limpiar selección al hacer clic fuera
        document.addEventListener('click', (e) => this.onDocumentClick(e));
    }
    
    /**
     * Maneja el evento `mousedown`.
     *
     * Inicia el proceso de selección si el clic es directamente sobre una palabra clickeable.
     * Limpia cualquier selección previa.
     *
     * @param {MouseEvent} event - El objeto de evento del ratón.
     */
    onMouseDown(event) {
        const target = event.target;
        
        // Solo activar si el clic es DIRECTAMENTE en una palabra clickeable
        const isWord = target.classList.contains('clickable-word') || target.classList.contains('practice-word');
        
        if (!isWord) {
            return;
        }
        
        this.isSelecting = true;
        this.startElement = target;
        this.hasDragged = false;
        this.startPosition = { x: event.clientX, y: event.clientY };
        
        // Limpiar selecciones anteriores
        this.clearSelection();
        
        event.preventDefault();
    }
    
    /**
     * Maneja el evento `mousemove`.
     *
     * Si la selección está activa y el ratón se mueve sobre una palabra clickeable,
     * actualiza el elemento final de la selección y el resaltado visual.
     * También detecta si el usuario está arrastrando.
     *
     * @param {MouseEvent} event - El objeto de evento del ratón.
     */
    onMouseMove(event) {
        if (!this.isSelecting) return;
        
        const target = event.target;
        
        // Solo procesar si estamos sobre una palabra clickeable
        const isWord = target.classList.contains('clickable-word') || target.classList.contains('practice-word');
        
        if (!isWord) {
            return;
        }
        
        // Detectar si el usuario está arrastrando
        if (this.startPosition) {
            const distance = Math.sqrt(
                Math.pow(event.clientX - this.startPosition.x, 2) + 
                Math.pow(event.clientY - this.startPosition.y, 2)
            );
            
            if (distance > 5) {
                this.hasDragged = true;
            }
        }
        
        if (target !== this.endElement) {
            this.endElement = target;
            this.updateSelection();
        }
    }
    
    /**
     * Maneja el evento `mouseup`.
     *
     * Finaliza el proceso de selección. Si se ha arrastrado el ratón,
     * actualiza la selección y traduce las palabras seleccionadas.
     *
     * @param {MouseEvent} event - El objeto de evento del ratón.
     */
    onMouseUp(event) {
        if (!this.isSelecting) return;
        
        const target = event.target;
        
        // Solo procesar si el elemento final es clickeable
        const isWord = target.classList.contains('clickable-word') || target.classList.contains('practice-word');
        
        if (!isWord) {
            this.isSelecting = false;
            this.clearSelection();
            return;
        }
        
        this.endElement = target;
        
        if (!this.hasDragged) {
            this.isSelecting = false;
            this.clearSelection();
            return;
        }
        
        // Si el usuario arrastró, es una selección múltiple
        this.updateSelection();
        this.translateSelection();
        
        this.isSelecting = false;
    }
    
    /**
     * Maneja los clics en el documento para limpiar la selección si el clic
     * se realiza fuera del tooltip o de las palabras seleccionadas.
     *
     * @param {MouseEvent} event - El objeto de evento del ratón.
     */
    onDocumentClick(event) {
        // Si se hace clic fuera de la selección, limpiar
        if (!event.target.closest('.multi-word-tooltip') && 
            !this.selectedWords.includes(event.target)) {
            this.clearSelection();
            this.hideTooltip();
        }
    }
    
    /**
     * Detecta palabras adyacentes que puedan formar una expresión común (ej. phrasal verbs).
     *
     * Si se encuentra una expresión, selecciona todas las palabras que la componen.
     * Si no, selecciona solo la palabra clickeada.
     *
     * @param {HTMLElement} clickedWord - El elemento DOM de la palabra clickeada.
     */
    detectAdjacentWords(clickedWord) {
        const container = clickedWord.closest('.reading-area, .practice-area, .text-example, p');
        
        if (!container) {
            this.selectedWords = [clickedWord];
            this.highlightWord(clickedWord, 'single');
            this.translateSelection();
            return;
        }
        
        const allWords = Array.from(container.querySelectorAll('.word-clickable, .practice-word'));
        const clickedIndex = allWords.indexOf(clickedWord);
        
        if (clickedIndex === -1) {
            this.selectedWords = [clickedWord];
            this.highlightWord(clickedWord, 'single');
            this.translateSelection();
            return;
        }
        
        // Buscar palabras adyacentes que formen una expresión común
        const adjacentWords = this.findAdjacentExpression(allWords, clickedIndex);
        
        if (adjacentWords.length > 1) {
            this.selectedWords = adjacentWords;
            this.highlightSelection(adjacentWords);
            this.translateSelection();
        } else if (adjacentWords.length === 1) {
            this.selectedWords = adjacentWords;
            this.highlightWord(adjacentWords[0], 'single');
            this.translateSelection();
        }
    }
    
    /**
     * Busca expresiones de varias palabras (ej. phrasal verbs) alrededor de una palabra clickeada.
     *
     * @param {Array<HTMLElement>} allWords - Un array de todos los elementos de palabra en el contenedor.
     * @param {number} clickedIndex - El índice de la palabra clickeada en el array `allWords`.
     * @returns {Array<HTMLElement>} Un array de elementos DOM que forman la expresión encontrada, o solo la palabra clickeada.
     */
    findAdjacentExpression(allWords, clickedIndex) {
        const clickedWord = allWords[clickedIndex];
        
        // Lista simplificada de expresiones comunes (Phrasal Verbs)
        const commonExpressions = [
            'look up', 'give up', 'take care', 'get up', 'sit down', 'come on',
            'go on', 'come in', 'go out', 'come back', 'go back', 'look for',
            'find out', 'put on', 'take off', 'get in', 'get out', 'wait for'
        ];
        
        // Buscar expresiones de 2 palabras
        for (let i = Math.max(0, clickedIndex - 1); i <= Math.min(allWords.length - 2, clickedIndex + 1); i++) {
            if (i + 1 < allWords.length) {
                const word1 = allWords[i].textContent.trim().toLowerCase();
                const word2 = allWords[i + 1].textContent.trim().toLowerCase();
                const expression = `${word1} ${word2}`;
                
                if (commonExpressions.includes(expression)) {
                    return [allWords[i], allWords[i + 1]];
                }
            }
        }
        
        // Buscar expresiones de 3 palabras
        for (let i = Math.max(0, clickedIndex - 2); i <= Math.min(allWords.length - 3, clickedIndex + 1); i++) {
            if (i + 2 < allWords.length) {
                const word1 = allWords[i].textContent.trim().toLowerCase();
                const word2 = allWords[i + 1].textContent.trim().toLowerCase();
                const word3 = allWords[i + 2].textContent.trim().toLowerCase();
                const expression = `${word1} ${word2} ${word3}`;
                
                if (commonExpressions.includes(expression)) {
                    return [allWords[i], allWords[i + 1], allWords[i + 2]];
                }
            }
        }
        
        // Si no encuentra expresión, devolver solo la palabra clickeada
        return [clickedWord];
    }
    
    /**
     * Aplica resaltado visual a un conjunto de palabras seleccionadas.
     *
     * @param {Array<HTMLElement>} words - Un array de elementos DOM de palabras a resaltar.
     */
    highlightSelection(words) {
        words.forEach((word, index) => {
            if (index === 0) {
                this.highlightWord(word, 'start');
            } else if (index === words.length - 1) {
                this.highlightWord(word, 'end');
            } else {
                this.highlightWord(word, 'middle');
            }
        });
    }
    
    /**
     * Actualiza la selección de palabras basándose en los elementos de inicio y fin.
     *
     * Limpia cualquier selección previa, identifica todas las palabras entre `startElement` y `endElement`,
     * y aplica el resaltado visual.
     */
    updateSelection() {
        this.clearSelection();
        
        if (!this.startElement || !this.endElement) return;
        
        // Obtener todos los elementos entre start y end
        const elements = this.getElementsBetween(this.startElement, this.endElement);
        this.selectedWords = elements;
        
        // Aplicar estilos
        elements.forEach((element, index) => {
            if (index === 0) {
                this.highlightWord(element, 'start');
            } else if (index === elements.length - 1) {
                this.highlightWord(element, 'end');
            } else {
                this.highlightWord(element, 'middle');
            }
        });
    }
    
    /**
     * Obtiene todos los elementos de palabra entre un elemento de inicio y uno de fin.
     *
     * @param {HTMLElement} start - El elemento DOM de inicio de la selección.
     * @param {HTMLElement} end - El elemento DOM de fin de la selección.
     * @returns {Array<HTMLElement>} Un array de elementos DOM de palabras entre el inicio y el fin.
     */
    getElementsBetween(start, end) {
        const elements = [];
        let current = start;
        
        // Buscar en el mismo contenedor
        const container = start.closest('.reading-area, .practice-area, .text-example, p');
        if (!container) return [start];
        
        const allWords = Array.from(container.querySelectorAll('.clickable-word, .practice-word'));
        
        const startIndex = allWords.indexOf(start);
        const endIndex = allWords.indexOf(end);
        
        if (startIndex === -1 || endIndex === -1) return [start];
        
        const minIndex = Math.min(startIndex, endIndex);
        const maxIndex = Math.max(startIndex, endIndex);
        
        for (let i = minIndex; i <= maxIndex; i++) {
            elements.push(allWords[i]);
        }
        
        return elements;
    }
    
    /**
     * Aplica clases CSS para resaltar una palabra individualmente, indicando su posición en una selección múltiple.
     *
     * @param {HTMLElement} element - El elemento DOM de la palabra a resaltar.
     * @param {string} position - La posición de la palabra en la selección ('single', 'start', 'middle', 'end').
     */
    highlightWord(element, position) {
        // SEGURIDAD: Solo aplicar a palabras, nunca a contenedores
        if (!element.classList.contains('clickable-word') && !element.classList.contains('practice-word')) {
            return;
        }

        element.classList.add('word-selection');
        
        if (position === 'start') {
            element.classList.add('word-selection-start');
        } else if (position === 'end') {
            element.classList.add('word-selection-end');
        }
    }
    
    /**
     * Elimina todas las clases de resaltado de selección de palabras en el documento.
     */
    clearSelection() {
        // Limpiar clases de selección en TODA la página para mayor seguridad
        const selectors = ['.word-selection', '.word-selection-start', '.word-selection-end'];
        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                element.classList.remove('word-selection', 'word-selection-start', 'word-selection-end');
            });
        });
    }
    
    /**
     * Traduce la selección actual de palabras.
     *
     * Concatena el texto de las palabras seleccionadas, muestra un tooltip de carga,
     * realiza una petición AJAX para la traducción y actualiza el tooltip con el resultado.
     * Si el usuario está logueado, guarda la traducción.
     */
    translateSelection() {
        const text = this.selectedWords.map(word => word.textContent.trim()).join(' ');
        
        if (!text || text.length < 2) {
            return;
        }
        
        // Mostrar tooltip de carga
        this.showTooltip(text, 'Traduciendo...', true);
        
        // Hacer petición de traducción
        fetch('traduciones/translate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'text=' + encodeURIComponent(text)
        })
        .then(res => res.json())
        .then(data => {
            if (data.translation) {
                this.showTooltip(text, data.translation, false);
                
                // Guardar palabra traducida si el usuario está logueado
                if (typeof saveTranslatedWord === 'function') {
                    const sentence = this.findSentenceContainingWords();
                    saveTranslatedWord(text, data.translation, sentence);
                }
            } else {
                this.showTooltip(text, 'No se encontró traducción', false);
            }
        })
        .catch((error) => {
            this.showTooltip(text, 'Error en la traducción', false);
        });
    }
    
    /**
     * Encuentra la oración completa que contiene las palabras seleccionadas.
     *
     * @returns {string} La oración que contiene las palabras seleccionadas, o un fragmento del párrafo si es muy largo.
     */
    findSentenceContainingWords() {
        if (this.selectedWords.length === 0) return '';
        
        const firstWord = this.selectedWords[0];
        let paragraph = firstWord.closest('p');
        
        if (!paragraph) {
            paragraph = firstWord.closest('.paragraph');
        }
        
        if (paragraph) {
            let fullText = paragraph.textContent || paragraph.innerText;
            fullText = fullText.trim();
            
            const sentences = fullText.split(/[.!?]+/).filter(s => s.trim().length > 0);
            
            for (let sentence of sentences) {
                const selectedText = this.selectedWords.map(w => w.textContent.trim()).join(' ');
                if (sentence.toLowerCase().includes(selectedText.toLowerCase())) {
                    return sentence.trim() + '.';
                }
            }
            
            return fullText.length > 200 ? fullText.substring(0, 200) + '...' : fullText;
        }
        
        return this.selectedWords.map(w => w.textContent.trim()).join(' ') + '.';
    }
    
    /**
     * Muestra un tooltip con el texto original y su traducción.
     *
     * @param {string} originalText - El texto original seleccionado.
     * @param {string} translation - La traducción del texto.
     * @param {boolean} [isLoading=false] - Indica si el tooltip debe mostrar un estado de carga.
     */
    showTooltip(originalText, translation, isLoading = false) {
        this.hideTooltip();
        
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'multi-word-tooltip';
        
        if (isLoading) {
            this.tooltip.innerHTML = `
                <div class="original-text">"${originalText}"</div>
                <div class="translation loading">${translation}</div>
            `;
        } else {
            this.tooltip.innerHTML = `
                <div class="original-text">"${originalText}"</div>
                <div class="translation">${translation}</div>
            `;
        }
        
        // Agregar al contenedor de la página de prueba si existe
        const testContainer = document.querySelector('.text-example');
        if (testContainer) {
            testContainer.appendChild(this.tooltip);
        } else {
            document.body.appendChild(this.tooltip);
        }
        
        // Posicionar tooltip
        this.positionTooltip();
    }
    
    /**
     * Posiciona el tooltip de traducción de manera que esté centrado bajo la selección de palabras.
     *
     * Ajusta la posición para que el tooltip sea visible dentro del contenedor de la página de prueba
     * o en el cuerpo del documento.
     */
    positionTooltip() {
        if (!this.tooltip || this.selectedWords.length === 0) {
            return;
        }
        
        const firstWord = this.selectedWords[0];
        const lastWord = this.selectedWords[this.selectedWords.length - 1];
        
        const firstRect = firstWord.getBoundingClientRect();
        const lastRect = lastWord.getBoundingClientRect();
        
        // Posicionamiento mejorado - dentro del contenedor
        const centerX = (firstRect.left + lastRect.right) / 2;
        const top = lastRect.bottom + 10;
        
        // Obtener el contenedor de la página de prueba
        const testContainer = document.querySelector('.text-example');
        if (testContainer) {
            const containerRect = testContainer.getBoundingClientRect();
            
            // Posicionar dentro del contenedor
            const relativeLeft = centerX - containerRect.left - 100;
            const relativeTop = top - containerRect.top;
            
            this.tooltip.style.position = 'absolute';
            this.tooltip.style.left = relativeLeft + 'px';
            this.tooltip.style.top = relativeTop + 'px';
        } else {
            // Fallback para otras páginas
            this.tooltip.style.left = (centerX - 100) + 'px';
            this.tooltip.style.top = top + 'px';
        }
        
        // Asegurar que esté visible
        this.tooltip.style.display = 'block';
        this.tooltip.style.visibility = 'visible';
    }
    
    /**
     * Oculta y elimina el tooltip de traducción del DOM.
     */
    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }
    
    /**
     * Limpia completamente el estado del selector de palabras múltiples,
     * eliminando selecciones, tooltips y restableciendo las banderas de estado.
     */
    destroy() {
        this.clearSelection();
        this.hideTooltip();
        this.isSelecting = false;
        this.selectedWords = [];
    }
}

// Inicializar solo en páginas de lectura, no en práctica
document.addEventListener('DOMContentLoaded', () => {
    /* DESACTIVADO TEMPORALMENTE: Evitar marcado agresivo de múltiples palabras
    // Solo inicializar si estamos en una página de lectura (no práctica)
    const isPracticePage = window.location.href.includes('practice') || 
                          document.querySelector('.practice-area') ||
                          document.querySelector('#practice-container') ||
                          document.querySelector('.text-selector-container') ||
                          document.querySelector('#text-selector');
    
    // También verificar si estamos en la pestaña de práctica
    const currentTab = document.querySelector('.tab-btn.active');
    const isPracticeTab = currentTab && currentTab.textContent.includes('Práctica');
    
    if (!isPracticePage && !isPracticeTab) {
        window.multiWordSelector = new MultiWordSelector();
    }
    */
});

// Exportar para uso global
window.MultiWordSelector = MultiWordSelector;
