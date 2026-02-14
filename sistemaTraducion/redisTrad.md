# Sistema de Traducción Híbrido - Paso 2: Búsqueda en la Caché de Redis

## Objetivo

Si la búsqueda en la base de datos del usuario (Paso 1) no devuelve ningún resultado, el sistema procede a consultar la caché de Redis. Redis actúa como una capa de caché global y de alta velocidad, compartida por todos los usuarios.

El objetivo de este paso es:
1.  **Velocidad**: Obtener traducciones comunes de forma casi instantánea desde la memoria.
2.  **Reducción de Carga**: Evitar consultas a las APIs de traducción externas, que son más lentas y tienen un coste asociado.

## Archivos Implicados

-   **`includes/translation_service.php`**: Tras el fallo del Paso 1, la función `get_translation()` continúa su ejecución y procede a llamar al servicio de caché.
-   **`includes/cache.php`**: Contiene la lógica de abstracción de la caché. La función `cache_get()` es la que interactúa directamente con Redis.
-   **`includes/config.php`**: Este archivo es responsable de establecer la conexión inicial con el servidor Redis cuando la aplicación se carga.

## Lógica de la Caché (Redis)

-   **Función Clave**: `cache_get($key)`
-   **Clave de Búsqueda**: Para mantener un orden, la clave no es simplemente la palabra. Se construye usando un prefijo estático seguido de la palabra en minúsculas.
    -   *Ejemplo*: Para la palabra `House`, la clave de búsqueda en Redis será `translation_house`.
-   **Política de Desalojo (LRU)**: La configuración de Redis en el servidor está establecida para usar una política de **LRU (Least Recently Used)**. Esto significa que cuando la memoria asignada a Redis se llena, automáticamente se eliminará la clave que lleva más tiempo sin ser utilizada para hacer espacio a una nueva traducción. Esto asegura que las traducciones más populares permanezcan en caché.

## Flujo de Resultados

1.  **Traducción Encontrada (Éxito)**:
    -   Si `cache_get()` encuentra una clave coincidente en Redis, recupera el valor (la traducción).
    -   Este valor se devuelve inmediatamente al cliente a través de la respuesta AJAX.
    -   **Importante**: La traducción recuperada de esta caché global **no se guarda** en la base de datos personal del usuario (`user_translations`) en este paso.
    -   **El proceso de búsqueda finaliza aquí.**

2.  **Traducción No Encontrada (Fallo)**:
    -   Si la clave no existe en Redis, `cache_get()` no devuelve nada.
    -   El sistema continúa al **Paso 3: Llamada a APIs Externas**.

---

## Comandos Útiles de Redis (`redis-cli`)

Para monitorizar y depurar el estado de la caché de Redis directamente desde la terminal del servidor, puedes usar la herramienta `redis-cli`.

1.  **Conectarse a la CLI de Redis**:
    ```bash
    redis-cli
    ```

2.  **Verificar el Estado del Servidor**:
    -   Comprobar si el servidor está activo:
        ```bash
        PING
        # Debería devolver: PONG
        ```
    -   Obtener un informe completo del estado de Redis:
        ```bash
        INFO
        ```

3.  **Inspeccionar el Contenido de la Caché**:
    -   Listar todas las claves de traducción almacenadas:
        ```bash
        KEYS "translation_*"
        ```
    -   Obtener la traducción para una palabra específica (el valor estará en formato JSON):
        ```bash
        GET "translation_hello"
        ```
    -   Contar el número total de claves en la base de datos:
        ```bash
        DBSIZE
        ```

4.  **Monitorizar Capacidad y Uso de Memoria**:
    -   Obtener información específica sobre la memoria:
        ```bash
        INFO memory
        # Busca los campos 'used_memory_human' y 'maxmemory_human' para ver el uso actual y el límite.
        ```
    -   Ver estadísticas de memoria más detalladas:
        ```bash
        MEMORY STATS
        ```

5.  **Limpiar la Caché (¡Usar con precaución!)**:
    -   Eliminar **todas las claves** de la base de datos actual. Útil en desarrollo si necesitas empezar de cero.
        ```bash
        FLUSHDB
        ```
