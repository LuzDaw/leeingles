# datos_clean.md — resumen limpio

Este documento es una versión condensada y limpia de `docs/datos.md`. Contiene solo la información útil y verificada para el equipo.

## Entorno y URLs
- Producción: https://leeingles.com
- Local: http://localhost/leeingles

## Base de datos
- En local `db/connection.php` apunta a la base `traductor_app` (host `localhost:3306`, usuario `root`, charset `utf8mb4`).
- En el repo aparece `db/leeingles.sql` como dump histórico; confirmar si debe importarse o mantenerse como archivo histórico.

## Archivos y puntos clave
- Archivo de configuración de rutas central: `includes/config.php` (define `BASE_URL`, `BASE_PATH`, `url()`, `asset()` y inyecta `window.APP`).
- Entradas que requieren atención:
  - `actions/email_handler.php` — leer credenciales SMTP desde entorno (`.env`).
  - `recordatorio/email_templates.php` — usar `BASE_URL` para botones.
  - JS que realiza `fetch()` hacia rutas internas — usar `window.APP.BASE_URL`.

## Flujo de despliegue (resumen)
- Subida manual por SFTP (controlada). `sftp.json` con `uploadOnSave:false`.
- Crear `.env` en servidor (no subir desde local).
- Backups de BD antes de cada despliegue (`mysqldump`).

## Inconsistencias detectadas (acción requerida)
- `docs/datos.md` contiene múltiples bloques repetidos y mensajes de `TASK RESUMPTION` generados automáticamente: limpiar y consolidar.
- Documentación menciona `db/leeingles.sql` pero la configuración local usa `traductor_app`. Aclarar cuál es la BD oficial en cada entorno y documentarlo.

## Estado actual
- Código refactorizado para rutas portables (se añadió `includes/config.php`), `window.APP.BASE_URL` inyectado y varios `fetch`/links adaptados.
- `docs/entorno.md` creado (resumen de entorno operativo).

---
Para detalles o para restaurar la versión original completa de `docs/datos.md`, consultar `docs/datos.md` (archivo original).
