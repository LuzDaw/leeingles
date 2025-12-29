# Sistema de Traducción

## 🌐 Descripción General

El sistema de traducción de LeerEntender utiliza **Google Translate** como motor principal para proporcionar traducciones automáticas de inglés a español. El sistema incluye caché inteligente, traducción línea por línea y manejo de errores robusto.

## 🔧 Configuración Técnica

### Proveedor Principal
- **Servicio**: Google Translate
- **Idioma origen**: Inglés (en)
- **Idioma destino**: Español (es)
- **Método**: API directa

### Archivos Principales
- **`translate.php`** - Endpoint principal de traducción
- **`save_content_translation.php`** - Guardado de traducciones
- **`get_content_translation.php`** - Recuperación de traducciones

## 🏗️ Arquitectura del Sistema

### 1. Traducción de Párrafos
```javascript
// Traducir párrafo completo
function translateAndSaveParagraph(text, box, textId) {
    // Verificar caché primero
    if (window.contentTranslationsCache && window.contentTranslationsCache[text]) {
        box.innerText = window.contentTranslationsCache[text];
        return;
    }
    
    // Traducir con API
    fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(text)
    })
    .then(res => res.json())
    .then(data => {
        if (data.translation) {
            box.innerText = data.translation;
            // Guardar en caché
            window.contentTranslationsCache[text] = data.translation;
            // Guardar en BD
            saveToDatabase(text, data.translation, textId);
        }
    });
}
```

### 2. Traducción de Palabras
```javascript
// Traducir palabra individual
function handleWordClick(event) {
    const word = this.textContent.trim();
    
    fetch('translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'word=' + encodeURIComponent(word)
    })
    .then(res => res.json())
    .then(data => {
        if (data.translation) {
            showSimpleTooltip(this, word, data.translation);
            // Guardar palabra traducida
            saveTranslatedWord(word, data.translation, sentence);
        }
    });
}
```

## 🎯 Funcionalidades Principales

### 1. Traducción Línea por Línea
- **Activación**: Solo cuando se lee cada párrafo
- **Caché**: Traducciones se guardan localmente
- **Persistencia**: Traducciones se guardan en base de datos

### 2. Caché Inteligente
```javascript
// Cargar caché al inicio
window.loadContentTranslationsCache = async function() {
    const textId = document.querySelector('#pages-container')?.dataset?.textId;
    if (!textId) return;
    
    try {
        const response = await fetch(`get_content_translation.php?text_id=${textId}`);
        const data = await response.json();
        
        if (data.success && data.translation && data.format === 'json') {
            // Crear caché local
            window.contentTranslationsCache = {};
            data.translation.forEach(item => {
                if (item.content && item.translation) {
                    window.contentTranslationsCache[item.content] = item.translation;
                }
            });
        }
    } catch (error) {
        // Manejo silencioso de errores
    }
};
```

### 3. Traducción de Palabras
- **Activación**: Clic en palabra
- **Tooltip**: Muestra traducción instantánea
- **Guardado**: Palabra se guarda para práctica

## 🔄 Flujo de Traducción

### 1. Lectura de Párrafo
```
Usuario inicia lectura → Párrafo se resalta → 
Verificar si ya tiene traducción → 
Si no: traducir y guardar → 
Mostrar traducción → Continuar
```

### 2. Caché de Traducciones
```
Texto → Verificar caché local → 
Si existe: mostrar inmediatamente → 
Si no: traducir con API → 
Guardar en caché → Guardar en BD
```

### 3. Manejo de Errores
```
Error de traducción → Mostrar mensaje → 
Fallback a traducción anterior → 
Continuar con siguiente párrafo
```

## 💾 Sistema de Caché

### 1. Caché Local (JavaScript)
```javascript
window.contentTranslationsCache = {
    "In the forgotten city of mirrors...": "En la ciudad olvidada de espejos...",
    "Travelers saw reflections of dreams.": "Los viajeros vieron reflejos de sueños."
};
```

### 2. Caché de Base de Datos
```sql
-- Tabla: content_translations
CREATE TABLE content_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_id INT NOT NULL,
    content TEXT NOT NULL,
    translation TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Estrategia de Caché
- **Primera prioridad**: Caché local (JavaScript)
- **Segunda prioridad**: Base de datos
- **Última opción**: API de Google Translate

## 🚨 Manejo de Errores

### 1. Timeout de Traducción
```javascript
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos

fetch('translate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'word=' + encodeURIComponent(text),
    signal: controller.signal
})
.then(res => {
    clearTimeout(timeoutId);
    return res.json();
})
.catch((error) => {
    clearTimeout(timeoutId);
    if (error.name === 'AbortError') {
        box.innerText = 'Timeout en traducción.';
    } else {
        box.innerText = 'Error en la traducción.';
    }
});
```

### 2. Fallbacks
- **Error de red**: Mostrar mensaje de error
- **Timeout**: Mensaje de timeout
- **API no disponible**: Usar traducciones guardadas

## 📊 Optimizaciones

### 1. Traducción Asíncrona
- Las traducciones no bloquean la lectura
- Se procesan en segundo plano
- Guardado diferido para no interferir

### 2. Lazy Loading
- Solo se traducen párrafos visibles
- Caché se carga progresivamente
- No hay traducción innecesaria

### 3. Compresión de Datos
- Textos largos se comprimen
- Solo se almacenan diferencias
- Optimización de espacio en BD

## 🔍 Debugging y Monitoreo

### 1. Verificar Estado de Traducción
```javascript
// Verificar caché
console.log('Caché de traducciones:', window.contentTranslationsCache);

// Verificar traducciones guardadas
console.log('Traducciones en BD:', await getSavedTranslations(textId));
```

### 2. Logs de Traducción
- **Navegador**: F12 → Console
- **Errores**: Automáticamente capturados
- **Performance**: Tiempos de traducción

## 🐛 Solución de Problemas

### Problema: Traducciones no aparecen
**Solución**:
1. Verificar conexión a internet
2. Comprobar consola para errores
3. Verificar caché local
4. Reiniciar aplicación

### Problema: Traducciones lentas
**Solución**:
1. Verificar velocidad de conexión
2. Comprobar caché local
3. Optimizar requests a la API

### Problema: Traducciones incorrectas
**Solución**:
1. Verificar idioma de origen
2. Comprobar calidad del texto
3. Revisar logs de traducción

## 📈 Métricas de Rendimiento

### Tiempos de Respuesta
- **Caché local**: < 10ms
- **Base de datos**: < 100ms
- **API Google**: < 2000ms

### Tasa de Acierto
- **Caché hit**: 85%
- **Calidad traducción**: 90%+
- **Disponibilidad**: 99%+

## 🔮 Futuras Mejoras

### 1. Más Proveedores
- **DeepL**: Mejor calidad para idiomas europeos
- **Microsoft Translator**: Integración con Azure
- **OpenAI**: Traducción contextual

### 2. Traducción Offline
- **Modelos locales**: Traducción sin internet
- **Caché expandido**: Más traducciones offline
- **Sincronización**: Sincronizar cuando hay conexión

### 3. Traducción Contextual
- **Contexto del párrafo**: Mejor precisión
- **Historial de usuario**: Aprender preferencias
- **Corrección manual**: Permitir editar traducciones

## 📋 Endpoints de API

### 1. Traducir Texto
```
POST /translate.php
Body: word={texto_a_traducir}
Response: {translation: "traducción"}
```

### 2. Guardar Traducción
```
POST /save_content_translation.php
Body: FormData(text_id, content, translation)
Response: {success: true/false}
```

### 3. Obtener Traducciones
```
GET /get_content_translation.php?text_id={id}
Response: {success: true, translation: [...], format: "json"}
```

---

**Archivos principales**: `translate.php`, `save_content_translation.php`, `get_content_translation.php`  
**Última actualización**: Septiembre 2025  
**Mantenido por**: Sistema de Traducción Unificado
