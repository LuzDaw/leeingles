**Resumen de sugerencias de consolidación**

- Generado: 2026-02-07
- Umbral usado: 99% (similar_consolidation_suggestions.json)
- Cluster principal: canonical `actions/delete_text.php` con ~32 candidatos (ver `limpieza/similar_consolidation_suggestions.json`).

**Observaciones importantes**
- La mayoría de candidatos pertenecen a rutas sensibles (UI, auth, includes, pagos, ajax, traducciones, practicas, admin).
- Aplicar wrappers automáticamente a estos ficheros puede romper la aplicación (HTTP 500), como ya ocurrió.

**Recomendación**
1. No aplicar consolidación automática sobre todo el cluster.
2. Revisar manualmente las siguientes categorías y aprobar ficheros concretos:
   - Tests y utilidades (`*/test/*`, `*check_db.php`)
   - Scripts que no exponen rutas públicas (batch, CLI)
3. Una vez aprobada la lista, ejecutar `apply_similar_consolidation.php --whitelist limpieza/whitelist.txt` con backups activados.

**Acción propuesta ahora**
- Espero tu aprobación para:
  - generar una whitelist propuesta basada en rutas concretas que autorices, o
  - aplicar solo a los ficheros marcados como "test" si das permiso.
