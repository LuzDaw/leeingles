# API Endpoints para Traducciones de Títulos

## Resumen

Se han creado tres nuevos endpoints siguiendo el patrón existente de `save_content_translation.php` y `get_content_translation.php`.

---

## 1. Guardar Traducción de Título

**Endpoint:** `save_title_translation.php`

**Método:** POST

**Parámetros requeridos:**
- `text_id` (int) - ID del texto
- `title` (string) - Título original en inglés
- `translation` (string) - Traducción al español

**Ejemplo de uso:**
```javascript
const formData = new FormData();
formData.append('text_id', 182);
formData.append('title', 'Good customer service');
formData.append('translation', 'Buen servicio al cliente');

fetch('/traductor/save_title_translation.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log(data));
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Traducción de título guardada correctamente"
}
```

**Respuesta de error:**
```json
{
    "error": "Descripción del error"
}
```

**Errores posibles:**
- `'Usuario no autenticado'` - No hay sesión de usuario
- `'Datos incompletos'` - Faltan parámetros
- `'Título y traducción son requeridos'` - Campos vacíos
- `'Texto no encontrado o no autorizado'` - El usuario no es propietario del texto
- `'Error verificando autorización'` - Error en la base de datos

---

## 2. Obtener Traducción de Título

**Endpoint:** `get_title_translation.php`

**Método:** GET

**Parámetros requeridos:**
- `text_id` (int) - ID del texto

**Ejemplo de uso:**
```javascript
fetch('/traductor/get_title_translation.php?text_id=182')
    .then(r => r.json())
    .then(data => console.log(data));
```

**Respuesta exitosa (con traducción):**
```json
{
    "success": true,
    "text_id": 182,
    "title": "Good customer service",
    "translation": "Buen servicio al cliente",
    "source": "database"
}
```

**Respuesta cuando NO hay traducción:**
```json
{
    "success": false,
    "text_id": 182,
    "title": "Good customer service",
    "translation": null,
    "needs_translation": true
}
```

---

## 3. Traducir Títulos en Batch

**Endpoint:** `translate_titles_batch.php`

**Método:** POST

### 3.1 Traducir TODOS los títulos sin traducción

**Parámetro:**
- `action=translate_all`

**Límite:** Se traducen máximo 50 títulos por solicitud

**Ejemplo:**
```javascript
const formData = new FormData();
formData.append('action', 'translate_all');

fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log(data));
```

**Respuesta:**
```json
{
    "success": true,
    "translated": 5,
    "failed": 2,
    "errors": ["Error descripción 1", "Error descripción 2"],
    "message": "Se tradujeron 5 títulos, 2 fallaron"
}
```

### 3.2 Traducir un título específico

**Parámetros:**
- `action=translate_single`
- `text_id` (int) - ID del texto a traducir

**Ejemplo:**
```javascript
const formData = new FormData();
formData.append('action', 'translate_single');
formData.append('text_id', 182);

fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(data => console.log(data));
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "text_id": 182,
    "original": "Good customer service",
    "translation": "Buen servicio al cliente",
    "message": "Título traducido correctamente"
}
```

**Respuesta de error:**
```json
{
    "success": false,
    "error": "No se pudo traducir el título"
}
```

---

## Funciones Reutilizadas

Todos los endpoints usan funciones de `includes/title_functions.php`:

### saveTitleTranslation($text_id, $title, $translation)
Guarda una traducción de título en la BD.

**Parámetros:**
- `$text_id` (int) - ID del texto
- `$title` (string) - Título original
- `$translation` (string) - Traducción

**Retorno:**
```php
[
    'success' => true/false,
    'message' => 'Mensaje de éxito',
    'error' => 'Mensaje de error'
]
```

### getTitleTranslation($text_id)
Obtiene la traducción de un título.

**Parámetros:**
- `$text_id` (int) - ID del texto

**Retorno:**
- `string` - Traducción si existe
- `NULL` - Si no hay traducción

---

## Flow de Traducción Automática

1. **Al subir un texto** (`upload_text.php`):
   - Se inserta el texto en BD
   - Se llama a `translate.php` (DeepL/Google)
   - Se guarda automáticamente la traducción en `title_translation`

2. **Para títulos faltantes** (`translate_titles_batch.php`):
   - Se obtienen textos donde `title_translation IS NULL`
   - Se llama a `translate.php` para cada uno
   - Se guarda la traducción usando `saveTitleTranslation()`

3. **Para mostrar en UI** (`ajax_my_texts_content.php`):
   - Se consulta `title_translation` de la BD
   - Si está NULL, se usa fallback `getTitleTranslation()`
   - Se renderiza en HTML si tiene valor

---

## Seguridad

✅ Todos los endpoints:
- Requieren autenticación (`session_user_id`)
- Validan que el usuario sea propietario o sea texto público
- Escapan el HTML con `htmlspecialchars()`
- Usan prepared statements contra inyección SQL
- Retornan JSON con encriptación UTF-8

---

## Ejemplos de Integración

### Opción 1: Desde JavaScript en la pestaña "Mis Textos"

```javascript
// Botón para traducir títulos faltantes
document.getElementById('translate-all-btn')?.addEventListener('click', async () => {
    const response = await fetch('/traductor/translate_titles_batch.php', {
        method: 'POST',
        body: new FormData(Object.assign(new FormData(), {
            'action': 'translate_all'
        }))
    });
    const data = await response.json();
    if (data.success) {
        alert(`✅ Se tradujeron ${data.translated} títulos`);
        location.reload();
    }
});
```

### Opción 2: Desde PHP (backend)

```php
require_once 'includes/title_functions.php';

// Traducir automáticamente un texto recién creado
$text_id = $conn->insert_id;
$title = 'New Title';

// Llamar a la API de traducción
$translation = callTranslateAPI($title);

// Guardar la traducción
if ($translation) {
    $result = saveTitleTranslation($text_id, $title, $translation);
}
```

### Opción 3: Desde consola del navegador

```javascript
// Traducir un título específico
fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: new URLSearchParams({
        action: 'translate_single',
        text_id: 182
    })
}).then(r => r.json()).then(d => console.log(d));
```

---

## Performance

- **Timeout de API:** 5 segundos
- **Connection timeout:** 3 segundos
- **Batch limit:** 50 textos máximo por solicitud
- **Pausa entre traducciones:** 200ms (para no sobrecargar API)

## Logs

Se registran en el error_log de PHP:

```
[BATCH] Título traducido ID 182: 'Good customer service' -> 'Buen servicio al cliente'
Traducción de título guardada: 'Title' -> 'Título'
Error traduciendo título 'Title': Connection timeout
```

---

## Compatibilidad

✅ Sigue el mismo patrón que:
- `save_content_translation.php`
- `get_content_translation.php`
- `includes/content_functions.php`

✅ Reutiliza:
- `translate.php` (API DeepL/Google)
- `includes/title_functions.php` (funciones de BD)
- `db/connection.php` (conexión MySQLi)
