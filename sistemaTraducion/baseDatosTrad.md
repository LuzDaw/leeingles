# Sistema de Traducción Híbrido - Paso 1: Búsqueda en Base de Datos

## Objetivo

El primer paso en el flujo de obtención de una traducción es consultar la base de datos local. El objetivo es buscar si el usuario actual ya ha guardado previamente una traducción para la palabra seleccionada.

Este enfoque tiene dos ventajas principales:
1.  **Personalización**: Permite que los usuarios vean sus propias traducciones guardadas, manteniendo la consistencia.
2.  **Optimización**: Evita realizar llamadas innecesarias a servicios externos (Redis o APIs de traducción), lo que ahorra costes y reduce la latencia.

## Archivos Implicados

-   **`traduciones/translate.php`**: Es el punto de entrada de la solicitud. Cuando un usuario hace clic en una palabra en la interfaz, una petición AJAX llega a este script con la palabra a traducir.
-   **`includes/translation_service.php`**: Contiene la lógica de negocio principal. Es el orquestador que decide dónde y cómo buscar la traducción.

## Funciones Clave

Dentro de `includes/translation_service.php`:

-   **`get_translation()`**: Es la función principal que gestiona el proceso. Su primera acción es invocar a `get_user_translation()` para realizar la búsqueda en la base de datos.
-   **`get_user_translation()`**: Esta función es la responsable directa de interactuar con la base de datos.

## Lógica de la Base de Datos

-   **Tabla**: `user_translations`
-   **Consulta**: La función `get_user_translation()` ejecuta una consulta `SELECT` para encontrar una entrada que coincida con:
    -   `user_id`: El ID del usuario que ha iniciado sesión.
    -   `original_word`: La palabra exacta que el usuario ha seleccionado.

## Flujo de Resultados

1.  **Traducción Encontrada (Éxito)**:
    -   Si la consulta a la tabla `user_translations` devuelve un resultado, significa que el usuario ya había traducido y guardado esa palabra.
    -   Esa traducción se devuelve inmediatamente como respuesta a la petición AJAX.
    -   **El proceso de búsqueda finaliza aquí.**

2.  **Traducción No Encontrada (Fallo)**:
    -   Si la consulta no encuentra ninguna coincidencia para ese `user_id` y `original_word`.
    -   La función no devuelve ningún dato.
    -   El sistema continúa al **Paso 2: Búsqueda en la caché de Redis**.
