# LeerEntender - Documentación Técnica (actualizada)

Aplicación web para aprender inglés leyendo: traducción inmediata al hacer clic, lectura con voz, vocabulario personal, práctica y seguimiento de progreso.

## 📁 Estructura del proyecto

```
traductor/
├─ css/                  # Hojas de estilo modulares
├─ db/                   # Scripts y conexión a db
├─ docs/                 # Documentación
├─ includes/             # Funciones PHP reutilizables (palabras, títulos, contenido, práctica)
├─ js/                   # JavaScript modular (lector, práctica, traducciones, UI)
├─ img/                  # Recursos gráficos
├─ google_api/           # Integraciones (dependencias)
├─ textoPublic/          # Textos de ejemplo/demos
├─ index.php             # Página principal (lectura, paneles)
├─ translate.php         # API de traducción (DeepL → Google backup)
├─ diccionario.php       # API de diccionario (Merriam‑Webster)
├─ practice.php          # API para guardar progreso de práctica
├─ saved_words.php       # Vista de vocabulario guardado
├─ logueo_seguridad/login.php / logueo_seguridad/register.php / logueo_seguridad/logout.php
├─ logueo_seguridad/ajax_login.php, logueo_seguridad/ajax_register.php # Endpoints AJAX (textos, progreso, práctica, subida)
├─ save_*.php / get_*.php
└─ ...
```

## 🎯 Características principales
- Lectura interactiva con traducción al hacer clic y tooltips por palabra.
- Lectura con voz (botón flotante ▶️, control de velocidad, pausar/reanudar).
- Paginación automática por número de palabras; modo de lectura limpia.
- Subida de textos (privados o públicos) y categorización.
- Guardado automático de palabras con traducción y contexto.
- Panel de palabras guardadas agrupadas por texto.
- Práctica de vocabulario: selección, escritura, y frases, con precisión por modo.
- Panel de progreso: palabras, textos, ejercicios, actividad reciente y calendario.

## 🔌 Sistema de traducción híbrido
- Flujo: intenta DeepL primero; si falla o tarda, hace fallback a Google Translate.
- Detección simple de idioma (inglés ↔ español) para elegir dirección.
- Respuesta JSON: `{ translation, source, original, detected_language }`.
- Timeouts cortos y User‑Agent configurado para robustez.

Endpoints implicados:
- `translate.php` (POST `text` | `word`) → DeepL (primario) → Google (backup).
- `diccionario.php` (GET `palabra`) → Merriam‑Webster (definición, categoría, ejemplos, sinónimos/antónimos, pronunciación/audio).
- El botón “Explica” usa `diccionario.php` y traduce definiciones, sinónimos, antónimos y ejemplos vía `translate.php`.

## 🧠 Caché de traducciones (evitar llamadas repetidas)
- Títulos: `texts.title_translation` (persistido). Funciones en `includes/title_functions.php`.
- Contenido: `texts.content_translation` (persistido, formato JSON simple). Funciones en `includes/content_functions.php` y utilidades JS en `js/content-translation-functions.js` (`get_content_translation.php` / `save_content_translation.php`).
- Palabras: tabla `saved_words` guarda `word`, `translation`, `context` y `text_id`. Se reutiliza para práctica y vistas, evitando retraducir lo ya aprendido.
- Frontend: evita retraducciones en elementos ya procesados (`data-translated`) y controla visibilidad; estado de lectura se persiste en `localStorage` (no contiene claves ni traducciones sensibles).

## 🧩 JavaScript modular (principales)
- Core: `global-state.js` (estado central), `lector.js` (lectura), `practice-functions.js` (práctica), `common-functions.js`.
- Lectura/UI: `floating-menu.js`, `header-functions.js`, `fullscreen-fix.js`, `fullscreen-translation.js`, `multi-word-selection.js`.
- Traducciones: `content-translation-functions.js`, `title-translation-functions.js`.
- Diccionario y “Explica”: `explain-sidebar.js` (sidebar, sinónimos/antónimos/ejemplos con traducción bajo demanda).
- Gestión: `text-management.js`, `upload-form.js`, `public-texts-dropdown.js`, `main.js`.

## 🎨 CSS modular (principales)
- Base/tema: `common-styles.css`, `modern-styles.css`, `color-theme.css`, `mobile-ready.css`.
- Lectura/UX: `reading-styles.css`, `text-styles.css`, `floating-menu.css`, `explain-sidebar.css`, `modal-styles.css`.
- App/páginas: `landing-page.css`, `index-page.css`, `tab-system.css`, `practice-styles.css`, `progress-styles.css`, `saved-words-styles.css`, `login-styles.css`, `upload-form.css`, `calendar-styles.css`.

## 🗄️ Base de datos (MySQL)
Tablas destacadas:
- `users` (username, email, password, is_admin, timestamps).
- `texts` (user_id, title, content, is_public, category_id, title_translation, content_translation, created_at).
- `categories` (name, description).
- `saved_words` (user_id, word, translation, context, text_id, review_count, last_reviewed, created_at).
- `practice_progress` (user_id, text_id, mode, total_words, correct_answers, incorrect_answers, accuracy, session_date).
- `reading_time` y `practice_time` para métricas temporales.

Scripts de referencia: `db/create_database.sql`, `db/create_practice_progress.sql`, `db/create_reading_time.sql`, `db/create_saved_words.sql`.

## 🔗 Endpoints y AJAX (selección)
- Lectura/páginas: `index.php`, `saved_words.php`.
- Auth: `logueo_seguridad/login.php`, `logueo_seguridad/register.php`, `logueo_seguridad/logout.php`, `logueo_seguridad/ajax_login.php`, `logueo_seguridad/ajax_register.php`.
- Textos: `ajax_user_texts.php`, `ajax_upload_text.php`, `print_texts.php`, `delete_text.php`, `admin_categories.php`.
- Progreso: `ajax_progress_content.php`, `get_practice_stats.php`, `save_practice_progress.php`, `save_practice_time.php`, `save_reading_time.php`, `ajax_calendar_data.php`.
- Práctica: `ajax_practice_data.php`, `ajax_text_sentences.php`, `practice.php` (guardar sesiones).
- Traducciones: `translate.php`, `diccionario.php`, `get_content_translation.php`, `save_content_translation.php`.

## 🧭 Flujo resumido
1) Usuario carga un texto (opcionalmente público y con categoría).
2) Lee en la vista principal: clic en palabras → traducción; audio con control de velocidad; paginación automática.
3) Las palabras traducidas se guardan con contexto; el título/fragmentos pueden persistir traducción.
4) Practica el vocabulario guardado en 3 modos; el sistema registra precisión y sesiones.
5) Consulta panel de progreso y calendario de actividad.

## 🔒 Notas de seguridad y configuración
- Uso de consultas preparadas en endpoints y funciones `includes/*`.
- Evitar claves de API en el código. Externalizarlas (variables de entorno/archivo no versionado) y cargarlas en tiempo de ejecución.
- Limitar endpoints AJAX sensibles a usuarios autenticados.
- Timeouts y manejo de errores en integraciones externas.

## 📄 Documentos relacionados
- Sistema de APIs y botón “Explica”: `docs/SISTEMA_APIS_EXPLICA.md`.
- Manual de usuario: `MANUAL_USUARIO.md`.

—
Última actualización: 17-08-2025
