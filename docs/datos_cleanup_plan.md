# Plan de limpieza para `docs/datos.md`

Objetivo: eliminar bloques repetidos, mensajes generados automáticos ("TASK RESUMPTION"), y consolidar información útil en documentos específicos (`docs/entorno.md`, `docs/datos_clean.md`, `docs/deploy_checklist.md`). No se modificará `docs/datos.md` hasta que apruebes el PR.

Pasos propuestos (para el PR):

1) Respaldar el original
- Acción: mover el archivo original a `docs/datos_raw.md` en el PR (copiar, no eliminar).

2) Eliminar bloques "TASK RESUMPTION"
- Criterio: eliminar cualquier línea o bloque que contenga la cadena exacta "TASK RESUMPTION" y las 1–3 líneas siguientes que repitan el contexto de working directory.
- Ejemplo: buscar regex `/TASK RESUMPTION/` y borrar cada ocurrencia más las 2 líneas siguientes.

3) Consolidar secciones "Current Working Directory (c:/xampp/htdocs/leeingles) Files"
- Criterio: detectar encabezados repetidos del tipo `# Current Working Directory (c:/xampp/htdocs/leeingles) Files` y eliminar las repeticiones dejando solo la primera si contiene información útil. Mantener listado de archivos relevante si aparece una sola vez.

4) Eliminar repeticiones de `db/leeingles.sql`
- Criterio: eliminar menciones repetidas del archivo `db/leeingles.sql` cuando aparecen más de 1 vez en secciones consecutivas. Mantener una referencia única en la sección de DB histórica.

5) Normalizar URLs y referencias a entorno
- Criterio: reemplazar apariciones directas de `http://localhost/leeingles` o `https://leeingles.com` por referencias a `docs/entorno.md` (insertar una nota: "ver docs/entorno.md"). No modificar enlaces útiles que formen parte de ejemplos técnicos, salvo que se centralicen.

6) Extraer listas largas de archivos repetidos
- Criterio: si hay múltiples listados automáticos del working directory, sustituir por una referencia corta: "Listado completo en `docs/datos_raw.md`".

7) Revisar y anotar inconsistencias de BD
- Criterio: añadir una nota clara indicando la discrepancia: "En local `db/connection.php` usa `traductor_app`; en repo existe `db/leeingles.sql` — confirmar cuál importar/usar".

8) Ejecutar comprobación final
- Acción: después del patch, ejecutar grep para asegurar que no quedan ocurrencias de `TASK RESUMPTION` ni múltiples bloques repetidos.

Comandos útiles para el autor del PR (sugeridos):

```bash
# Crear branch para limpieza
git checkout -b cleanup/docs-datos
# Copiar original como backup
cp docs/datos.md docs/datos_raw.md
# Generar cleaned file (datos_clean.md ya creado por el equipo)
# Aplicar cambios en el PR (ejemplo usando sed/perl para eliminar bloques TASK RESUMPTION)
git add docs/datos_raw.md docs/datos_clean.md docs/datos_cleanup_plan.md
git commit -m "docs: clean datos.md (plan + cleaned summary)"
git push origin cleanup/docs-datos
```

Notas para revisión
- No eliminar contenido técnico necesario sin revisarlo (ej. comandos SQL completos). Si dudas, dejar el fragmento en `docs/datos_raw.md` y anotar en el PR.
- Tras aprobación, mover `docs/datos_clean.md` a `docs/datos.md` o reemplazar el contenido, manteniendo `docs/datos_raw.md` como respaldo.

¿Aprobamos este plan para crear el PR de limpieza? Si confirmas, preparo el PR con:
- `docs/datos_raw.md` (copia completa),
- `docs/datos_clean.md` (resumen limpio),
- `docs/datos_cleanup_plan.md` (este documento),
- y un diff sugerido para eliminar bloques según reglas anteriores (opcional: aplicar cambios y mostrar patch para revisión).
