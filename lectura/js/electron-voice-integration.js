// electron-voice-integration.js
// Sistema unificado de ResponsiveVoice para web y Electron
// Evita conflictos de variables y proporciona una API consistente

(function() {
    'use strict';

    // Silenciar logs verbosos del script ResponsiveVoice sin afectar el resto
    (function setupRVLogSilencer(){
        try {
            console.warn = function(...args){ if (shouldMute(args)) return; return __orig.warn.apply(console, args); };
            // Permitir restaurar si fuera necesario
            window.__rvConsoleSilencer = __orig;
        } catch(e) { /* silencioso */ }
    })();

    // Variables privadas para evitar conflictos
    let _responsiveVoiceLoaded = false;
    let _isElectron = false;
    let _apiKey = 'wJGiW37b';
    let _scriptLoaded = false;
    let _initializationPromise = null;
    
    /**
     * Carga un script JavaScript dinámicamente en el documento.
     *
     * Evita cargar el mismo script varias veces.
     *
     * @param {string} src - La URL del script a cargar.
     * @param {function} [callback] - Función a ejecutar una vez que el script se haya cargado.
     */
    function loadScript(src, callback) {
        // Evitar cargar el mismo script múltiples veces
        if (_scriptLoaded) {
            if (callback) callback();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => {
            _scriptLoaded = true;
            if (callback) callback();
        };
        script.onerror = () => {
            // Error al cargar script externo (silencioso)
        };
        document.head.appendChild(script);
    }

    /**
     * Detecta si la aplicación se está ejecutando en un entorno Electron.
     *
     * @returns {boolean} `true` si se detecta Electron, `false` en caso contrario.
     */
    function detectElectron() {
        _isElectron = window.electronAPI !== undefined;
        return _isElectron;
    }

    /**
     * Inicializa el sistema de voz ResponsiveVoice.
     *
     * Detecta el entorno (web o Electron) y carga ResponsiveVoice si aún no está cargado.
     * Luego, configura las funciones de voz globales.
     *
     * @returns {Promise<void>} Una promesa que se resuelve cuando ResponsiveVoice está inicializado.
     */
    function initializeResponsiveVoice() {
        detectElectron();

        // Verificar si ResponsiveVoice ya está cargado
        if (typeof responsiveVoice !== 'undefined') {
            _responsiveVoiceLoaded = true;
            setupVoiceFunctions();
            return Promise.resolve();
        }

        // Cargar ResponsiveVoice desde CDN
        return new Promise((resolve) => {
            // loadScript(`https://code.responsivevoice.org/responsivevoice.js?key=${_apiKey}`, () => {
                _responsiveVoiceLoaded = true;
                setupVoiceFunctions();
                resolve();
            // });
        });
    }

    /**
     * Configura las funciones de voz globales (`window.leerTextoConResponsiveVoice`, etc.).
     *
     * Estas funciones actúan como una interfaz unificada para interactuar con ResponsiveVoice,
     * proporcionando métodos para leer, detener, pausar, reanudar y obtener información de voz.
     */
    function setupVoiceFunctions() {
        /**
         * Lee un texto utilizando ResponsiveVoice.
         *
         * @param {string} texto - El texto a leer.
         * @param {number} [velocidad=1.0] - La velocidad de lectura (0.5 a 1.5).
         * @param {object} [callbacks={}] - Objeto con funciones de callback para eventos de voz (onstart, onend, onpause, onresume, onerror).
         * @returns {boolean} `true` si la lectura se inició, `false` si ResponsiveVoice no está disponible.
         */
        window.leerTextoConResponsiveVoice = function(texto, velocidad = 1.0, callbacks = {}) {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                const config = {
                    VOICE: 'UK English Female',
                    RATE: velocidad,
                    PITCH: 1.0,
                    VOLUME: 1.0
                };
                
                const options = {
                    rate: config.RATE,
                    pitch: config.PITCH,
                    volume: config.VOLUME
                };
                if (typeof callbacks.onstart === 'function') options.onstart = callbacks.onstart;
                if (typeof callbacks.onend === 'function') options.onend = callbacks.onend;
                if (typeof callbacks.onpause === 'function') options.onpause = callbacks.onpause;
                if (typeof callbacks.onresume === 'function') options.onresume = callbacks.onresume;
                if (typeof callbacks.onerror === 'function') options.onerror = callbacks.onerror;
                
                responsiveVoice.speak(texto, config.VOICE, options);
                return true;
            } else {
                return false;
            }
        };

        /**
         * Detiene cualquier lectura de ResponsiveVoice en curso.
         *
         * @returns {boolean} `true` si la lectura se detuvo, `false` si ResponsiveVoice no está disponible.
         */
        window.detenerLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.cancel();
                return true;
            }
            return false;
        };

        /**
         * Verifica si ResponsiveVoice está reproduciendo audio actualmente.
         *
         * @returns {boolean} `true` si está leyendo, `false` en caso contrario.
         */
        window.estaLeyendoResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                return responsiveVoice.isPlaying();
            }
            return false;
        };

        /**
         * Pausa la lectura de ResponsiveVoice.
         *
         * @returns {boolean} `true` si la lectura se pausó, `false` si ResponsiveVoice no está disponible.
         */
        window.pausarLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.pause();
                return true;
            }
            return false;
        };

        /**
         * Reanuda la lectura de ResponsiveVoice si está pausada.
         *
         * @returns {boolean} `true` si la lectura se reanudó, `false` si ResponsiveVoice no está disponible.
         */
        window.reanudarLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.resume();
                return true;
            }
            return false;
        };

        /**
         * Cambia la velocidad de lectura de ResponsiveVoice.
         *
         * @param {number} nuevaVelocidad - La nueva velocidad de lectura.
         * @returns {boolean} Siempre `true` por ahora, ya que la implementación de cambio en caliente puede variar.
         */
        window.cambiarVelocidadResponsiveVoice = function(nuevaVelocidad) {
            // Implementación futura si es necesario ajustar en caliente
            return true;
        };

        /**
         * Obtiene la lista de voces disponibles en ResponsiveVoice.
         *
         * @returns {Array<object>} Un array de objetos de voz, o un array vacío si ResponsiveVoice no está disponible.
         */
        window.obtenerVocesDisponibles = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                return responsiveVoice.getVoices();
            }
            return [];
        };
    }

    /**
     * Verifica y devuelve el estado actual del sistema de voz.
     *
     * Proporciona información sobre el entorno (Electron/Web), la disponibilidad de ResponsiveVoice,
     * si el script se ha cargado y si las funciones globales de voz están configuradas.
     *
     * @returns {object} Un objeto con el estado detallado del sistema de voz.
     */
    window.verificarEstadoVoz = function() {
        const estado = {
            entorno: _isElectron ? "Electron" : "Web",
            responsiveVoiceDisponible: typeof responsiveVoice !== 'undefined',
            responsiveVoiceLoaded: _responsiveVoiceLoaded,
            scriptLoaded: _scriptLoaded,
            apiKey: _apiKey,
            funcionesDisponibles: {
                leerTexto: typeof window.leerTextoConResponsiveVoice === 'function',
                detener: typeof window.detenerLecturaResponsiveVoice === 'function',
                estaLeyendo: typeof window.estaLeyendoResponsiveVoice === 'function',
                pausar: typeof window.pausarLecturaResponsiveVoice === 'function',
                reanudar: typeof window.reanudarLecturaResponsiveVoice === 'function'
            }
        };
        return estado;
    };

    /**
     * Devuelve una promesa que se resuelve cuando el sistema de voz está completamente inicializado.
     *
     * Asegura que la inicialización solo se intente una vez.
     *
     * @returns {Promise<void>} Una promesa que se resuelve cuando el sistema de voz está listo.
     */
    window.getVoiceSystemReady = function() {
        if (!_initializationPromise) {
            _initializationPromise = initializeResponsiveVoice();
        }
        return _initializationPromise;
    };

    /**
     * Inicializa ResponsiveVoice condicionalmente si el usuario está logueado.
     *
     * Se ejecuta cuando el DOM está listo.
     */
    function conditionalInitialize() {
        if (window.userLoggedIn) {
            initializeResponsiveVoice();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', conditionalInitialize);
    } else {
        conditionalInitialize();
    }

})();
