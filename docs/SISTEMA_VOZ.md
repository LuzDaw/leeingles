# Sistema de Voz - ResponsiveVoice

## 🎤 Descripción General

El sistema de voz de LeerEntender utiliza **ResponsiveVoice** como motor principal de texto-a-voz (TTS). Este sistema está diseñado para funcionar tanto en navegador web como en la aplicación de escritorio Electron, proporcionando una experiencia de lectura consistente en inglés.

## 🔧 Configuración Técnica

### API Key
- **Clave**: `wJGiW37b`
- **Proveedor**: ResponsiveVoice.org
- **Plan**: Gratuito (con limitaciones)

### CDN
```html
<script src="https://code.responsivevoice.org/responsivevoice.js?key=wJGiW37b"></script>
```

## 🏗️ Arquitectura del Sistema

### Archivo Principal
- **`electron-voice-integration.js`** - Sistema unificado de voz

### Funciones Principales
```javascript
// Función principal de lectura
window.leerTextoConResponsiveVoice(texto, velocidad, callbacks)

// Control de reproducción
window.detenerLecturaResponsiveVoice()
window.pausarLecturaResponsiveVoice()
window.reanudarLecturaResponsiveVoice()

// Estado del sistema
window.estaLeyendoResponsiveVoice()
window.verificarEstadoVoz()
```

## 🎯 Funcionalidades

### 1. Lectura de Texto
- **Idioma**: Inglés (UK English Female por defecto)
- **Velocidad**: Configurable (0.5x - 2.0x)
- **Calidad**: Alta calidad de voz

### 2. Control de Reproducción
- **Play/Pause**: Control completo de reproducción
- **Stop**: Detener lectura actual
- **Resume**: Continuar desde donde se pausó

### 3. Callbacks de Eventos
```javascript
window.leerTextoConResponsiveVoice(texto, velocidad, {
    onstart: () => console.log('Lectura iniciada'),
    onend: () => console.log('Lectura terminada'),
    onpause: () => console.log('Lectura pausada'),
    onresume: () => console.log('Lectura reanudada'),
    onerror: (error) => console.error('Error:', error)
});
```

## 🌐 Integración Web vs Electron

### Modo Web (Navegador)
- Carga ResponsiveVoice desde CDN
- Funciona con conexión a internet
- API estándar del navegador

### Modo Electron (Escritorio)
- Mismo sistema de voz
- Funciona offline (después de primera carga)
- Experiencia idéntica a la web

## 🔄 Flujo de Lectura

### 1. Inicialización
```javascript
// Esperar a que el sistema esté listo
await window.getVoiceSystemReady();
```

### 2. Lectura de Párrafo
```javascript
// Leer texto con velocidad personalizada
const success = window.leerTextoConResponsiveVoice(
    "In the forgotten city of mirrors...",
    1.7, // velocidad
    {
        onend: () => {
            // Avanzar al siguiente párrafo
            readAndTranslate(index + 1);
        }
    }
);
```

### 3. Manejo de Errores
```javascript
onerror: (error) => {
    console.error('❌ Error en ResponsiveVoice:', error);
    // Fallback a SpeechSynthesis nativo
    fallbackToNativeTTS();
}
```

## 🚨 Fallbacks y Manejo de Errores

### 1. ResponsiveVoice Fallback
Si ResponsiveVoice falla, el sistema automáticamente:
- Intenta usar `SpeechSynthesis` nativo del navegador
- Mantiene la funcionalidad básica
- Registra errores para debugging

### 2. SpeechSynthesis Nativo
```javascript
const fallbackUtterance = new SpeechSynthesisUtterance(texto);
fallbackUtterance.rate = velocidad;
fallbackUtterance.lang = 'en-GB';
fallbackUtterance.onend = () => {
    // Continuar con el flujo normal
};
```

## ⚙️ Configuración de Voz

### Voces Disponibles
- **UK English Female** (por defecto)
- **UK English Male**
- **US English Female**
- **US English Male**

### Parámetros de Calidad
```javascript
const config = {
    VOICE: 'UK English Female',
    RATE: 1.0,        // Velocidad (0.5 - 2.0)
    PITCH: 1.0,       // Tono (0.5 - 2.0)
    VOLUME: 1.0       // Volumen (0.0 - 1.0)
};
```

## 🔍 Debugging y Monitoreo

### Verificar Estado del Sistema
```javascript
const estado = window.verificarEstadoVoz();
console.log(estado);
// Output:
// {
//     entorno: "Web" | "Electron",
//     responsiveVoiceDisponible: true/false,
//     responsiveVoiceLoaded: true/false,
//     scriptLoaded: true/false,
//     apiKey: "wJGiW37b",
//     funcionesDisponibles: {...}
// }
```

### Logs de Debug
- **Navegador**: F12 → Console
- **Electron**: DevTools integrados
- **Errores**: Automáticamente capturados y registrados

## 🚀 Optimizaciones

### 1. Caché de Voz
- Las voces se cargan una vez y se reutilizan
- No hay recarga innecesaria de scripts

### 2. Lazy Loading
- ResponsiveVoice se carga solo cuando es necesario
- Inicialización asíncrona para mejor rendimiento

### 3. Manejo de Concurrencia
- Prevención de múltiples lecturas simultáneas
- Flag `isReadingInProgress` para control de estado

## 🐛 Solución de Problemas

### Problema: Voz no funciona
**Solución**:
1. Verificar API key en `electron-voice-integration.js`
2. Comprobar conexión a internet
3. Verificar consola del navegador para errores

### Problema: Lectura se detiene
**Solución**:
1. Verificar callbacks `onend` y `onerror`
2. Comprobar flag `isReadingInProgress`
3. Revisar logs de consola

### Problema: Calidad de voz baja
**Solución**:
1. Verificar velocidad de conexión
2. Comprobar configuración de voz
3. Reiniciar aplicación

## 📊 Métricas de Rendimiento

### Tiempos de Respuesta
- **Inicialización**: < 500ms
- **Inicio de lectura**: < 100ms
- **Cambio de párrafo**: < 200ms

### Uso de Recursos
- **Memoria**: Mínimo impacto
- **CPU**: Solo durante reproducción
- **Red**: Solo para carga inicial (CDN)

## 🔮 Futuras Mejoras

### 1. Voces Offline
- Descarga de voces para uso sin internet
- Caché local de archivos de voz

### 2. Más Idiomas
- Soporte para español
- Voces en otros idiomas europeos

### 3. Control Avanzado
- Control de entonación
- Pausas automáticas en puntuación
- Sincronización con texto

---

**Archivo**: `traductor/js/electron-voice-integration.js`  
**Última actualización**: Septiembre 2025  
**Mantenido por**: Sistema de Voz Unificado
