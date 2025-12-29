# Documentación del Sistema de Práctica

Este documento detalla el funcionamiento del sistema de práctica de vocabulario y frases de la aplicación, cubriendo los archivos involucrados, los modos de práctica, el flujo de usuario y las funcionalidades clave.

## 1. Introducción

El sistema de práctica de LeerEntender está diseñado para ayudar a los usuarios a reforzar su vocabulario y comprensión de frases en inglés. Ofrece tres modos de ejercicio interactivos: selección múltiple, escritura de palabras y escritura de frases, adaptándose a diferentes estilos de aprendizaje.

## 2. Archivos Intervinientes

Los principales archivos que componen el sistema de práctica son:

*   **`js/practice-functions.js`**: Este es el archivo JavaScript central que contiene toda la lógica del frontend para la práctica. Incluye funciones para inicializar los modos, cargar preguntas, validar respuestas, mostrar feedback, gestionar estadísticas y la interacción con la voz.
    *   **Dependencias**:
        *   `global-state.js` (para el estado centralizado de la aplicación).
        *   `ajax_practice_data.php`, `ajax_text_sentences.php`, `ajax_saved_words_content.php` (endpoints AJAX para cargar datos).
        *   `translate.php` (para la traducción de palabras y frases).
        *   `save_practice_progress.php`, `save_practice_time.php` (endpoints para guardar el progreso).
        *   `practice-styles.css`, `dynamic-styles.css` (estilos CSS).
*   **`practice.php`**: Este archivo PHP actúa como un endpoint AJAX para el backend. Su función principal es manejar el guardado del progreso de la práctica en la base de datos. Requiere autenticación del usuario (`$_SESSION['user_id']`).
    *   **Funcionalidad**:
        *   Recibe datos del progreso de la práctica (modo, palabras totales, correctas, incorrectas, precisión).
        *   Inserta estos datos en la tabla `practice_progress` de la base de datos.
*   **`docs/COMENTARIOS_PRACTICE.md`**: Documentación JSDoc que describe las funciones, variables globales y eventos del archivo `js/practice-functions.js`. Sirve como una guía detallada para desarrolladores.
*   **`save_practice_progress.php`**: (Mencionado en `js/practice-functions.js`) Este script PHP es invocado por el frontend para guardar el progreso de la práctica del usuario en la base de datos.
*   **`save_practice_time.php`**: (Mencionado en `js/practice-functions.js`) Este script PHP es invocado por el frontend para guardar el tiempo que el usuario ha dedicado a una sesión de práctica.
*   **`ajax_user_texts.php`**: (Mencionado en `js/practice-functions.js`) Utilizado para listar los textos disponibles (propios y públicos) para que el usuario seleccione uno para practicar.
*   **`ajax_saved_words_content.php`**: (Mencionado en `js/practice-functions.js`) Utilizado para obtener las palabras guardadas de un texto específico, que son la base para los ejercicios de práctica.
*   **`translate.php`**: (Mencionado en `js/practice-functions.js`) Utilizado para obtener traducciones de palabras o frases a través de una API.

## 3. Modos de Práctica

El sistema ofrece tres modos de práctica, seleccionables por el usuario:

*   **📝 Selección múltiple**: El usuario ve una frase en inglés con un hueco y debe elegir la palabra correcta de entre varias opciones.
*   **✍️ Escribir palabra**: El usuario ve una frase en inglés con un hueco y debe escribir la palabra correcta para completarla. Incluye validación en tiempo real y pistas.
*   **📖 Escribir frases**: El usuario ve una frase en español y debe escribir la traducción completa en inglés. Este modo también cuenta con validación carácter por carácter y un sistema de pistas.

## 4. Flujo de la Práctica

1.  **Carga Inicial (`loadPracticeMode`)**: Al acceder a la pestaña de práctica, se muestra un selector de modo (`showPracticeModeSelector`).
2.  **Selección de Modo (`setPracticeMode`)**: El usuario elige uno de los tres modos. Por defecto, se activa "Selección múltiple".
3.  **Selección de Texto (`loadSentencePractice`, `showTextSelector`)**: Se presenta una lista de textos (propios y públicos) de los cuales el usuario ha guardado palabras. El usuario selecciona un texto para iniciar el ejercicio.
4.  **Carga de Palabras/Frases**:
    *   Para los modos "Selección múltiple" y "Escribir palabra", se cargan las palabras guardadas del texto seleccionado a través de `ajax_saved_words_content.php`.
    *   Para el modo "Escribir frases", se generan frases de práctica a partir de las palabras guardadas.
5.  **Carga de Pregunta (`loadPracticeQuestion` / `loadSentenceQuestion`)**: Se selecciona una palabra/frase aleatoria de las pendientes y se genera la interfaz de la pregunta según el modo.
6.  **Interacción del Usuario**: El usuario responde a la pregunta (seleccionando, escribiendo una palabra o escribiendo una frase).
7.  **Validación y Feedback**:
    *   **`selectPracticeOption`**: Valida la selección múltiple.
    *   **`checkPracticeWriteAnswer`**: Valida la palabra escrita.
    *   **`initForcedDictationInput`**: Gestiona la validación carácter por carácter para la escritura de frases.
    *   Se muestra feedback visual (carteles de "¡Correcto!" / "Incorrecto") y auditivo (sonidos de éxito/error).
    *   Las palabras correctas se eliminan de `practiceRemainingWords` o se reinsertan al final si son incorrectas (para repetición).
8.  **Pistas (`showPracticeHint`, `showSentenceHint`)**: El usuario puede solicitar una pista, que muestra parte de la palabra o frase correcta.
9.  **Traducción en Línea**: Al hacer clic en una palabra de la frase en inglés, se muestra su traducción en un tooltip (`handlePracticeWordClick`, `showPracticeTooltip`). También hay un botón para ver la traducción completa de la frase (`showPracticeTranslation`, `showEnglishSentence`).
10. **Siguiente Pregunta (`nextPracticeQuestion` / `nextSentenceQuestion`)**: Una vez respondida la pregunta, el usuario puede avanzar a la siguiente.
11. **Resultados (`showPracticeResults` / `showSentenceResults`)**: Cuando no quedan palabras/frases pendientes, se muestran las estadísticas finales del ejercicio y opciones para continuar. El progreso y el tiempo de práctica se guardan en el backend.

## 5. Funcionalidades Clave

*   **Generación de Contexto**: Las frases de práctica se generan utilizando el contexto original de la palabra en el texto, o plantillas genéricas si no hay contexto disponible.
*   **Validación Flexible**: La validación de respuestas es `case-insensitive` y maneja el `trimming` de espacios.
*   **Sistema de Pistas Inteligente**: En el modo "Escribir palabra", las pistas se adaptan al progreso del usuario, mostrando la parte correcta más la siguiente letra.
*   **Traducción Dinámica**: Utiliza `translate.php` para obtener traducciones de palabras individuales y frases completas bajo demanda.
*   **Integración de Voz**: Permite escuchar las palabras y frases en inglés utilizando el sistema unificado de ResponsiveVoice o un fallback nativo.
*   **Estadísticas en Tiempo Real**: Contadores de preguntas, correctas e incorrectas, y una barra de progreso se actualizan dinámicamente.
*   **Persistencia de Datos**: El progreso y el tiempo de práctica se guardan en la base de datos del usuario, permitiendo un seguimiento a largo plazo.
*   **Modo "Siempre Visible"**: Un icono de ojo permite al usuario mantener la traducción de la frase visible automáticamente en cada pregunta.

## 6. Variables Globales y Configuración

### Variables Globales (`window.*` en `js/practice-functions.js`)

*   `practiceWords`: Array de objetos con todas las palabras cargadas para la sesión de práctica.
*   `practiceRemainingWords`: Array de objetos con las palabras aún pendientes en la sesión actual.
*   `practiceCurrentMode`: Modo de práctica actual ('selection', 'writing', 'sentences').
*   `practiceCurrentQuestionIndex`: Índice de la pregunta actual.
*   `practiceCorrectAnswers`: Contador de respuestas correctas.
*   `practiceIncorrectAnswers`: Contador de respuestas incorrectas.
*   `practiceAnswered`: Booleano que indica si la pregunta actual ya ha sido respondida.
*   `practiceCurrentWordIndex`: Índice de la palabra actual en `practiceRemainingWords`.
*   `practiceCurrentSentenceData`: Objeto con la frase en inglés, español, original, palabra y traducción para la pregunta actual.
*   `practiceAlwaysShowTranslation`: Booleano para controlar si la traducción se muestra automáticamente.
*   `practiceStartTime`, `practiceEndTime`, `practiceDuration`: Variables para medir el tiempo de la sesión de práctica.
*   `practiceResultsActive`: Booleano para indicar si la pantalla de resultados está activa.
*   `currentWordErrors`: Contador de errores en la palabra actual (para modo "Escribir palabra").
*   `sentenceTexts`, `currentSentences`, `currentSentenceIndex`, `sentenceErrors`, `sentenceCorrectAnswers`, `sentenceIncorrectAnswers`: Variables específicas para el modo "Escribir frases".
*   `currentTextTitle`: Título del texto que se está practicando.

### Constante de Configuración (`PRACTICE_CONFIG`)

Aunque no se define explícitamente como `PRACTICE_CONFIG` en el código actual, los valores como el número máximo de opciones en selección múltiple, errores permitidos antes de la pista y el placeholder de la palabra faltante están implícitos en la lógica de `js/practice-functions.js`.

## 7. Consideraciones Adicionales

*   **Manejo de Errores**: El sistema incluye manejo de errores para la carga de textos y la conexión con el servidor, mostrando mensajes informativos al usuario.
*   **Experiencia de Usuario**: Se prioriza un feedback inmediato y claro, con animaciones y sonidos para mejorar la interactividad.
*   **Accesibilidad**: Se utilizan atributos `aria-label` y `role="img"` para mejorar la accesibilidad de elementos como el botón de altavoz.
