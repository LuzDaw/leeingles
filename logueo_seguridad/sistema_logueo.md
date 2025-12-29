# Documentación del Sistema de Logueo

Este documento detalla los archivos que componen el sistema de logueo de la aplicación, sus funciones y cómo interactúan entre sí. Todos los archivos mencionados se encuentran ahora en la carpeta `logueo_seguridad/`.

## Archivos Principales del Sistema de Logueo

### 1. `logueo_seguridad/login.php`

*   **Función:** Presenta el formulario de inicio de sesión a los usuarios y procesa sus credenciales.
*   **Proceso:**
    *   Inicia la sesión y requiere `db/connection.php` para la conexión a la base de datos.
    *   **Implementa la generación y verificación de tokens CSRF para seguridad.** Un token CSRF (Cross-Site Request Forgery) es un valor secreto y único que la aplicación genera para cada sesión y lo incrusta en los formularios. Esto previene ataques donde un atacante intenta engañar a un usuario autenticado para que realice acciones no deseadas.
    *   Al recibir una solicitud POST, **valida el token CSRF** (comparando el token recibido del formulario con el almacenado en la sesión del servidor) y las credenciales (nombre de usuario/email y contraseña) contra la tabla `users`.
    *   Utiliza `password_verify()` para la verificación segura de contraseñas hasheadas.
    *   Si las credenciales son correctas, regenera el ID de sesión, establece las variables de sesión (`user_id`, `username`, `is_admin`) y redirige al usuario a `index.php`.
    *   Muestra mensajes de error si el token es inválido, la contraseña es incorrecta o el usuario/email no se encuentra.
*   **Interacciones:** Enlaza a `logueo_seguridad/register.php` para el registro de nuevos usuarios.

### 2. `logueo_seguridad/register.php`

*   **Función:** Presenta el formulario de registro para nuevos usuarios y gestiona la creación de cuentas.
*   **Proceso:**
    *   Inicia la sesión y requiere `db/connection.php`.
    *   Al recibir una solicitud POST, valida los campos de entrada (nombre de usuario, email, contraseña) y el formato del email.
    *   Verifica si el nombre de usuario o el email ya existen en la base de datos.
    *   Si los datos son válidos y únicos, hashea la contraseña (`password_hash()`) y la inserta en la tabla `users`.
    *   En caso de registro exitoso, inicia sesión automáticamente al nuevo usuario (estableciendo `user_id` y `username` en la sesión) y lo redirige a `index.php`.
    *   Muestra mensajes de error para campos obligatorios, formato de email inválido, usuario/email existente o errores de base de datos.
*   **Interacciones:** Enlaza a `logueo_seguridad/login.php` para iniciar sesión si el usuario ya tiene una cuenta.

### 3. `logueo_seguridad/logout.php`

*   **Función:** Gestiona el cierre de la sesión del usuario.
*   **Proceso:**
    *   Inicia la sesión.
    *   Destruye todas las variables de sesión (`session_destroy()`).
    *   Redirige al usuario a `index.php`.
*   **Interacciones:** Es el punto de salida para los usuarios autenticados.

### 4. `logueo_seguridad/ajax_login.php`

*   **Función:** Proporciona un endpoint para el inicio de sesión asíncrono (AJAX), permitiendo a los usuarios iniciar sesión sin recargar la página.
*   **Proceso:**
    *   Requiere `db/connection.php` y `session_start()`.
    *   Devuelve respuestas en formato JSON.
    *   Procesa solicitudes POST, valida las credenciales y las verifica contra la base de datos.
    *   Si el login es exitoso, establece las variables de sesión.
    *   Incluye la funcionalidad "Mantener sesión" para extender la duración de la misma.
    *   Retorna un objeto JSON indicando `success` (true/false) y un mensaje.
*   **Interacciones:** Consumido por scripts de JavaScript en el frontend para una experiencia de usuario dinámica.

### 5. `logueo_seguridad/ajax_register.php`

*   **Función:** Proporciona un endpoint para el registro de usuarios asíncrono (AJAX), permitiendo a los usuarios registrarse sin recargar la página.
*   **Proceso:**
    *   Requiere `db/connection.php` y `session_start()`.
    *   Devuelve respuestas en formato JSON.
    *   Procesa solicitudes POST, valida los datos de registro y verifica la unicidad del nombre de usuario/email.
    *   Hashea la contraseña y la inserta en la base de datos.
    *   Si el registro es exitoso, inicia sesión automáticamente al nuevo usuario y retorna un objeto JSON con `success: true`.
    *   Retorna un objeto JSON con `success: false` y un mensaje de error en caso de problemas.
*   **Interacciones:** Consumido por scripts de JavaScript en el frontend para una experiencia de usuario dinámica.

### 6. `logueo_seguridad/auth_functions.php`

*   **Función:** Contiene un conjunto de funciones reutilizables para la autenticación, diseñadas para centralizar la lógica y reducir la duplicación de código.
*   **Funciones principales:**
    *   `generateCSRFToken()`: Crea un token CSRF.
    *   `verifyCSRFToken($token)`: Valida un token CSRF.
    *   `authenticateUser($username, $password, $remember_me)`: Encapsula la lógica de verificación de credenciales y gestión de sesión.
    *   `registerUser($username, $email, $password)`: Encapsula la lógica de registro de nuevos usuarios.
    *   `isAuthenticated()`: Comprueba el estado de autenticación del usuario.
    *   `getCurrentUserId()`: Obtiene el ID del usuario logueado.
    *   `getCurrentUsername()`: Obtiene el nombre de usuario logueado.
    *   `isAdmin()`: Verifica si el usuario actual tiene privilegios de administrador.
*   **Interacciones:** Este archivo está diseñado para ser incluido (`require_once`) en otros scripts PHP que necesiten funcionalidades de autenticación. Aunque `logueo_seguridad/login.php` y `logueo_seguridad/ajax_login.php` actualmente implementan su propia lógica de autenticación, este archivo ofrece una alternativa para refactorizar y centralizar esas operaciones.

## Resumen de Interacción del Sistema

El sistema de logueo ofrece dos vías principales para la autenticación y el registro:

1.  **Formularios Tradicionales:** A través de `logueo_seguridad/login.php` y `logueo_seguridad/register.php`, que manejan las solicitudes directamente y recargan la página.
2.  **Solicitudes AJAX:** A través de `logueo_seguridad/ajax_login.php` y `logueo_seguridad/ajax_register.php`, que son utilizados por JavaScript en el frontend para una interacción más fluida y sin recargas.

`logueo_seguridad/logout.php` es el punto de salida universal. `logueo_seguridad/auth_functions.php` actúa como una biblioteca de funciones de autenticación que puede ser utilizada para mantener la consistencia y la seguridad en todo el sistema. Todos estos componentes dependen de `db/connection.php` para la gestión de la base de datos de usuarios.
