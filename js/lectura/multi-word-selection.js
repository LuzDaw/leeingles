/**
 * SISTEMA DE SELECCIÓN MÚLTIPLE DE PALABRAS
 * Similar a Readlang - permite seleccionar múltiples palabras para traducir
 */

class MultiWordSelector {
    constructor() {
        this.isSelecting = false;
        this.selectedWords = [];
        this.startElement = null;
        this.endElement = null;
        this.selectionHighlight = null;
        this.tooltip = null;
        this.hasDragged = false;
        this.startPosition = null;
        
        this.init();
    }
    
    init() {
        // Event listeners para selección usando arrow functions para mantener el contexto
        document.addEventListener('mousedown', (e) => this.onMouseDown(e));
        document.addEventListener('mousemove', (e) => this.onMouseMove(e));
        document.addEventListener('mouseup', (e) => this.onMouseUp(e));
        
        // Limpiar selección al hacer clic fuera
        document.addEventListener('click', (e) => this.onDocumentClick(e));
    }
    
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
    
    onDocumentClick(event) {
        // Si se hace clic fuera de la selección, limpiar
        if (!event.target.closest('.multi-word-tooltip') && 
            !this.selectedWords.includes(event.target)) {
            this.clearSelection();
            this.hideTooltip();
        }
    }
    
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
    
    clearSelection() {
        // Limpiar clases de selección en TODA la página para mayor seguridad
        const selectors = ['.word-selection', '.word-selection-start', '.word-selection-end'];
        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                element.classList.remove('word-selection', 'word-selection-start', 'word-selection-end');
            });
        });
    }
    
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
    
    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }
    
    // Método público para limpiar todo
    destroy() {
        this.clearSelection();
        this.hideTooltip();
        this.isSelecting = false;
        this.selectedWords = [];
    }
}

// Inicializar solo en páginas de lectura, no en práctica
document.addEventListener('DOMContentLoaded', () => {
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
});

// Exportar para uso global
window.MultiWordSelector = MultiWordSelector;
