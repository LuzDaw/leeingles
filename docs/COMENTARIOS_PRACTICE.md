# Documentación JSDoc para practice-functions.js

## Encabezado del Archivo
```javascript
/**
 * @fileoverview Sistema de práctica de vocabulario para LeerEntender
 * Maneja tres modos de práctica: selección múltiple, escribir palabra, escribir frases
 * 
 * @author LeerEntender Team
 * @version 2.0
 * @since 1.0
 * 
 * Dependencias:
 * - global-state.js (estado centralizado)
 * - Ajax endpoints: ajax_practice_data.php, ajax_text_sentences.php
 * - CSS: practice-styles.css, dynamic-styles.css
 */
```

## Variables Globales
```javascript
/**
 * @namespace Practice
 * @description Variables globales para el sistema de práctica
 */

/**
 * Array de palabras para practicar cargadas desde la base de datos
 * @type {Array<Object>}
 * @property {string} word - Palabra en inglés
 * @property {string} translation - Traducción en español
 * @property {string} context - Contexto de uso
 */
window.practiceWords = [];

/**
 * Palabras pendientes en la sesión actual de práctica
 * @type {Array<Object>}
 */
window.practiceRemainingWords = [];

/**
 * Modo de práctica actual
 * @type {string}
 * @enum {'selection', 'writing', 'sentences'}
 */
window.practiceCurrentMode = 'selection';
```

## Funciones Principales
```javascript
/**
 * Inicializa el sistema de práctica mostrando el selector de modo
 * @async
 * @function loadPracticeMode
 * @returns {Promise<void>}
 * @description Carga la interfaz inicial y prepara el sistema de práctica
 * 
 * @example
 * await loadPracticeMode();
 */

/**
 * Muestra la interfaz de selección de modo de práctica
 * @function showPracticeModeSelector
 * @description Genera el HTML para elegir entre los tres modos disponibles
 * 
 * Modos disponibles:
 * - selection: Selección múltiple de traducciones
 * - writing: Completar palabras en frases
 * - sentences: Escribir frases completas
 */

/**
 * Establece el modo de práctica y carga los datos correspondientes
 * @function setPracticeMode
 * @param {string} mode - Modo de práctica ('selection', 'writing', 'sentences')
 * @throws {Error} Si el modo no es válido
 * 
 * @example
 * setPracticeMode('selection'); // Activa modo selección múltiple
 */

/**
 * Carga la siguiente pregunta del ejercicio
 * @function loadPracticeQuestion
 * @description Genera el HTML y lógica para la pregunta actual según el modo
 * 
 * Comportamiento por modo:
 * - selection: Muestra 4 opciones de traducción
 * - writing: Muestra frase con hueco para completar
 * - sentences: Carga frase para traducir completa
 */
```

## Funciones de Validación
```javascript
/**
 * Valida la selección en modo selección múltiple
 * @function selectPracticeOption
 * @param {string} selectedWord - Palabra seleccionada por el usuario
 * @param {string} correctWord - Palabra correcta
 * @returns {void}
 * 
 * @fires CustomEvent#practiceAnswer - Emite evento con resultado
 * 
 * @example
 * selectPracticeOption('house', 'casa'); // Valida si 'house' es correcto para 'casa'
 */

/**
 * Valida respuesta escrita en modo escribir palabra
 * @function checkPracticeWriteAnswer
 * @param {string} correctWord - Palabra correcta esperada
 * @returns {void}
 * @description Compara input del usuario con respuesta correcta
 * 
 * Características:
 * - Validación case-insensitive
 * - Trimming de espacios
 * - Feedback inmediato
 */

/**
 * Validación en tiempo real para modo escribir frases
 * @function checkSentenceInput
 * @description Valida carácter por carácter la entrada del usuario
 * 
 * Características:
 * - Validación carácter por carácter
 * - Corrección automática de errores
 * - Sistema de pistas después de 2 errores
 * - Mantenimiento de cursor al final
 */
```

## Funciones de Feedback
```javascript
/**
 * Muestra feedback visual y auditivo al usuario
 * @function showWordSuccessFeedback
 * @param {HTMLElement} inputElement - Elemento de input que activó el feedback
 * @description Crea tooltip de éxito con animación y sonido
 * 
 * Efectos:
 * - Tooltip verde con "¡Bien hecho!"
 * - Sonido de éxito
 * - Actualización de UI (ocultar input, mostrar botón siguiente)
 * - Palabra destacada en frase
 */

/**
 * Muestra mensaje de ejercicio completado
 * @function showSuccessMessage
 * @description Presenta estadísticas finales y opciones para continuar
 * 
 * Contenido:
 * - Número de palabras aprendidas
 * - Botón para repetir ejercicio
 * - Enlace para volver a textos
 */
```

## Funciones Auxiliares
```javascript
/**
 * Genera frase de práctica con contexto para una palabra
 * @function generatePracticeSentence
 * @param {string} word - Palabra a practicar
 * @returns {Object} Objeto con frase en inglés y español
 * @property {string} en - Frase en inglés con hueco (_____)
 * @property {string} es - Frase completa en español
 * 
 * @example
 * const sentence = generatePracticeSentence('house');
 * // Returns: { en: "The _____ is very big", es: "La casa es muy grande" }
 */

/**
 * Actualiza estadísticas mostradas en pantalla
 * @function updatePracticeStats
 * @description Actualiza contadores de progreso, correctas e incorrectas
 */

/**
 * Reproduce sonido de éxito
 * @function playSuccessSound
 * @description Reproduce audio de feedback positivo
 */

/**
 * Reproduce sonido de error
 * @function playErrorSound
 * @description Reproduce audio de feedback negativo
 */
```

## Eventos y Estados
```javascript
/**
 * Eventos personalizados emitidos por el sistema de práctica
 * @namespace PracticeEvents
 */

/**
 * @event practiceAnswer
 * @description Se emite cuando el usuario responde una pregunta
 * @property {Object} detail - Detalles del evento
 * @property {boolean} detail.correct - Si la respuesta fue correcta
 * @property {string} detail.word - Palabra practicada
 * @property {string} detail.mode - Modo de práctica
 */

/**
 * @event practiceComplete
 * @description Se emite cuando se completa un ejercicio
 * @property {Object} detail - Estadísticas del ejercicio
 * @property {number} detail.correct - Respuestas correctas
 * @property {number} detail.incorrect - Respuestas incorrectas
 * @property {number} detail.total - Total de palabras
 */
```

## Configuración y Constantes
```javascript
/**
 * Configuración del sistema de práctica
 * @constant {Object} PRACTICE_CONFIG
 */
const PRACTICE_CONFIG = {
    /** @type {number} Máximo de opciones en selección múltiple */
    MAX_OPTIONS: 4,
    
    /** @type {number} Errores permitidos antes de mostrar pista */
    MAX_ERRORS_BEFORE_HINT: 2,
    
    /** @type {number} Tiempo de espera para feedback (ms) */
    FEEDBACK_TIMEOUT: 2000,
    
    /** @type {string} Placeholder para palabra faltante */
    WORD_PLACEHOLDER: '_____'
};
```

## Patrones de Uso
```javascript
// Flujo típico de práctica
loadPracticeMode()
  .then(() => setPracticeMode('selection'))
  .then(() => loadPracticeQuestion())
  .then(() => {
    // Usuario interactúa...
    // selectPracticeOption() o checkPracticeWriteAnswer()
    // Feedback automático
    // nextPracticeQuestion() o showSuccessMessage()
  });
```
