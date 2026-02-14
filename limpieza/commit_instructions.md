# Instrucciones de commit - Limpieza 2026-02-07

Antes de commitear:
- Verificar localmente las rutas críticas (login, uploads, pagos).
- Ejecutar `php -l` sobre los ficheros modificados.
- Hacer una petición HEAD a la web de producción para comprobar disponibilidad:

```
php -l path/to/changed.php
curl -I -k https://leeingles.com/
```

Comandos sugeridos para commit y push:

```
git add limpieza CHANGELOG.md
git add -u
git commit -m "limpieza: consolidación controlada (whitelist), limpieza HTML/CSS y scripts de rollback; backups .bak.similar incluidos"
git tag -a v2026.02.07-limpieza -m "Limpieza: consolidación controlada y scripts de rollback"
git push origin main
git push origin v2026.02.07-limpieza
```

Notas:
- Para excluir backups del commit, use `git reset -- **/*.bak.similar` antes de `git commit` o ajuste el `git add`.
- Los reportes y backups están en la carpeta `limpieza/`.
