/**
 * Estado global centralizado de la aplicación LeerEntender
 * Consolidación de variables globales dispersas en múltiples archivos
 */

// Estado principal de la aplicación
window.AppState = {
    // === ESTADO DE LECTURA ===
    isCurrentlyReading: false,
    isCurrentlyPaused: false,
    lastReadParagraphIndex: 0,
    lastReadPageIndex: 0,
    currentTextId: null,
    
    // === ESTADO DE PRÁCTICA ===
    practiceWords: [],
    practiceRemainingWords: [],
    practiceCurrentMode: 'selection', // 'selection', 'writing', 'sentences'
    practiceCurrentQuestionIndex: 0,
    practiceCorrectAnswers: 0,
    practiceIncorrectAnswers: 0,
    practiceAnswered: false,
    practiceCurrentWordIndex: 0,
    practiceCurrentSentenceData: {},
    
    // === ESTADO DE UI ===
    isFloatingMenuVisible: false,
    isHeaderVisible: true,
    isFullscreen: false,
    isMobileMenuOpen: false,
    
    // === CONFIGURACIÓN ===
    autoSaveEnabled: true,
    translationMode: 'click', // 'click', 'hover', 'off'
    practiceSettings: {
        showHints: true,
        autoAdvance: false,
        soundEnabled: true
    }
};

/**
 * Utilidades para gestión de estado
 */
window.StateManager = {
    
    /**
     * Actualiza el estado de lectura
     * @param {Object} updates - Propiedades a actualizar
     */
    updateReadingState: function(updates) {
        Object.assign(window.AppState, updates);
        this.persistState();
    },
    
    /**
     * Actualiza el estado de práctica
     * @param {Object} updates - Propiedades a actualizar
     */
    updatePracticeState: function(updates) {
        Object.assign(window.AppState, updates);
        this.persistState();
    },
    
    /**
     * Resetea el estado de práctica
     */
    resetPracticeState: function() {
        window.AppState.practiceWords = [];
        window.AppState.practiceRemainingWords = [];
        window.AppState.practiceCurrentQuestionIndex = 0;
        window.AppState.practiceCorrectAnswers = 0;
        window.AppState.practiceIncorrectAnswers = 0;
        window.AppState.practiceAnswered = false;
        this.persistState();
    },
    
    /**
     * Guarda estado en localStorage
     */
    persistState: function() {
        if (window.AppState.autoSaveEnabled) {
            try {
                const stateToPersist = {
                    lastReadParagraphIndex: window.AppState.lastReadParagraphIndex,
                    lastReadPageIndex: window.AppState.lastReadPageIndex,
                    currentTextId: window.AppState.currentTextId,
                    practiceSettings: window.AppState.practiceSettings
                };
                localStorage.setItem('leerEntenderState', JSON.stringify(stateToPersist));
            } catch (e) {
                // Error silencioso al guardar estado
            }
        }
    },
    
    /**
     * Carga estado desde localStorage
     */
    loadPersistedState: function() {
        try {
            const saved = localStorage.getItem('leerEntenderState');
            if (saved) {
                const state = JSON.parse(saved);
                Object.assign(window.AppState, state);
            }
        } catch (e) {
            // Error silencioso al cargar estado
        }
    },
    
    /**
     * Obtiene una propiedad del estado
     * @param {string} key - Clave del estado
     * @returns {*} Valor del estado
     */
    get: function(key) {
        return window.AppState[key];
    },
    
    /**
     * Establece una propiedad del estado
     * @param {string} key - Clave del estado
     * @param {*} value - Valor a establecer
     */
    set: function(key, value) {
        window.AppState[key] = value;
        this.persistState();
    }
};

/**
 * Eventos de estado para comunicación entre módulos
 */
window.StateEvents = {
    
    /**
     * Dispara un evento de cambio de estado
     * @param {string} eventType - Tipo de evento
     * @param {Object} data - Datos del evento
     */
    emit: function(eventType, data) {
        const event = new CustomEvent('stateChange', {
            detail: { type: eventType, data: data }
        });
        window.dispatchEvent(event);
    },
    
    /**
     * Escucha eventos de cambio de estado
     * @param {Function} callback - Función callback
     */
    listen: function(callback) {
        window.addEventListener('stateChange', callback);
    }
};

// Cargar estado persistido al inicializar
document.addEventListener('DOMContentLoaded', function() {
    window.StateManager.loadPersistedState();
});

// Guardar estado antes de cerrar
window.addEventListener('beforeunload', function() {
    window.StateManager.persistState();
});

// Compatibilidad hacia atrás - mantener variables globales existentes
Object.defineProperty(window.AppState, 'isCurrentlyReading', {
    set: function(val) {
        this._isCurrentlyReading = val;
    },
    get: function() {
        return this._isCurrentlyReading;
    },
    configurable: true
});
window.AppState._isCurrentlyReading = false;

Object.defineProperty(window.AppState, 'isCurrentlyPaused', {
    set: function(val) {
        this._isCurrentlyPaused = val;
    },
    get: function() {
        return this._isCurrentlyPaused;
    },
    configurable: true
});
window.AppState._isCurrentlyPaused = false;

Object.defineProperty(window.AppState, 'lastReadParagraphIndex', {
    get: () => window.AppState.lastReadParagraphIndex,
    set: (value) => { window.AppState.lastReadParagraphIndex = value; }
});

Object.defineProperty(window.AppState, 'practiceWords', {
    get: () => window.AppState.practiceWords,
    set: (value) => { window.AppState.practiceWords = value; }
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AppState: window.AppState, StateManager: window.StateManager };
}
