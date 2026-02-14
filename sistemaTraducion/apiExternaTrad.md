# Sistema de Traducción Híbrido - Paso 3: Llamada a APIs Externas y Almacenamiento

## Objetivo

Este es el último recurso del sistema de traducción, activado únicamente cuando una palabra no se ha encontrado ni en la base de datos personal del usuario (Paso 1) ni en la caché global de Redis (Paso 2).

El objetivo de este paso es doble:
1.  **Obtener la Traducción**: Conseguir una traducción precisa utilizando servicios externos de alta calidad.
2.  **Poblar las Cachés**: Una vez obtenida la traducción, guardarla en las capas de caché (Redis y base de datos del usuario) para que futuras solicitudes de la misma palabra sean mucho más rápidas y no consuman más cuota de API.

## Archivos Implicados

-   **`includes/translation_service.php`**: Orquesta la lógica de llamadas a las APIs externas.
-   **`includes/external_services.php`**: (O similar) Contiene las funciones específicas que encapsulan la comunicación con cada API (ej. `translateWithDeepL()`, `translateWithGoogle()`).
-   **`includes/cache.php`**: Su función `cache_set()` se utiliza para guardar el nuevo resultado en Redis.
-   **`traduciones/translate.php`**: El script que recibe el resultado final y lo guarda en la base de datos del usuario.

## Lógica de Llamadas a APIs (Con Fallback)

El sistema no depende de un único proveedor, sino que implementa un mecanismo de fallback para aumentar su resiliencia.

1.  **Prioridad 1: DeepL**
    -   El sistema primero intenta obtener la traducción utilizando la API de **DeepL**. Generalmente, se considera que ofrece traducciones de mayor calidad y más naturales.

2.  **Prioridad 2: Google Translate (Fallback)**
    -   Si la llamada a DeepL falla por cualquier motivo (el servicio está caído, se ha superado la cuota, la clave de API es incorrecta, etc.), el sistema no se rinde.
    -   Automáticamente, realiza un segundo intento utilizando la API de **Google Translate** como mecanismo de respaldo.

## Flujo de Resultados: El Círculo Virtuoso del Almacenamiento

Una vez que una de las APIs devuelve una traducción con éxito, el sistema realiza dos operaciones cruciales para optimizar el futuro:

1.  **Guardado en Caché de Redis (Paso 2)**
    -   **¿Qué?**: La traducción obtenida se guarda inmediatamente en la caché global de Redis.
    -   **¿Cómo?**: Se llama a la función `cache_set('translation_palabra', $traduccion, $ttl)`.
    -   **¿Por qué?**: Para que la próxima vez que **cualquier usuario** busque esta misma palabra, la encuentre directamente en Redis, evitando por completo las llamadas a la API. El `TTL` (Time To Live) asegura que la caché se renueve periódicamente.

2.  **Guardado en Base de Datos del Usuario (Paso 1)**
    -   **¿Qué?**: La misma traducción se guarda también en la tabla `user_translations`.
    -   **¿Cómo?**: El script `traduciones/translate.php` inserta un nuevo registro asociando la `original_word`, la `translated_word` y el `user_id`.
    -   **¿Por qué?**: Para que la próxima vez que **este usuario específico** busque la palabra, la encuentre en su historial personal, que es la capa de caché más rápida y prioritaria.

Este doble guardado es lo que hace que el sistema sea tan eficiente y sostenible. Cada llamada exitosa a la API fortalece las capas de caché, reduciendo la probabilidad de futuras llamadas.

### Fallo Total

En el caso improbable de que tanto DeepL como Google Translate fallen, el sistema devolverá un error al cliente, indicando que la traducción no pudo ser obtenida.
