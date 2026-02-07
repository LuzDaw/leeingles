# CHANGELOG

## [Unreleased] - 2026-02-07

- **Limpieza y consolidación controlada:** se detectaron duplicados y similares; se aplicó una consolidación controlada únicamente a los ficheros aprobados en `limpieza/whitelist.txt` (32 ficheros reemplazados con wrappers que usan rutas relativas). Los originales se preservaron como backups con sufijo `.bak.similar`.
- **Herramientas añadidas en `limpieza/`:** utilidades para detectar duplicados y similares, aplicar consolidación, corregir wrappers relativos y verificar (`find_duplicates.php`, `summarize_similar.php`, `apply_similar_consolidation.php`, `fix_wrappers_to_relative.php`, `verify_wrappers.php`, entre otros). Reportes generados: `limpieza/duplicates_report.json`, `limpieza/similar_consolidation_suggestions.json`, `limpieza/apply_similar_actions.json`.
- **Seguridad operacional:** se usó una whitelist para evitar cambios en código sensible (UI, login, pagos). Se realizaron comprobaciones de sintaxis (`php -l`) y peticiones HEAD a producción (https://leeingles.com/) después de los cambios — producción respondió HTTP 200 OK.
- **Rollback y documentación:** añadidos `limpieza/rollback_restore.ps1`, `limpieza/rollback_restore.sh` y `limpieza/rollback_instructions.md`. También se generaron `limpieza/changes_summary.md` y `limpieza/similar_suggestions_review.md` para auditoría y revisión manual.
- **Limpieza HTML/CSS:** reglas duplicadas y estilos redundantes refactorizados en varios ficheros CSS.

**Notas importantes:**
- No se ha ejecutado `git commit` desde el entorno del agente; el usuario realizará el commit manualmente. Backups `.bak.similar` están presentes junto a cada fichero reemplazado.

## Instrucciones de commit sugeridas

Antes de hacer `git commit` se recomienda verificar localmente los cambios críticos (ej.: probar login, upload y pagos) y ejecutar `php -l` sobre los ficheros modificados.

Comandos sugeridos:

```
git add limpieza CHANGELOG.md
git add -u
git commit -m "limpieza: consolidación controlada (whitelist), limpieza HTML/CSS y scripts de rollback; backups .bak.similar incluidos"
git tag -a v2026.02.07-limpieza -m "Limpieza: consolidación controlada y scripts de rollback"
git push origin main
git push origin v2026.02.07-limpieza
```

Si se prefiere no incluir los backups en el commit, ajustar el `git add` para excluir los `*.bak.similar`.

---

*Detalles completos y reportes en la carpeta `limpieza/`.*
