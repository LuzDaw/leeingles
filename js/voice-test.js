// voice-test.js
// Script de prueba para verificar el sistema de voz unificado

// Función para probar el sistema de voz
async function testVoiceSystem() {
    // Esperar a que el sistema esté listo
    if (typeof window.getVoiceSystemReady === 'function') {
        await window.getVoiceSystemReady();
    }
    
    // Verificar estado
    if (typeof window.verificarEstadoVoz === 'function') {
        const estado = window.verificarEstadoVoz();
        
        if (estado.responsiveVoiceDisponible) {
            // Probar funciones básicas
            if (typeof window.leerTextoConResponsiveVoice === 'function') {
                const testText = "Hello, this is a test of the voice system.";
                window.leerTextoConResponsiveVoice(testText, 1.0);
            }
            
            // Verificar voces disponibles
            if (typeof window.obtenerVocesDisponibles === 'function') {
                void window.obtenerVocesDisponibles();
            }
        }
    }
}

// Función para mostrar información del entorno
function showEnvironmentInfo() {
    // Información disponible vía funciones; evitamos logs
    return {
        electron: typeof window.electronAPI !== 'undefined',
        responsiveVoice: typeof responsiveVoice !== 'undefined',
        config: typeof window.RESPONSIVE_VOICE_CONFIG !== 'undefined'
    };
}

// Ejecución automática de pruebas desactivada para producción

// Exponer función de prueba globalmente
window.testVoiceSystem = testVoiceSystem;
window.showEnvironmentInfo = showEnvironmentInfo;
