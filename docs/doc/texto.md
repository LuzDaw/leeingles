# Sistema de Textos - Documentación Completa

## 1. Flujo General de Carga de Textos

### Cuando un usuario abre la pestaña "Mis Textos"

```
index.php → loadTabContent('my-texts') → ajax_my_texts_content.php
```

**Archivo:** `index.php` (línea 549-616)
- La función `loadTabContent(tab)` es el punto de entrada
- Realiza una petición FETCH a `ajax_my_texts_content.php`
- El contenido se carga dinámicamente en `#tab-content`

---

## 2. Carga de Textos Propios (ajax_my_texts_content.php)

### Base de Datos

**Tabla:** `texts`
```sql
- id (INT, primary key)
- user_id (INT, foreign key)
- title (VARCHAR)
- title_translation (VARCHAR) -- Traducción del título
- content (TEXT)
- is_public (BOOLEAN)
- category_id (INT, nullable)
- created_at (TIMESTAMP)
```

### Proceso de Carga

1. **Consulta SQL Principal** (línea 98)
```php
SELECT id, title, title_translation, content, is_public 
FROM texts 
WHERE user_id = ? 
AND (is_public = 0 OR id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)) 
ORDER BY created_at DESC
```

2. **Renderizado de Títulos** (línea 184-196)
```php
// Muestra el título en inglés
echo '<span class="title-english">' . htmlspecialchars($row['title']) . '</span>';

// Obtiene la traducción del título
$titleTranslation = $row['title_translation'];

// Fallback: si la traducción no viene en la consulta, usa getTitleTranslation()
if (empty($titleTranslation)) {
    $titleTranslation = getTitleTranslation((int)$row['id']);
}

// Muestra la traducción en español
if (!empty($titleTranslation)) {
    echo '<span class="title-spanish">• ' . htmlspecialchars($titleTranslation) . '</span>';
}
```

3. **Información Adicional Mostrada**
   - Número de palabras
   - Estado de lectura (barra de progreso o "Leído")
   - Estado de privacidad (Público/Privado)

---

## 3. Carga de Textos Públicos Leídos (línea 142-267)

### Textos Públicos Leídos por el Usuario

**Consulta SQL** (línea 142-150)
```php
SELECT t.id, t.title, t.title_translation, t.content, t.user_id, t.is_public, 
       rp.percent, rp.read_count
FROM texts t
INNER JOIN reading_progress rp ON rp.text_id = t.id
WHERE rp.user_id = ? 
AND t.is_public = 1 
AND t.user_id != ? 
AND (rp.percent > 0 OR rp.read_count > 0)
AND t.id NOT IN (SELECT text_id FROM hidden_texts WHERE user_id = ?)
GROUP BY t.id
ORDER BY rp.updated_at DESC, t.title ASC
```

### Información Mostrada
- Título en inglés
- Traducción del título (si existe)
- Número de palabras
- Porcentaje de lectura
- Contador de veces leído (si > 1)
- Estado "Leído" (si progreso >= 100%)

---

## 4. Traducción de Títulos

### 4.1 Durante la Carga de Texto (upload_text.php)

Cuando se sube un nuevo texto:

1. **Se inserta el texto en BD** (línea 44-51)
```php
INSERT INTO texts (user_id, title, content, category_id, is_public) 
VALUES (?, ?, ?, ?, ?)
```

2. **Se traduce automáticamente el título** (línea 56-85)
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/taller/1traductor/traductor/translate.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'word=' . urlencode($title));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$translation_data = json_decode($response, true);

// Guardar traducción en BD
UPDATE texts SET title_translation = ? WHERE id = ?
```

### 4.2 Obtención de Traducción (includes/title_functions.php)

**Función:** `getTitleTranslation($text_id)` (línea 42-63)
```php
SELECT title_translation FROM texts WHERE id = ?
```

---

## 5. Funciones de Manejo de Títulos

**Archivo:** `includes/title_functions.php`

### Funciones Disponibles

1. **saveTitleTranslation($text_id, $title, $translation)**
   - Guarda o actualiza la traducción de un título
   - Retorna array con estado success/error

2. **getTitleTranslation($text_id)**
   - Obtiene la traducción de un título específico
   - Retorna NULL si no existe

3. **getTextsWithTranslations($user_id = null, $limit = null)**
   - Obtiene múltiples textos con sus traducciones
   - Parámetro $user_id: filtra por usuario
   - Parámetro $limit: limita resultados

4. **needsTitleTranslation($text_id)**
   - Verifica si un título necesita traducción
   - Retorna true si está vacía

5. **getTitleTranslationStats($user_id = null)**
   - Estadísticas de traducción de títulos
   - Total de textos, textos traducidos, porcentaje

---

## 6. API de Traducción (translate.php)

### Servicio Híbrido

**Orden de intentos:**
1. DeepL API (principal)
2. Google Translate (fallback)

### Uso

**POST Request:**
```php
$_POST['text'] o $_POST['word']
```

**Response:**
```json
{
    "translation": "Texto traducido",
    "source": "DeepL" o "Google Translate",
    "original": "Texto original",
    "detected_language": "en" o "es"
}
```

### Detección de Idioma

- Si contiene caracteres españoles (áéíóúñ) → Traduce a EN
- Por defecto → Traduce a ES

---

## 7. Funciones Auxiliares

### getTitleTranslation() - Fallback en Varias Partes

Se usa como fallback en:

1. **ajax_my_texts_content.php** (línea 190)
   - Para textos propios del usuario

2. **ajax_my_texts_content.php** (línea 250)
   - Para textos públicos leídos

3. **index.php** (línea 15, 80)
   - En consultas iniciales de textos públicos

---

## 8. Estructura de Datos Retornada

### Al cargar la pestaña "Mis Textos"

El objeto `$row` contiene:
```php
[
    'id' => int,
    'title' => string,                  // Título en inglés
    'title_translation' => string|null, // Traducción en español
    'content' => string,                // Contenido del texto
    'is_public' => bool                 // 0 = privado, 1 = público
]
```

### Para textos públicos leídos adicionales:
```php
[
    ...anteriores...
    'user_id' => int,           // ID del autor
    'percent' => int,           // Porcentaje de lectura
    'read_count' => int         // Veces que fue leído
]
```

---

## 9. Flujo Completo de Ejemplo

```
Usuario logueado abre "Mis Textos"
    ↓
index.php loadTabContent('my-texts')
    ↓
FETCH ajax_my_texts_content.php
    ↓
Consulta textos propios (WHERE user_id = ?)
    ↓
Itera sobre cada texto:
    - Obtiene id, title, title_translation, content, is_public
    - Si title_translation está vacío, llama getTitleTranslation()
    - Renderiza título en inglés
    - Renderiza traducción en español (si existe)
    - Calcula palabras del contenido
    - Obtiene progreso de lectura
    ↓
Consulta textos públicos leídos
    ↓
Itera sobre textos públicos:
    - Obtiene id, title, title_translation, percent, read_count
    - Similar al proceso anterior pero con información de lectura
    ↓
HTML renderizado se inyecta en #tab-content
```

---

## 10. Problema Identificado: Las Traducciones de Títulos No Aparecen

### Causa Principal

**En `upload_text.php` línea 61:**
```php
curl_setopt($ch, CURLOPT_URL, 'http://localhost/taller/1traductor/traductor/translate.php');
```

**El problema:**
- La URL está hardcodeada con una ruta de desarrollo incorrecta
- Debe ser: `'http://localhost/traductor/translate.php'` (en XAMPP)
- O usar: `'http://' . $_SERVER['HTTP_HOST'] . '/traductor/translate.php'` (dinámico)

### Impacto

- Las nuevas traducciones de títulos NO se guardan al subir textos
- Títulos subidos antiguamente SÍ aparecen si tienen traducción guardada
- Fallback `getTitleTranslation()` devuelve NULL para títulos sin traducción

### Solución

Corregir la URL en `upload_text.php`:

```php
// Opción 1: URL dinámica (recomendado)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/traductor/translate.php';

// Opción 2: URL relativa (más simple)
$url = 'translate.php';

curl_setopt($ch, CURLOPT_URL, $url);
```

---

## 11. Archivos Relacionados

| Archivo | Función |
|---------|---------|
| `index.php` | Página principal, carga pestañas |
| `ajax_my_texts_content.php` | Renderiza lista de textos |
| `upload_text.php` | Sube nuevos textos |
| `translate.php` | API de traducción (DeepL/Google) |
| `includes/title_functions.php` | Funciones de gestión de títulos |
| `db/connection.php` | Conexión a base de datos |
| `js/` | JavaScript para interactividad de pestañas |

---

## 12. Tabla de Estado de Traducción

| Situación | Traducción Visible | Causa |
|-----------|-------------------|-------|
| Texto nuevo subido (título) | NO | URL incorrecta en upload_text.php |
| Texto con traducción previa | SÍ | Guardada en BD |
| Texto público leído | SÍ (si existe) | Consulta incluye title_translation |
| Fallback getTitleTranslation() | Intenta recuperar de BD | Último recurso |

---

## 13. Recomendaciones

1. **Corregir URL de traducción** en `upload_text.php`
2. **Hacer URL dinámica** en lugar de hardcodeada
3. **Agregar logs** en translate.php para debuggear fallos
4. **Validar respuesta** de curl antes de json_decode
5. **Implementar retry** si falla la traducción
6. **Cache de traducciones** para no traducir dos veces el mismo texto
