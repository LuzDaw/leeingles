# Diagnóstico: Títulos Sin Traducción Mostrando

## Síntoma

Al abrir la pestaña "Mis Textos", los títulos no muestran su traducción al español.

**Ejemplo observado:**
```html
<span class="title-english">Good customer service</span>
<span class="title-spanish" style="color: #6b7280; font-size: 0.9em; margin-left: 8px;"></span>
```

El span `title-spanish` está **vacío**, lo que significa que `title_translation` es NULL o vacío en la BD.

---

## Diagnóstico

### Raíz del Problema

Los textos fueron subidos **antes de que se corrigiera** la URL de traducción en `upload_text.php` (línea 61). Esto causó que:

1. Los nuevos textos se insertaran en la BD correctamente
2. **PERO** la llamada a `translate.php` fallara silenciosamente (URL hardcodeada incorrecta)
3. El campo `title_translation` quedara NULL

### Verificación en BD

**Ejemplo para texto ID 182:**
```sql
SELECT id, title, title_translation FROM texts WHERE id = 182;
```

**Resultado:**
```
id: 182
title: Good customer service
title_translation: NULL  ← ❌ VACÍO
```

---

## Solución

### Opción 1: Traducir Manualmente vía API (Recomendado)

Usar el endpoint que traduce automáticamente los títulos faltantes:

**URL para traducir TODOS:** 
```
POST a /traductor/translate_titles_batch.php
Parámetro: action=translate_all
```

**URL para traducir UNO:**
```
POST a /traductor/translate_titles_batch.php
Parámetros: action=translate_single&text_id=182
```

**Respuesta JSON:**
```json
{
    "success": true,
    "text_id": 182,
    "original": "Good customer service",
    "translation": "Buen servicio al cliente",
    "message": "Título traducido correctamente"
}
```

**Para usar desde consola del navegador:**
```javascript
// Traducir un título específico (ID 182)
const formData = new FormData();
formData.append('action', 'translate_single');
formData.append('text_id', 182);

fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log(data));

// Traducir TODOS los títulos sin traducción
const formData2 = new FormData();
formData2.append('action', 'translate_all');

fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: formData2
})
.then(r => r.json())
.then(data => console.log(data));
```

### Opción 2: Actualización Directa en BD

```sql
-- Para un texto específico
UPDATE texts SET title_translation = 'Buen servicio al cliente' 
WHERE id = 182 AND user_id = [TU_USER_ID];

-- Para todos los textos de un usuario sin traducción
UPDATE texts 
SET title_translation = title  -- Temporal, se debería traducir
WHERE user_id = [TU_USER_ID] AND (title_translation IS NULL OR title_translation = '');
```

### Opción 3: Esperar a Subir Nuevos Textos

Ahora que se corrigió `upload_text.php`, **nuevos textos se traducirán automáticamente**.

---

## Verificación de la Corrección

El fix en `upload_text.php` (línea 56-96) ahora:

1. **Construye URL dinámica:**
   ```php
   $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
   $host = $_SERVER['HTTP_HOST'];
   $translate_url = $protocol . '://' . $host . '/traductor/translate.php';
   ```

2. **Valida respuestas:**
   ```php
   $curl_error = curl_error($ch);
   if ($response && empty($curl_error)) {
       // Procesar traducción
   }
   ```

3. **Registra en logs:**
   ```php
   error_log("Traducción de título guardada: '{$title}' -> '{$translation}'");
   ```

---

## Archivos Disponibles

| Archivo | Propósito |
|---------|-----------|
| `translate_titles_batch.php` | **NEW** - Endpoint para traducir títulos en batch |
| `save_title_translation.php` | **NEW** - Endpoint para guardar traducción de título |
| `get_title_translation.php` | **NEW** - Endpoint para obtener traducción de título |
| `upload_text.php` | Corregido - traduce automáticamente al subir |
| `translate.php` | API de traducción (DeepL/Google) |
| `includes/title_functions.php` | Funciones: saveTitleTranslation(), getTitleTranslation() |

---

## Próximos Pasos

### Para el Usuario
1. Usar `translate_missing_titles.php` para traducir títulos existentes
2. Subir nuevos textos (se traducirán automáticamente)
3. Verificar que aparezcan las traducciones en "Mis Textos"

### Para el Desarrollador
- [ ] Implementar traducción automática en background job
- [ ] Agregar caché de traducciones
- [ ] Implementar retry logic si falla la API
- [ ] Agregar UI para manual translation fallback

---

## Esperado vs Actual

**Esperado (después del fix):**
```html
<span class="title-english">Good customer service</span>
<span class="title-spanish" style="color: #eaa827;">• Buen servicio al cliente</span>
```

**Actual (para textos viejos):**
```html
<span class="title-english">Good customer service</span>
<span class="title-spanish" style="color: #6b7280;"></span>
```

---

## Script de Batch Update (SQL)

Si prefieres actualizar muchos títulos a la vez usando Google Translate vía SQL (avanzado):

```sql
-- Crear tabla temporal con traducciones
CREATE TEMPORARY TABLE translations AS
SELECT 
    id,
    title,
    title AS translation
FROM texts
WHERE user_id = [TU_USER_ID] AND (title_translation IS NULL OR title_translation = '');

-- Luego actualizar manualmente o usar la herramienta web
UPDATE texts t
JOIN translations tr ON t.id = tr.id
SET t.title_translation = tr.translation;
```

**NOTA:** La mejor opción es usar la herramienta `translate_missing_titles.php` que llama a la API real.
