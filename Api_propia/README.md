Api_propia - Import TMP cache to Redis
=====================================

Este directorio contiene utilidades para poblar Redis con los ficheros de `tmp_cache/`.

import_tmp_cache_to_redis.php
- Script PHP que itera los ficheros `tmp_cache/*.cache` y los inserta en Redis con clave `translate:<hash>`.
- Requisitos:
  - Extensión PHP `redis` instalada (clase `Redis`).
  - Variables de entorno (opcional): `REDIS_HOST`, `REDIS_PORT`, `REDIS_DB`, `REDIS_PASS`.

  - Alternativa sin extensión: usar `Predis` vía Composer.
    - Instalar: `composer require predis/predis`
    - El script detecta `Predis` en `vendor/autoload.php` y lo usará automáticamente.

Uso:

```bash
# desde la raíz del proyecto
php Api_propia/import_tmp_cache_to_redis.php
```

Notas:
- El script interpreta cada `.cache` como JSON con estructura `{ "expires": <epoch>, "data": <obj> }`.
- Sólo importará entradas no expiradas (TTL > 0). La clave Redis usada es `translate:<hash>` donde `<hash>` viene del nombre del fichero (si tiene formato `translate_<hash>.cache`) o del md5 del contenido.
# desde la raíz del proyecto
- Después de importar, las claves tendrán TTL acorde al `expires` del fichero.
 
# mover (archivar) los ficheros importados a tmp_cache/imported/
php Api_propia/import_tmp_cache_to_redis.php --move
