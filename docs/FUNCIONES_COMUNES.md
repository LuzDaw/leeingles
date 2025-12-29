# Funciones Comunes - Documentación

## 📁 Estructura de Archivos Comunes

```
traductor/includes/
├── auth_functions.php      # Funciones de autenticación
├── word_functions.php      # Funciones de manejo de palabras
└── practice_functions.php  # Funciones de práctica
```

---

## 🔐 AUTH_FUNCTIONS.PHP

### Funciones de Autenticación

#### `generateCSRFToken()`
**Descripción**: Genera un token CSRF para protección contra ataques.
**Retorna**: `string` - Token CSRF
**Uso**: Para formularios de login/registro

#### `verifyCSRFToken($token)`
**Descripción**: Verifica si un token CSRF es válido.
**Parámetros**: 
- `$token` (string) - Token a verificar
**Retorna**: `bool` - true si es válido
**Uso**: Validación en formularios

#### `authenticateUser($username, $password, $remember_me = false)`
**Descripción**: Autentica un usuario con username/email y contraseña.
**Parámetros**:
- `$username` (string) - Usuario o email
- `$password` (string) - Contraseña
- `$remember_me` (bool) - Mantener sesión (opcional)
**Retorna**: `array` - Resultado de autenticación
```php
[
    'success' => true/false,
    'user_id' => int,
    'username' => string,
    'is_admin' => int,
    'error' => string (si success = false)
]
```

#### `registerUser($username, $email, $password)`
**Descripción**: Registra un nuevo usuario.
**Parámetros**:
- `$username` (string) - Nombre de usuario
- `$email` (string) - Email
- `$password` (string) - Contraseña
**Retorna**: `array` - Resultado del registro
```php
[
    'success' => true/false,
    'user_id' => int,
    'username' => string,
    'error' => string (si success = false)
]
```

#### `isAuthenticated()`
**Descripción**: Verifica si el usuario está autenticado.
**Retorna**: `bool` - true si está autenticado

#### `getCurrentUserId()`
**Descripción**: Obtiene el ID del usuario actual.
**Retorna**: `int|null` - ID del usuario o null

#### `getCurrentUsername()`
**Descripción**: Obtiene el nombre del usuario actual.
**Retorna**: `string|null` - Nombre del usuario o null

#### `isAdmin()`
**Descripción**: Verifica si el usuario actual es administrador.
**Retorna**: `bool` - true si es admin

---

## 📚 WORD_FUNCTIONS.PHP

### Funciones de Manejo de Palabras

#### `saveTranslatedWord($user_id, $word, $translation, $context = '', $text_id = null)`
**Descripción**: Guarda o actualiza una palabra traducida.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$word` (string) - Palabra en inglés
- `$translation` (string) - Traducción al español
- `$context` (string) - Contexto de la palabra (opcional)
- `$text_id` (int) - ID del texto (opcional)
**Retorna**: `array` - Resultado de la operación
```php
[
    'success' => true/false,
    'message' => string,
    'error' => string (si success = false)
]
```

#### `getSavedWords($user_id, $text_id = null, $limit = null)`
**Descripción**: Obtiene las palabras guardadas del usuario.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$text_id` (int) - ID del texto específico (opcional)
- `$limit` (int) - Límite de resultados (opcional)
**Retorna**: `array` - Lista de palabras guardadas
```php
[
    [
        'word' => string,
        'translation' => string,
        'context' => string,
        'text_id' => int,
        'text_title' => string,
        'created_at' => string
    ],
    // ...
]
```

#### `countSavedWords($user_id, $text_id = null)`
**Descripción**: Cuenta las palabras guardadas del usuario.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$text_id` (int) - ID del texto específico (opcional)
**Retorna**: `int` - Número de palabras

#### `getWordStatsByDate($user_id, $days = 7)`
**Descripción**: Obtiene estadísticas de palabras por fecha.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$days` (int) - Número de días hacia atrás (por defecto 7)
**Retorna**: `array` - Estadísticas por fecha
```php
[
    [
        'date' => string (YYYY-MM-DD),
        'count' => int
    ],
    // ...
]
```

#### `getRandomWordsForPractice($user_id, $limit = 10)`
**Descripción**: Obtiene palabras aleatorias para práctica.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$limit` (int) - Número de palabras (por defecto 10)
**Retorna**: `array` - Lista de palabras aleatorias
```php
[
    [
        'word' => string,
        'translation' => string,
        'context' => string
    ],
    // ...
]
```

---

## 🎯 PRACTICE_FUNCTIONS.PHP

### Funciones de Práctica

#### `savePracticeProgress($user_id, $mode, $total_words, $correct_answers, $incorrect_answers, $text_id = null)`
**Descripción**: Guarda el progreso de una sesión de práctica.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$mode` (string) - Modo de práctica ('selection', 'writing', 'sentences')
- `$total_words` (int) - Total de palabras practicadas
- `$correct_answers` (int) - Respuestas correctas
- `$incorrect_answers` (int) - Respuestas incorrectas
- `$text_id` (int) - ID del texto (opcional)
**Retorna**: `array` - Resultado de la operación
```php
[
    'success' => true/false,
    'message' => string,
    'accuracy' => float,
    'error' => string (si success = false)
]
```

#### `savePracticeTime($user_id, $mode, $duration)`
**Descripción**: Guarda el tiempo de práctica.
**Parámetros**:
- `$user_id` (int) - ID del usuario
- `$mode` (string) - Modo de práctica
- `$duration` (int) - Duración en segundos
**Retorna**: `array` - Resultado de la operación
```php
[
    'success' => true/false,
    'error' => string (si success = false)
]
```

#### `getPracticeStats($user_id)`
**Descripción**: Obtiene estadísticas de práctica del usuario.
**Parámetros**:
- `$user_id` (int) - ID del usuario
**Retorna**: `array` - Estadísticas de práctica
```php
[
    'selection' => [
        'count' => int,
        'accuracy' => float
    ],
    'writing' => [
        'count' => int,
        'accuracy' => float
    ],
    'sentences' => [
        'count' => int,
        'accuracy' => float
    ],
    'total_exercises' => int
]
```

#### `getReadingProgress($user_id)`
**Descripción**: Obtiene el progreso completo de lectura del usuario.
**Parámetros**:
- `$user_id` (int) - ID del usuario
**Retorna**: `array` - Progreso completo
```php
[
    'total_words' => int,
    'recent_words' => [
        [
            'word' => string,
            'translation' => string,
            'created_at' => string
        ],
        // ...
    ],
    'total_texts' => int,
    'recent_texts' => [
        [
            'title' => string,
            'created_at' => string
        ],
        // ...
    ],
    'practice' => array // Resultado de getPracticeStats()
]
```

---

## 🔄 Cómo Usar las Funciones Comunes

### 1. Incluir los archivos
```php
require_once 'includes/auth_functions.php';
require_once 'includes/word_functions.php';
require_once 'includes/practice_functions.php';
```

### 2. Ejemplo de uso en login
```php
// Antes (código duplicado)
$stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
// ... código duplicado ...

// Ahora (usando función común)
$result = authenticateUser($username, $password);
if ($result['success']) {
    // Usuario autenticado
} else {
    // Error de autenticación
    $error = $result['error'];
}
```

### 3. Ejemplo de uso para palabras
```php
// Antes (código duplicado)
$stmt = $conn->prepare("INSERT INTO saved_words (user_id, word, translation, context) VALUES (?, ?, ?, ?)");
// ... código duplicado ...

// Ahora (usando función común)
$result = saveTranslatedWord($user_id, $word, $translation, $context, $text_id);
if ($result['success']) {
    // Palabra guardada
} else {
    // Error al guardar
    $error = $result['error'];
}
```

---

## 📊 Beneficios de la Reorganización

1. **✅ Eliminación de Duplicados**: Código reutilizable centralizado
2. **✅ Mantenimiento Más Fácil**: Cambios en un solo lugar
3. **✅ Código Más Limpio**: Archivos principales más pequeños
4. **✅ Mejor Organización**: Estructura clara y lógica
5. **✅ Consistencia**: Misma lógica en toda la aplicación
6. **✅ Testing Más Fácil**: Funciones aisladas y reutilizables

---

## 🔧 Archivos Eliminados en la Reorganización

- `simple_register.php` - Versión simplificada redundante
- `ajax/load_user_texts.php` - Duplicado de ajax_user_texts.php
- `create_test_data.php` - Archivo de prueba
- `test_public_texts_practice.php` - Archivo de prueba
- `test-header.html` - Archivo de prueba
- `docs/FUNCIONES_COMUNES.md` - Documentación actualizada

---

## 📝 Notas Importantes

- **No eliminar archivos existentes** que puedan romper la aplicación
- Las funciones comunes están en `includes/` para fácil acceso
- Todas las funciones incluyen manejo de errores
- Las consultas SQL están optimizadas y preparadas
- Se mantiene compatibilidad con el código existente 