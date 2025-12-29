# Resumen: Problema de Traducciones de Títulos

## Problema Encontrado

Las traducciones de títulos de textos no están apareciendo cuando se suben nuevos textos.

## Causa Raíz

**Archivo:** `upload_text.php` (línea 61)

URL hardcodeada incorrectamente en la llamada a `translate.php`:

```php
❌ INCORRECTO:
curl_setopt($ch, CURLOPT_URL, 'http://localhost/taller/1traductor/traductor/translate.php');

✅ CORRECTO:
// URL dinámica basada en el servidor actual
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$translate_url = $protocol . '://' . $host . '/traductor/translate.php';
```

## Impacto

- **Textos nuevos:** Las traducciones de títulos fallan silenciosamente (no se captura error)
- **Textos antiguos:** Los que tienen traducción guardada SÍ aparecen
- **Fallback:** La función `getTitleTranslation()` devuelve NULL para títulos sin traducción

## Solución Aplicada

### Cambios en `upload_text.php`

1. **URL dinámica** → Se construye basada en `$_SERVER['HTTP_HOST']` y protocolo
2. **Validación de errores** → Se captura `curl_error()` y se valida antes de processar
3. **Logging mejorado** → Se registran:
   - Traducciones exitosas
   - Traducciones fallidas
   - Errores de conexión

### Código actualizado:

```php
// Construir URL dinámica basada en el servidor actual
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$translate_url = $protocol . '://' . $host . '/traductor/translate.php';

// Llamar a la API de traducción
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $translate_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'word=' . urlencode($title));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response && empty($curl_error)) {
    $translation_data = json_decode($response, true);
    if (isset($translation_data['translation']) && !empty($translation_data['translation'])) {
        // Guardar la traducción en la base de datos
        $update_stmt = $conn->prepare("UPDATE texts SET title_translation = ? WHERE id = ?");
        $update_stmt->bind_param("si", $translation_data['translation'], $text_id);
        $update_stmt->execute();
        $update_stmt->close();
        error_log("Traducción de título guardada: '{$title}' -> '{$translation_data['translation']}'");
    } else {
        error_log("No se obtuvo traducción válida para: '{$title}'");
    }
} else {
    error_log("Error de conexión al traducir título '{$title}': " . ($curl_error ?: "Sin respuesta"));
}
```

## Documentación Completa

Se ha creado `/docs/doc/texto.md` con:

- ✅ Flujo completo de carga de textos
- ✅ Estructura de base de datos
- ✅ Funciones de traducción de títulos
- ✅ API de traducción (DeepL/Google)
- ✅ Tabla de estado de traducciones
- ✅ Recomendaciones para mejorar

## Prueba

Para verificar que funciona:

1. Subir un nuevo texto
2. Revisar los logs de PHP en `apache/logs/error.log` o `apache2/error.log`
3. Abrir pestaña "Mis Textos"
4. Verificar que aparezca la traducción del título

**Ejemplo de log exitoso:**
```
Traducción de título guardada: 'Hello World' -> 'Hola Mundo'
```

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `upload_text.php` | URL dinámica + validación mejorada de errores |
| `docs/doc/texto.md` | Documentación completa del sistema |

## Archivos Relacionados (Sin cambios)

- `translate.php` → API funciona correctamente
- `includes/title_functions.php` → Funciones OK
- `ajax_my_texts_content.php` → Renderizado OK
- `index.php` → Carga de pestañas OK
