# Quick Start - Traducir Títulos Sin Traducción

## Problema
Los títulos de textos antiguos no muestran su traducción porque fueron subidos antes de que se corrigiera el bug en `upload_text.php`.

## Solución Rápida

### Opción A: Desde la Consola del Navegador (Más fácil)

1. Abre la pestaña "Mis Textos"
2. Abre la consola del navegador (F12 → Consola)
3. Copia y ejecuta:

```javascript
// Traducir TODOS los títulos sin traducción
fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: new URLSearchParams({ action: 'translate_all' })
})
.then(r => r.json())
.then(d => alert(`✅ ${d.translated} traducidos, ${d.failed} fallaron`))
.then(() => location.reload());
```

**Esperado:**
```
✅ 5 traducidos, 0 fallaron
[Página se recarga]
```

---

### Opción B: Traducir un Título Específico

```javascript
// Traducir solo el título con ID 182
fetch('/traductor/translate_titles_batch.php', {
    method: 'POST',
    body: new URLSearchParams({ 
        action: 'translate_single',
        text_id: 182
    })
})
.then(r => r.json())
.then(d => {
    if (d.success) {
        alert(`✅ "${d.original}" → "${d.translation}"`);
        location.reload();
    } else {
        alert(`❌ ${d.error}`);
    }
});
```

---

## Archivos Nuevos Creados

| Archivo | Función |
|---------|---------|
| `translate_titles_batch.php` | Traducir títulos en batch (todos o uno) |
| `save_title_translation.php` | Guardar traducción de título (API) |
| `get_title_translation.php` | Obtener traducción de título (API) |
| `upload_text.php` | ✅ CORREGIDO - URL de API dinámica |

---

## Verificación

Después de traducir, abre "Mis Textos" y verifica:

**Antes:**
```
Good customer service
[sin traducción]
```

**Después:**
```
Good customer service
• Buen servicio al cliente
```

---

## Para Futuros Textos

Los textos nuevos que subas **se traducirán automáticamente** porque ya está corregido el bug en `upload_text.php`.

---

## Si algo falla

**Abre la consola (F12) y mira los errores:**

```javascript
// Verificar qué textos necesitan traducción
fetch('/traductor/get_title_translation.php?text_id=182')
    .then(r => r.json())
    .then(d => console.log(d));

// Verifica si la API de traducción funciona
fetch('/traductor/translate.php', {
    method: 'POST',
    body: new URLSearchParams({ word: 'Hello World' })
})
.then(r => r.json())
.then(d => console.log(d));
```

---

## Contacto/Logs

Si la traducción falla:
1. Revisa el error.log de PHP: `C:/xampp/apache/logs/error.log`
2. Busca líneas con `[BATCH]` o `Error traduciendo`
3. El error más común es timeout de API (no hay internet o DeepL está caído)

---

**¡Listo! Ahora puedes traducir todos tus títulos. 🚀**
