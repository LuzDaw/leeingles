# Estructura de Archivos - LeerEntender

## Directorio Raíz
```
📁 traductor/
├── 📁 ajax/                    # AJAX handlers (vacío actualmente)
├── 📁 css/                     # Estilos CSS organizados
├── 📁 db/                      # Configuración de base de datos
├── 📁 docs/                    # Documentación (nuevo)
├── 📁 google_api/              # Integración Google API
├── 📁 img/                     # Imágenes y recursos
├── 📁 js/                      # JavaScript modular
├── 📁 node_modules/            # Dependencias npm (si se usan)
├── 📁 textoPublic/             # Textos públicos de ejemplo
└── 📁 .idea/                   # Configuración IDE
```

## Archivos CSS (css/) - Sistema Modular
```
📁 css/
├── common-styles.css         # Variables CSS y estilos comunes
├── modern-styles.css         # Estilos base modernos
├── color-theme.css           # Sistema de colores
├── mobile-ready.css          # Optimización móvil
├── practice-styles.css       # Sistema de práctica (20KB)
├── header-redesign.css       # Header responsive
├── tab-system.css            # Sistema de pestañas
├── landing-page.css          # Página principal
├── reading-styles.css        # Modo lectura
├── dynamic-styles.css        # Estilos generados por JS
├── floating-menu.css         # Estilos menú flotante
├── modal-styles.css          # Modales del sistema
├── saved-words-styles.css    # Palabras guardadas
├── progress-styles.css       # Sistema de progreso
├── upload-form.css           # Formularios de subida
├── text-styles.css           # Tipografía y textos
├── index-page.css            # Página principal
└── print.css                 # Estilos para impresión
```

**📋 Documentación CSS**: [ESTRUCTURA_CSS.md](./ESTRUCTURA_CSS.md)

## Archivos JavaScript (js/) - Sistema Modular
```
📁 js/
├── common-functions.js       # Utilidades comunes y funciones centralizadas
├── global-state.js           # Estado centralizado de la aplicación
├── practice-functions.js     # Sistema completo de práctica (73KB)
├── lector.js                 # Motor principal de lectura interactiva (40KB)
├── text-management.js        # Gestión de textos y contenido
├── header-functions.js       # Navegación y UI responsiva
├── floating-menu.js          # Menú flotante y controles
├── modal-functions.js        # Sistema de modales de autenticación
├── upload-form.js            # Formulario de subida de archivos
├── public-texts-dropdown.js  # Dropdown de textos públicos
├── fullscreen-fix.js         # Correcciones para modo pantalla completa
├── fullscreen-translation.js # Traducción específica para fullscreen
├── main.js                   # Funciones específicas de la página principal
└── loadUserTexts.js          # Carga de textos del usuario
```

**📋 Documentación JavaScript**: [ESTRUCTURA_JAVASCRIPT.md](./ESTRUCTURA_JAVASCRIPT.md)

## Archivos PHP Principales
```
📁 traductor/
├── index.php                 # Página principal/lector
├── practice.php              # (ELIMINADO - Funcionalidad integrada en pestañas)
├── my_texts.php              # Gestión de textos usuario
├── logueo_seguridad/         # Archivos de logueo y seguridad
│   ├── login.php             # Sistema de login
│   ├── register.php          # Registro de usuarios
│   ├── logout.php            # Cerrar sesión
│   ├── ajax_login.php        # Login asíncrono
│   ├── ajax_register.php     # Registro asíncrono
│   ├── auth_functions.php    # Funciones de autenticación
│   └── login-styles.css      # Estilos de login
├── includes/                 # Funciones comunes
│   ├── word_functions.php    # Funciones de manejo de palabras
│   └── practice_functions.php # Funciones de práctica
├── upload_text.php           # Subida de textos
├── translate.php             # Sistema de traducción
├── save_word.php             # Guardar palabras
├── save_translated_word.php  # Guardar traducciones
├── delete_text.php           # Eliminar textos
```

## Archivos AJAX
```
📁 traductor/
├── ajax_practice_data.php    # Datos para práctica
├── ajax_text_sentences.php   # Frases para ejercicios
├── ajax_upload_text.php      # Subida asíncrona
└── ajax_user_texts.php       # Gestión textos AJAX
```

## Base de Datos (db/)
```
📁 db/
├── connection.php            # Conexión MySQL
├── create_admin_user.php     # Crear usuario admin
├── add_email_column.sql      # Script SQL email
└── add_email_field.sql       # Script SQL campo email
```

## Documentación (docs/)
```
📁 docs/
├── ESQUEMA_APLICACION.md     # Esquema general
├── FUNCIONES_COMUNES.md      # Funciones PHP centralizadas
├── ESTRUCTURA_ARCHIVOS.md    # Este archivo
└── PLAN_REFACTORING.md       # Plan de mejoras
```

## Archivos de Configuración
```
📁 traductor/
├── package.json              # Dependencias npm
├── package-lock.json         # Lock de dependencias
├── MEMORIA_APP.md             # Memoria del desarrollo
├── MOBILE_PREPARATION.md      # Preparación móvil
├── GOOGLE_PLAY_CONFIG.md      # Configuración Google Play
└── resumen_correcciones.md    # Historial de correcciones
```

## Recursos y Assets
```
📁 img/                       # Imágenes
📁 textoPublic/               # Textos de ejemplo
📁 google_api/                # Configuración Google
```

## Archivos de Desarrollo
```
📁 traductor/
├── analisis_problemas.md     # Análisis de problemas
├── translate_debug.log       # Logs de debug
└── saved_words_old.php       # Versión anterior (legacy)
```

## Funcionalidad por Archivo

### Páginas Principales
- **index.php**: Lector principal, traducción interactiva
- **practice.php**: (ELIMINADO - Funcionalidad integrada en pestañas)
- **my_texts.php**: Gestión personal de textos
- **logueo_seguridad/login.php / logueo_seguridad/register.php**: Autenticación

### AJAX Handlers
- **ajax_practice_data.php**: Proporciona palabras para ejercicios
- **ajax_text_sentences.php**: Genera frases para práctica de escritura
- **ajax_user_texts.php**: CRUD de textos del usuario

### JavaScript Modular
- **common-functions.js**: Utilidades comunes centralizadas
- **global-state.js**: Estado centralizado de la aplicación
- **practice-functions.js**: Lógica completa de ejercicios
- **lector.js**: Motor principal de lectura interactiva
- **text-management.js**: Gestión de textos y lectura
- **header-functions.js**: Navegación y UI responsiva
- **floating-menu.js**: Acceso rápido a funcionalidades

### CSS Organizado
- **common-styles.css**: Variables CSS y estilos comunes
- **modern-styles.css**: Base visual
- **practice-styles.css**: Específico para ejercicios
- **mobile-ready.css**: Adaptaciones responsive
- **color-theme.css**: Sistema de colores consistente

## Estado Actual vs Ideal
- ✅ **Bien organizado**: CSS y JS modulares
- ⚠️ **Mejorable**: Algunos archivos PHP en raíz
- ⚠️ **Legacy**: Archivos '_old' pendientes de limpieza
- ✅ **Documentado**: Archivos .md informativos
