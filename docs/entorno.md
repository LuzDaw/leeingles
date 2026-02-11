# Entorno de Trabajo — LeeIngles

Fecha: 2026-02-11

Resumen breve del entorno que usamos para desarrollar y desplegar LeeIngles.

1. Entornos
- Producción: https://leeingles.com
- Local (desarrollo): http://localhost/leeingles (XAMPP)

2. Base de datos
- Local: MySQL (mysqli) configurado en `db/connection.php`.
  - Host: `localhost`
  - Usuario: `root`
  - Base de datos: `traductor_app`
  - Charset: `utf8mb4`

3. Configuración y rutas
- Archivo central: `includes/config.php` — define `BASE_URL`, `BASE_PATH`, funciones `url()` y `asset()`, e inyecta `window.APP` a JS.
- Variables de entorno: usar `.env` (plantilla en `.env.example`). No versionar `.env`.

4. JS cliente
- `window.APP.BASE_URL` disponible para que los scripts construyan llamadas `fetch()` y referencias a assets de forma portable.

5. Despliegue y control de subidas
- Subidas manuales por SFTP (controladas). Archivo de configuración SFTP: `.vscode/sftp.json`.
- `uploadOnSave` en `sftp.json` = `false` para evitar subidas automáticas.

6. Credenciales y seguridad
- Mover SMTP y secretos a `.env` en servidor (no en repo). `actions/email_handler.php` puede leer `SMTP_*` desde entorno.
- `sftp.json` contiene credenciales FTP; mantenerlo privado y fuera de repositorios públicos.

## Instrucciones para `.env` (local y producción)

- Importante: **NUNCA** subir `.env` al repositorio. En producción sube el archivo tú manualmente (SFTP/SSH).
- Copia la plantilla: clona `.env.example` a `.env` y rellena los valores.

Variables mínimas obligatorias:

- `DB_HOST` — host de la base de datos (ej. `localhost` o `127.0.0.1:3306`).
- `DB_USER` — usuario de la base de datos.
- `DB_PASSWORD` — contraseña de la base de datos.
- `DB_NAME` — nombre de la base de datos.
- `APP_BASE_URL` — URL base del sitio (ej. `https://leeingles.com/` en producción).
- `DEEPL_API_KEY` o `GOOGLE_TRANSLATE_KEY` — al menos una clave de traducción si usas el servicio.

Variables recomendadas para email (usadas por `includes/email_service.php`):

- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE` (ssl/tls), `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`.

Pasos recomendados para producción:

1. En tu entorno local, crea `.env` a partir de `.env.example` y verifica la app.
2. En producción, crea el archivo `.env` con los mismos nombres de variables y valores de producción.
3. Sube `.env` por SFTP/SSH al directorio raíz de la aplicación (donde está `index.php`).
4. Ajusta permisos para que el archivo no sea accesible públicamente (por ejemplo `chmod 640` y dueño `www-data`/usuario del servidor).
5. Reinicia PHP/Apache-FPM si procede (depende del host) o limpia caches aplicables.

Notas adicionales:

- Si necesitas mantener variables antiguas (`EMAIL_SMTP_*`) el código ofrece compatibilidad; preferimos usar `SMTP_*`.
- Mantén un `.env.produc` fuera del repo (como plantilla privada) si te ayuda a desplegar, pero nunca lo comprometas públicamente.


7. Flujo de trabajo y backups
- Trabajar en ramas (ej. `feature/portable-routes`). Commit local antes de subir.
- Backups antes de despliegue: `mysqldump` para BD y copia empaquetada de los archivos web.

8. Comprobaciones post-despliegue
- Verificar: carga de la web, `window.APP.BASE_URL`, flujos críticos (registro, verificación por email, restablecer contraseña, subida AJAX).
- Revisar logs del servidor (Apache/PHP) tras cada despliegue.

9. Archivos relevantes de referencia
- `index.php`, `includes/config.php`, `db/connection.php`, `actions/email_handler.php`, `recordatorio/email_templates.php`, `.vscode/sftp.json`.

10. Regla operativa
- Siempre probar localmente, subir manualmente por SFTP y validar en producción antes de marcar cambios como cerrados.

Contacto/Responsable: Equipo de desarrollo LeeIngles
