/**
 * ReadingManager - Controlador unificado del sistema de lectura
 * Centraliza el estado, la lógica de TTS, el seguimiento de tiempo y el progreso.
 */
window.ReadingManager = (function() {
    'use strict';

    // Estado interno privado
    let state = {
        isReading: false,
        isPaused: false,
        currentIndex: 0,
        currentPage: 0,
        sessionID: 0,
        startTime: null,
        lastSaveTime: null,
        updateInterval: null,
        safetyTimeout: null,
        pauseReasons: new Set(),
        doubleReadingMode: false,
        repeatCount: 0,
        lastEventTime: null
    };

    // Configuración
    const CONFIG = {
        SAVE_INTERVAL: 10000, // 10 segundos
        SAFETY_TIMEOUT: 30000, // 30 segundos
        LANG: 'en-GB',
        PITCH: 1.0,
        VOLUME: 1.0
    };

    /**
     * Inicializa el controlador
     */
    function init() {
        syncWithGlobalState();
        console.log('ReadingManager inicializado');
    }

    /**
     * Sincroniza el estado interno con AppState y variables globales
     */
    function syncWithGlobalState() {
        if (window.AppState) {
            state.currentIndex = window.AppState.lastReadParagraphIndex || 0;
            state.currentPage = window.AppState.lastReadPageIndex || 0;
        } else {
            state.currentIndex = window.currentIndex || 0;
            state.currentPage = window.currentPage || 0;
        }
    }

    /**
     * Obtiene la velocidad de lectura actual
     */
    function getRate() {
        const rateInput = document.getElementById('rate');
        return rateInput ? parseFloat(rateInput.value) : 1.0;
    }

    /**
     * Actualiza la velocidad de lectura
     */
    function setRate(value) {
        const rateInput = document.getElementById('rate');
        if (rateInput) {
            rateInput.value = value;
            rateInput.dispatchEvent(new Event('input'));
        }
    }

    /**
     * Cancela cualquier reproducción de voz activa
     */
    function cancelAllTTS() {
        try {
            if (window.speechSynthesis) window.speechSynthesis.cancel();
            if (typeof window.detenerLecturaResponsiveVoice === 'function') window.detenerLecturaResponsiveVoice();
            if (window.eSpeakAPI && window.eSpeakAPI.cancel) window.eSpeakAPI.cancel();
        } catch (e) {
            console.error('Error cancelando TTS:', e);
        }
    }

    /**
     * Ejecuta físicamente el TTS para un texto
     */
    async function speak(text, rate, callbacks = {}) {
        const localSessionID = state.sessionID;
        cancelAllTTS();

        if (typeof window.getVoiceSystemReady === 'function') {
            await window.getVoiceSystemReady();
        }

        if (localSessionID !== state.sessionID) return;

        try {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = rate || 1.0;
            utterance.pitch = CONFIG.PITCH;
            utterance.volume = CONFIG.VOLUME;
            utterance.lang = CONFIG.LANG;

            utterance.onboundary = (event) => {
                if (localSessionID === state.sessionID && callbacks.onboundary) {
                    callbacks.onboundary(event);
                }
            };

            utterance.onend = () => {
                if (localSessionID === state.sessionID && callbacks.onend) {
                    callbacks.onend();
                }
            };

            utterance.onerror = (event) => {
                if (localSessionID === state.sessionID && callbacks.onerror) {
                    callbacks.onerror(event);
                }
            };

            window.speechSynthesis.speak(utterance);
            return true;
        } catch (e) {
            console.error('Error en speak:', e);
            if (callbacks.onerror) callbacks.onerror(e);
            return false;
        }
    }

    /**
     * Guarda el tiempo de lectura en el servidor
     */
    async function saveTime(duration) {
        if (duration <= 0) return;
        const textId = document.querySelector('.reading-area')?.dataset.textId;
        if (!textId) return;

        try {
            const response = await fetch('actions/save_reading_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `duration=${duration}&text_id=${textId}`
            });
            const data = await response.json();
            if (data.success && typeof window.updateCalendarNow === 'function') {
                window.updateCalendarNow();
            }
        } catch (e) {}
    }

    /**
     * Actualiza el tiempo de lectura en tiempo real
     */
    function updateRealTime() {
        if (state.isReading && !state.isPaused && state.lastSaveTime) {
            const now = Date.now();
            const delta = Math.floor((now - state.lastSaveTime) / 1000);
            if (delta >= 10) {
                saveTime(delta);
                state.lastSaveTime = now;
            }
        }
        
        if (state.isReading && !state.isPaused && state.startTime) {
            const inactiveTime = Date.now() - (state.lastEventTime || state.startTime);
            if (inactiveTime > CONFIG.SAFETY_TIMEOUT) {
                console.warn('Watchdog: Detectada inactividad en TTS, saltando al siguiente párrafo');
                onParagraphEnd(state.currentIndex);
            }
        }

        if (window.AppState) {
            window.AppState.lastReadParagraphIndex = state.currentIndex;
            window.AppState.lastReadPageIndex = state.currentPage;
            window.AppState.isCurrentlyReading = state.isReading;
            window.AppState.isCurrentlyPaused = state.isPaused;
        }
    }

    /**
     * Inicia la lectura
     */
    async function start(index = null, page = null) {
        state.sessionID++;
        cancelAllTTS();

        if (index !== null) state.currentIndex = index;
        if (page !== null) state.currentPage = page;

        state.isReading = true;
        state.isPaused = false;
        state.startTime = Date.now();
        state.lastSaveTime = Date.now();
        state.pauseReasons.clear();
        state.repeatCount = 0;

        if (window.AppState) {
            window.AppState.isCurrentlyReading = true;
            window.AppState.isCurrentlyPaused = false;
        }
        window.isCurrentlyReading = true;
        window.isCurrentlyPaused = false;
        window.autoReading = true;

        if (state.updateInterval) clearInterval(state.updateInterval);
        state.updateInterval = setInterval(updateRealTime, CONFIG.SAVE_INTERVAL);

        if (typeof window.hideHeader === 'function') window.hideHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();

        if (typeof window.readAndTranslate === 'function') {
            window.readAndTranslate(state.currentIndex);
        }
    }

    /**
     * Pausa la lectura
     */
    function pause(reason = 'user') {
        if (!state.isReading) return;
        state.pauseReasons.add(reason);
        state.isPaused = true;

        if (state.lastSaveTime) {
            const delta = Math.floor((Date.now() - state.lastSaveTime) / 1000);
            saveTime(delta);
            state.lastSaveTime = null;
        }

        cancelAllTTS();
        if (window.AppState) window.AppState.isCurrentlyPaused = true;
        window.isCurrentlyPaused = true;
        window.autoReading = false;

        if (typeof window.showHeader === 'function') window.showHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
    }

    /**
     * Reanuda la lectura
     */
    function resume(reason = 'user') {
        if (!state.isReading) {
            start();
            return;
        }
        if (reason) state.pauseReasons.delete(reason);
        if (state.pauseReasons.size > 0) return;

        state.isPaused = false;
        state.lastSaveTime = Date.now();

        if (window.AppState) window.AppState.isCurrentlyPaused = false;
        window.isCurrentlyPaused = false;
        window.autoReading = true;

        if (typeof window.hideHeader === 'function') window.hideHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();

        if (typeof window.readAndTranslate === 'function') {
            window.readAndTranslate(state.currentIndex);
        }
    }

    /**
     * Detiene la lectura completamente
     */
    function stop() {
        if (state.lastSaveTime) {
            const delta = Math.floor((Date.now() - state.lastSaveTime) / 1000);
            saveTime(delta);
        }

        state.isReading = false;
        state.isPaused = false;
        state.startTime = null;
        state.lastSaveTime = null;
        state.pauseReasons.clear();

        if (state.updateInterval) {
            clearInterval(state.updateInterval);
            state.updateInterval = null;
        }

        cancelAllTTS();
        if (window.AppState) {
            window.AppState.isCurrentlyReading = false;
            window.AppState.isCurrentlyPaused = false;
        }
        window.isCurrentlyReading = false;
        window.isCurrentlyPaused = false;
        window.autoReading = false;

        if (typeof window.showHeader === 'function') window.showHeader();
        if (typeof window.updateFloatingButton === 'function') window.updateFloatingButton();
        if (typeof window.clearCurrentHighlight === 'function') window.clearCurrentHighlight();
    }

    /**
     * Maneja el fin de un párrafo
     */
    async function onParagraphEnd(index) {
        state.lastEventTime = Date.now();
        if (!state.isReading || state.isPaused) return;

        if (state.doubleReadingMode && state.repeatCount < 1) {
            state.repeatCount++;
            if (typeof window.readAndTranslate === 'function') {
                window.readAndTranslate(index);
            }
            return true;
        }

        state.repeatCount = 0;
        const paragraphs = document.querySelectorAll('.page.active p.paragraph');
        
        if (index + 1 < paragraphs.length) {
            state.currentIndex = index + 1;
            if (window.AppState) window.AppState.lastReadParagraphIndex = state.currentIndex;
            window.currentIndex = state.currentIndex;
            if (typeof window.readAndTranslate === 'function') {
                window.readAndTranslate(state.currentIndex);
            }
            return false;
        } else {
            if (typeof window.onPageReadByTTS === 'function') {
                window.onPageReadByTTS(state.currentPage);
            }
            if (!nextPage()) {
                stop();
                if (typeof window.showLoadingRedirectModal === 'function') {
                    window.showLoadingRedirectModal('Lectura finalizada', 'Redirigiendo...', 'index.php?tab=practice', 2000);
                }
            }
            return false;
        }
    }

    /**
     * Gestiona la traducción simultánea
     */
    async function handleSimultaneousTranslation(index, text, box, textId) {
        if (!box || !text) return;
        const showTranslations = (typeof window.translationsVisible === 'undefined') ? true : !!window.translationsVisible;
        
        if (showTranslations) {
            if (box.innerText.trim() === '') {
                if (textId && typeof window.translateAndSaveParagraph === 'function') {
                    window.translateAndSaveParagraph(text, box, textId);
                } else if (typeof window.translateParagraphOnly === 'function') {
                    window.translateParagraphOnly(text, box);
                }
            }
        } else {
            box.innerText = '';
        }

        const paragraphs = document.querySelectorAll('.page.active p.paragraph');
        const translationBoxes = document.querySelectorAll('.page.active .translation');
        const nextPara = paragraphs[index + 1];
        const nextBox = translationBoxes[index + 1];
        
        if (nextPara && nextBox && nextBox.innerText.trim() === '') {
            if (textId && typeof window.translateAndSaveParagraph === 'function') {
                window.translateAndSaveParagraph(nextPara.innerText.trim(), nextBox, textId);
            }
        }
    }

    /**
     * Alterna el modo de lectura doble
     */
    function toggleDoubleReading() {
        state.doubleReadingMode = !state.doubleReadingMode;
        window.doubleReadingMode = state.doubleReadingMode;
        return state.doubleReadingMode;
    }

    /**
     * Avanza a la siguiente página
     */
    function nextPage() {
        const pagesContainer = document.getElementById('pages-container');
        const totalPages = parseInt(pagesContainer?.dataset.totalPages || 1);

        if (state.currentPage < totalPages - 1) {
            state.currentPage++;
            state.currentIndex = 0;
            if (window.AppState) {
                window.AppState.lastReadPageIndex = state.currentPage;
                window.AppState.lastReadParagraphIndex = 0;
            }
            window.currentPage = state.currentPage;
            window.currentIndex = 0;
            window._resumeIndexPending = 0;

            if (typeof window.updatePageDisplay === 'function') {
                window.updatePageDisplay();
            }
            return true;
        }
        return false;
    }

    /**
     * Actualiza la página actual
     */
    function onPageChange(pageIdx) {
        state.currentPage = pageIdx;
        state.currentIndex = 0;
        if (window.AppState) {
            window.AppState.lastReadPageIndex = pageIdx;
            window.AppState.lastReadParagraphIndex = 0;
        }
        window.currentPage = pageIdx;
        window.currentIndex = 0;
    }

    /**
     * Gestiona la visibilidad de las traducciones
     */
    function setTranslationsVisible(visible) {
        window.translationsVisible = visible;
        const boxes = document.querySelectorAll('.translation');
        if (!visible) {
            boxes.forEach(box => box.innerText = '');
        } else {
            if (state.isReading) {
                const paragraphs = document.querySelectorAll('.page.active p.paragraph');
                const currentPara = paragraphs[state.currentIndex];
                const currentBox = boxes[state.currentIndex];
                if (currentPara && currentBox) {
                    handleSimultaneousTranslation(state.currentIndex, currentPara.innerText.trim(), currentBox);
                }
            }
        }
    }

    /**
     * Lógica de impresión
     */
    function printText() {
        if (typeof window.printFullTextWithTranslations === 'function') {
            window.printFullTextWithTranslations();
        } else {
            window.print();
        }
    }

    return {
        init, start, pause, resume, stop, speak,
        onParagraphEnd, nextPage, onPageChange,
        toggleDoubleReading, handleSimultaneousTranslation,
        setRate, setTranslationsVisible, printText,
        getState: () => ({ ...state }),
        getRate
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    window.ReadingManager.init();
});
