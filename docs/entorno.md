# Entorno de Trabajo — LeeIngles

Fecha: 2026-02-11

Resumen breve del entorno que usamos para desarrollar y desplegar LeeIngles.

1. Entornos
- Producción: https://leeingles.com
- Local (desarrollo): http://localhost/leeingles (XAMPP)

2. Base de datos
- Local: MySQL (mysqli) configurado en `db/connection.php`.
  - Host: `localhost:3306`
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
