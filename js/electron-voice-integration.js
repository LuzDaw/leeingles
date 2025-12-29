// electron-voice-integration.js
// Sistema unificado de ResponsiveVoice para web y Electron
// Evita conflictos de variables y proporciona una API consistente

(function() {
    'use strict';

    // Silenciar logs verbosos del script ResponsiveVoice sin afectar el resto
    (function setupRVLogSilencer(){
        try {
            const __orig = {
                log: console.log,
                info: console.info,
                warn: console.warn
            };
            const shouldMute = (args) => {
                if (!args || !args.length) return false;
                const s = args[0];
                if (typeof s !== 'string') return false;
                // Mensajes típicos del loader de ResponsiveVoice
                if (s.startsWith('ResponsiveVoice')) return true; // "ResponsiveVoice r1.8.4"
                if (s.startsWith('RV: ')) return true;           // "RV: Voice support ready"
                if (s === 'Prerender: false') return true;
                if (s === 'isHidden: false') return true;
                return false;
            };
            console.log = function(...args){ if (shouldMute(args)) return; return __orig.log.apply(console, args); };
            console.info = function(...args){ if (shouldMute(args)) return; return __orig.info.apply(console, args); };
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
    
    // Función para cargar scripts dinámicamente
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

    // Función para verificar si estamos en Electron
    function detectElectron() {
        _isElectron = window.electronAPI !== undefined;
        return _isElectron;
    }

    // Función principal de inicialización
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
            loadScript(`https://code.responsivevoice.org/responsivevoice.js?key=${_apiKey}`, () => {
                _responsiveVoiceLoaded = true;
                setupVoiceFunctions();
                resolve();
            });
        });
    }

    // Configurar funciones de voz globales
    function setupVoiceFunctions() {
        // Función principal para leer texto
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

        // Función para detener la lectura
        window.detenerLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.cancel();
                return true;
            }
            return false;
        };

        // Función para verificar si está leyendo
        window.estaLeyendoResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                return responsiveVoice.isPlaying();
            }
            return false;
        };

        // Función para pausar
        window.pausarLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.pause();
                return true;
            }
            return false;
        };

        // Función para reanudar
        window.reanudarLecturaResponsiveVoice = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                responsiveVoice.resume();
                return true;
            }
            return false;
        };

        // Función para cambiar velocidad
        window.cambiarVelocidadResponsiveVoice = function(nuevaVelocidad) {
            // Implementación futura si es necesario ajustar en caliente
            return true;
        };

        // Función para obtener voces disponibles
        window.obtenerVocesDisponibles = function() {
            if (typeof responsiveVoice !== 'undefined' && _responsiveVoiceLoaded) {
                return responsiveVoice.getVoices();
            }
            return [];
        };
    }

    // Función para verificar el estado del sistema
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

    // Función para obtener una promesa de inicialización
    window.getVoiceSystemReady = function() {
        if (!_initializationPromise) {
            _initializationPromise = initializeResponsiveVoice();
        }
        return _initializationPromise;
    };

    // Inicializar cuando el DOM esté listo, solo si el usuario está logueado
    function conditionalInitialize() {
        if (window.userLoggedIn) {
            initializeResponsiveVoice();
        } else {
            console.log("[LECTOR] ResponsiveVoice no inicializado: usuario no logueado.");
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', conditionalInitialize);
    } else {
        conditionalInitialize();
    }

})();
