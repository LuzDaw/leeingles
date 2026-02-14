# Limpieza del proyecto leeingles

Este directorio contiene herramientas iniciales para ayudar en la limpieza del proyecto.

Uso recomendado:

1. Ejecutar el escaneo desde la raíz del proyecto:

```bash
php limpieza/scan_references.php
```

2. El script generará `limpieza/unused_report.json` con archivos estáticos (imágenes, CSS, JS) que no aparecen referenciados en archivos de código/plantillas.

3. Revisar el reporte manualmente antes de borrar cualquier archivo. Algunos archivos pueden ser referenciados dinámicamente o por rutas generadas en tiempo de ejecución.

Siguientes pasos sugeridos:
- Ejecutar el script y revisar `unused_report.json`.
- Identificar duplicados en `css/` y `js/`.
- Preparar un commit con las eliminaciones aprobadas.
