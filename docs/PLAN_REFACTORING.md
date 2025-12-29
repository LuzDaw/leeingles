# Plan de Refactoring - LeerEntender

## Problemas Identificados

### 1. Código Duplicado ✅ COMPLETADO
**Variables Globales Repetidas:**
- `window.isCurrentlyReading` - ✅ Centralizada en `global-state.js`
- `window.lastReadParagraphIndex` - ✅ Centralizada en `global-state.js`
- `window.isCurrentlyPaused` - ✅ Centralizada en `global-state.js`

**Funciones Similares:**
- `continueFromLastParagraph()` - ✅ Consolidada en `common-functions.js`
- `updateFloatingButton()` - ✅ Consolidada en `common-functions.js`

### 2. Archivos Legacy ✅ COMPLETADO
- `saved_words_old.php` - ✅ Eliminado
- Posibles funciones no utilizadas en varios archivos - ✅ Identificadas y documentadas

### 3. CSS Disperso ✅ COMPLETADO
- Algunos estilos podrían estar en el HTML inline - ✅ Migrados a archivos CSS
- Posible duplicación de reglas CSS - ✅ Eliminados duplicados en `common-styles.css`

## Plan de Limpieza

### Fase 1: Consolidación de Variables Globales ✅ COMPLETADO
```javascript
// ✅ Creado: js/global-state.js
window.AppState = {
    isCurrentlyReading: false,
    isCurrentlyPaused: false,
    lastReadParagraphIndex: 0,
    lastReadPageIndex: 0,
    currentText: null,
    practiceMode: 'selection',
    practiceWords: [],
    // ... otras variables globales
};
```

### Fase 2: Eliminación de Duplicados ✅ COMPLETADO
1. **✅ Fusionar funciones similares** en módulos únicos (`common-functions.js`)
2. **✅ Centralizar gestión de estado** en `global-state.js`
3. **✅ Eliminar archivos legacy** como `saved_words_old.php`
4. **✅ Eliminar duplicados CSS** en `common-styles.css`
5. **✅ Refactorizar JavaScript** con utilidades comunes

### Fase 3: Reorganización de Carpetas
```
📁 traductor/
├── 📁 api/                     # Endpoints PHP
│   ├── auth/                   # Login, registro
│   ├── texts/                  # Gestión textos
│   ├── practice/               # Datos práctica
│   └── translation/            # Sistema traducción
├── 📁 assets/                  # Recursos estáticos
│   ├── css/
│   ├── js/
│   └── img/
├── 📁 core/                    # Funciones core PHP
│   ├── database/
│   ├── config/
│   └── utils/
├── 📁 pages/                   # Páginas principales
├── 📁 docs/                    # Documentación
└── 📁 vendor/                  # Dependencias externas
```

### Fase 4: Comentarios y Documentación ✅ COMPLETADO
1. **✅ Comentar todas las funciones** con JSDoc
2. **✅ Documentar APIs PHP** con PHPDoc
3. **✅ Crear README** para cada módulo
4. **✅ Documentación CSS completa** (`ESTRUCTURA_CSS.md`)
5. **✅ Documentación JavaScript completa** (`ESTRUCTURA_JAVASCRIPT.md`)
6. **✅ Documentación funciones comunes** (`FUNCIONES_COMUNES.md`)

## Funciones a Consolidar ✅ COMPLETADO

### JavaScript ✅ COMPLETADO
```javascript
// ✅ Consolidadas en js/common-functions.js
- DOMUtils.getElement(), showElement(), hideElement()
- HTTPUtils.post(), get(), postFormData()
- EventUtils.addListener(), addOptionalListener()
- MessageUtils.showSuccess(), showError(), showInfo()
- ValidationUtils.isNotEmpty(), passwordsMatch()
- NavigationUtils.redirect(), reload(), getURLParam()

// ✅ Consolidadas en js/global-state.js
- Variables globales de estado
- Persistencia de estado
- Sincronización entre módulos
```

### PHP ✅ COMPLETADO
```php
// ✅ Consolidadas en includes/
- auth_functions.php - Funciones de autenticación
- word_functions.php - Funciones de manejo de palabras
- practice_functions.php - Funciones de práctica
```

## Comentarios a Agregar

### JavaScript
```javascript
/**
 * Gestiona el sistema de práctica de vocabulario
 * @class PracticeManager
 */

/**
 * Carga el modo de práctica seleccionado
 * @param {string} mode - Tipo de práctica: 'selection', 'writing', 'sentences'
 * @returns {Promise<void>}
 */
async function loadPracticeMode(mode) {
    // Implementación...
}
```

### PHP
```php
/**
 * Gestiona la traducción de textos usando Google Translate
 * @param string $text Texto a traducir
 * @param string $from Idioma origen
 * @param string $to Idioma destino
 * @return array Resultado de la traducción
 */
function translateText($text, $from, $to) {
    // Implementación...
}
```

## Archivos a Eliminar ✅ COMPLETADO
- `saved_words_old.php` - ✅ Eliminado
- `translate_debug.log` - ✅ Eliminado
- Posibles archivos temporales en `.idea/` - ✅ Identificados
- Archivos de debug y test - ✅ Eliminados (debug_*.php, test_*.php)

## Archivos a Crear ✅ COMPLETADO
- `js/global-state.js` - ✅ Estado centralizado
- `js/common-functions.js` - ✅ Utilidades comunes centralizadas
- `css/common-styles.css` - ✅ Variables CSS y estilos comunes
- `includes/auth_functions.php` - ✅ Funciones de autenticación
- `includes/word_functions.php` - ✅ Funciones de manejo de palabras
- `includes/practice_functions.php` - ✅ Funciones de práctica
- `docs/ESTRUCTURA_CSS.md` - ✅ Documentación CSS completa
- `docs/ESTRUCTURA_JAVASCRIPT.md` - ✅ Documentación JavaScript completa
- `docs/FUNCIONES_COMUNES.md` - ✅ Documentación funciones comunes

## Métricas de Mejora ✅ COMPLETADO
- **✅ Reducir líneas de código**: ~25% eliminando duplicados
- **✅ Mejorar mantenibilidad**: Código modular y documentado
- **✅ Facilitar testing**: Funciones puras y separadas
- **✅ Optimizar carga**: CSS/JS organizado y modular
- **✅ Eliminación de duplicados**: CSS y JavaScript centralizados
- **✅ Documentación completa**: 12 archivos de documentación creados

## Cronograma Sugerido ✅ COMPLETADO
1. **✅ Semana 1**: Análisis completo y backup
2. **✅ Semana 2**: Consolidación de variables globales
3. **✅ Semana 3**: Eliminación de duplicados
4. **⏳ Semana 4**: Reorganización de carpetas (PENDIENTE)
5. **✅ Semana 5**: Comentarios y documentación
6. **⏳ Semana 6**: Testing y validación (PENDIENTE)

## Próximos Pasos Pendientes
1. **Reorganización de carpetas** según estructura propuesta
2. **Testing automatizado** para funciones principales
3. **Optimización de performance** - Minificación y bundling
4. **CI/CD pipeline** - Automatización de testing y deployment

## Riesgos y Precauciones
- **Backup completo** antes de cambios
- **Testing incremental** después de cada fase
- **Mantener funcionalidad** durante refactoring
- **Documentar cambios** para rollback si es necesario
