**Rollback automático desde backups `.bak.similar`**

Archivos incluidos:
- `rollback_restore.ps1` — PowerShell (Windows). Ejecutar desde la raíz del proyecto `C:\xampp\htdocs\leeingles`.
- `rollback_restore.sh` — Bash (Linux/macOS). Ejecutar desde la raíz del proyecto.

Uso (PowerShell):

```powershell
# Dry run (lista lo que se restauraría)
.\limpieza\rollback_restore.ps1

# Aplicar restauración
.\limpieza\rollback_restore.ps1 -Apply
```

Uso (bash):

```bash
# Dry run
./limpieza/rollback_restore.sh

# Aplicar restauración
./limpieza/rollback_restore.sh --apply
```

Notas:
- Los scripts buscan recursivamente archivos con sufijo `.bak.similar` y los mueven sobre el archivo original (eliminando el sufijo). Los backups se sobrescriben en la restauración.
- Siempre ejecutar primero el dry-run para verificar la lista antes de aplicar.
- Después de restaurar, verifica permisos y, si procede, reinicia servicios web o limpia cachés.
