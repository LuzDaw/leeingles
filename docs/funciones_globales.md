# Funciones Globales — LeeIngles

Este documento recopila funciones globales (PHP y JS) candidatas a ser centralizadas y reutilizadas en el proyecto. Sirve como base para eliminar duplicados y mejorar la mantenibilidad.

---

## Funciones Globales JS

### practicas/js/practice-functions.js
- `window.configureEnglishVoice(utterance)`
- `window.savePracticeTime(seconds, isFinal = false)`
- `window.startPracticeTimer()`
- `window.stopPracticeTimer()`
- `window.loadPracticeMode()`
- `window.setPracticeMode(mode)`
- `window.loadPracticeQuestion()`
- `window.checkWordInput(correctWord)`
- `window.selectPracticeOption(selected, correct)`
- `window.checkPracticeWriteAnswer(correct)`
- `window.showPracticeTranslation()`
- `window.nextPracticeQuestion()`
- `window.restartPracticeExercise()`
- `window.showPracticeHint(word)`
- `window.initForcedDictationInput(correctText)`
- `window.toggleCustomSelect()`
- `window.selectPracticeText(event, id)`
- `window.startSentencePractice()`
- `window.showEnglishSentence()`
- ...otras funciones internas auxiliares (normalizeWord, getSmartHint, etc.)

### lectura/js/text-management.js
- `window.saveTranslatedWord(word, translation, sentence = '')`
- `window.showFloatingButton()`
- `window.hideFloatingButton()`
- `window.updateFloatingButton()`
- `window.continueFromLastParagraph()`
- `window.countWordsInText(text)`
- `window.countLettersInText(text)`

### lectura/js/public-texts-dropdown.js
- (Funciones auxiliares para dropdown de textos públicos)

---

## Funciones Globales JS (fuera de practicas/lectura)

### js/upload-form.js
- `showUploadForm()` — Muestra el formulario de subida de texto.
- `hideUploadForm()` — Oculta el formulario de subida de texto.

### js/modal-functions.js
- `requireLogin(action)` — Requiere login antes de ejecutar una acción.
- `showUploadFormWithLogin()` — Muestra el formulario de subida tras login.

### js/header-functions.js
- `hideHeader()` — Oculta el header.
- `showHeader()` — Muestra el header.
- `toggleMobileMenu()` — Muestra/oculta menú móvil.
- `onReadingStart()`, `onReadingStop()`, `onPracticeStart()`, `onPracticeEnd()` — Hooks para eventos de lectura/práctica.
- `showHeaderOnReadingPage()` — Muestra header en página de lectura.

### js/calendar-functions.js
- `loadCalendarData(month, year)` — Carga datos del calendario.
- `updateCalendarDisplay(data)` — Actualiza la visualización del calendario.
- `updateCalendarDays(calendarData)` — Actualiza los días del calendario.
- `isCurrentDay(dateString)` — Verifica si es el día actual.
- `previousMonth()`, `nextMonth()` — Navegación de meses.
- `startRealTimeUpdates()`, `stopRealTimeUpdates()` — Control de actualizaciones en tiempo real.
- `updateCalendarNow()` — Fuerza actualización del calendario.
- `initializeCalendar()` — Inicializa el calendario.

(Otras funciones internas auxiliares en practicas/js/practice-functions.js y lectura/js/lector.js ya listadas en secciones previas)

---

## Funciones Globales LECTURA (JS)

- `window.saveTranslatedWord(word, translation, sentence = '')` — Guarda una palabra traducida en la base de datos del usuario.
- `window.showFloatingButton()` — Muestra los botones flotantes de menú y reproducción/pausa.
- `window.hideFloatingButton()` — Oculta los botones flotantes de menú y reproducción/pausa.
- `window.updateFloatingButton()` — Actualiza el texto y título del botón flotante según el estado de lectura.
- `window.continueFromLastParagraph()` — Continúa la lectura desde el último párrafo leído.
- `window.loadPublicTexts()` — Carga los textos públicos en el contenedor correspondiente.
- `window.countWordsInText(text)` — Cuenta el número de palabras en un texto.
- `window.countLettersInText(text)` — Cuenta el número de letras en un texto.

---

## Funciones Globales PHP

(Pendiente de completar: se irán añadiendo a medida que se identifiquen en includes/, ajax/, actions/, etc.)

---

## Funciones Globales AJAX

### PHP
- `formatReadingTime($seconds)` — ajax/ajax_calendar_data.php: Formatea segundos a string tipo '1h 20m'.

### JS (en scripts embebidos en PHP)
- `initializeUploadForm()` — ajax/ajax_upload_content.php: Inicializa el formulario de subida y gestiona la visibilidad de la sección de categoría.
- `initializeCustomSelect()` — ajax/ajax_upload_content.php: Inicializa el select personalizado de categorías.
- `loadPracticeStats()` (async) — lectura/ajax/ajax_progress_content.php: Carga estadísticas de práctica vía AJAX.

---

## Funciones Globales PRACTICAS (PHP)

- `translateUsingExistingSystem($text)` — practicas/ajax_text_sentences.php: Traduce texto usando Google Translate API con fallback local.

---

## Funciones Globales TRADUCIONES (PHP)

- `obtenerInfoPalabra($palabra)` — traduciones/diccionario.php: Obtiene información de una palabra (definición, ejemplos, sinónimos, etc.) usando Merriam-Webster y cache.

---

## Funciones Globales INCLUDES (PHP)

### practice_functions.php
- `savePracticeProgress($user_id, $mode, $total_words, $correct_answers, $incorrect_answers, $text_id = null)`
- `savePracticeTime($user_id, $mode, $duration)`
- `getPracticeStats($user_id)`
- `getReadingProgress($user_id)`
- `get_total_reading_seconds($user_id)`
- `saveReadingTime($user_id, $text_id, $duration)`
- `get_total_practice_seconds($user_id)`
- `get_completed_texts_count($user_id)`
- `deleteReadingProgressByTextIds($user_id, $text_ids)`
- `getReadingProgressEntry($user_id, $text_id)`
- `saveReadingProgress($user_id, $text_id, $percent, $pages_read, $finish = 0)`

### word_functions.php
- `saveTranslatedWord($user_id, $word, $translation, $context = '', $text_id = null)`
- `getSavedWords($user_id, $text_id = null, $limit = null)`
- `countSavedWords($user_id, $text_id = null)`
- `getWordStatsByDate($user_id, $days = 7)`
- `getRandomWordsForPractice($user_id, $limit = 10)`
- `deleteSavedWord($user_id, $word, $text_id = null)`
- `deleteSavedWordsBulk($user_id, $items)`
- `deleteSavedWordsByTextIds($user_id, $text_ids)`

### user_functions.php
- `delete_user_account($conn, $user_id)`

### translation_service.php
- `detectLanguage($text)`
- `translateWithDeepL($text, $target_lang, $api_key)`
- `translateWithGoogle($text, $source_lang, $target_lang)`
- `translateText($text)`

### title_functions.php
- `saveTitleTranslation($text_id, $title, $translation)`
- `getTitleTranslation($text_id)`
- `getTextsWithTranslations($user_id = null, $limit = null)`
- `needsTitleTranslation($text_id)`
- `getTitleTranslationStats($user_id = null)`

### helpers.php
- `limpiarEjemploMerriamWebster(string $texto): string`
- `normalizarTexto(string $texto): string`
- `make_cache_key(string $prefix, string $texto, string $salt = ''): string`
- `generate_hex_token(int $bytes = 32): string`
- `hash_token(string $token, string $algo = 'sha256'): string`
- `safe_cache_set(string $key, $value, int $ttl = 3600): void`

### external_services.php
- `external_translate_text(string $text, array $opts = [])`
- `external_send_email(string $toEmail, string $toName, string $subject, string $body)`
- `external_charge(array $paymentPayload)`

### email_service.php
- `sendEmail($toEmail, $toName, $subject, $body)`

### dictionary_service.php
- `getFreeDictionaryInfo($word)`
- `getWordsAPIInfo($word)`
- `processFreeDictionaryData($data)`
- `processWordsAPIData($data)`
- `getDictionaryInfo($word)`

### db_helpers.php
- `get_text_if_allowed($conn, int $text_id, int $user_id)`
- `update_text_title_translation($conn, int $text_id, string $title_translation)`

### content_functions.php
- `saveContentTranslation($text_id, $content, $translation)`
- `getContentTranslation($text_id)`
- `getTextsWithContentTranslations($user_id = null, $limit = null)`
- `needsContentTranslation($text_id)`
- `getContentTranslationStats($user_id = null)`
- `getTotalUserTexts($user_id)`
- `render_text_clickable($text, $title = '', $title_translation = '')`
- `get_index_page_data($conn)`

### config.php
- `url($path = '')`
- `asset($path = '')`
- `inject_app_config_js()`

### cache.php
- `cache_dir_path()`
- `cache_key_to_path($key)`
- `cache_set($key, $value, $ttl = 3600)`
- `cache_get($key)`
- `cache_delete($key)`

### ajax_helpers.php
- `ajax_error($message = 'Error del servidor', $code = 500, $details = null)`
- `ajax_success($data = [], $code = 200)`
- `renderTextItem($row, $user_id, $is_public_list = false)`

### ajax_common.php
- `ensureSessionStarted()`
- `noCacheHeaders()`
- `requireUserOrExitJson()`
- `requireUserOrExitHtml()`

---

## Funciones Globales dePago (PHP)

### subscription_functions.php
- `getSubscriptionData($user_id)`
- `getUserSubscriptionStatus($user_id)`
- `initUserSubscription($user_id)`
- `getWeeklyUsage($user_id)`
- `incrementTranslationUsage($user_id, $text)`
- `getInactiveLimitedUsers($days = 14)`
- `checkTranslationLimit($user_id, $is_active_reading = false)`
- `debugUpdateRegistrationDate($user_id, $new_date)`
- `debugAddUsage($user_id, $words)`
- `debugUpdateLastConnection($user_id, $new_date)`

### payment_functions.php
- `activateUserPlan($user_id, $plan, $paypal_id, $method = 'paypal')`
- `handlePaypalWebhookResource($resource, $is_completed = true)`

### test/webhook_handler.php
- `resetUser()`

### test/sandbox_minimal.php
- `notifyServer(orderID, status, planName)`
- `initPayPalButton(containerId, amount, description, planName)`
- `initAll()`

### paypal_*.php
- `render()`

---

## Funciones Globales admin (JS)

### admin_categories.php (JS embebido)
- `deleteCategory(categoryId)` — Elimina una categoría vía AJAX.
- `showAlert(type, message)` — Muestra alertas en la interfaz de administración.

---

## Siguiente paso
- Revisar y clasificar funciones PHP globales.
- Identificar duplicados y proponer centralización en helpers/utilidades.
- Marcar funciones candidatas a eliminación o refactorización.
