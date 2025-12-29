# LeerEntender App - Documentación Completa

## 📋 Descripción General

LeerEntender es una aplicación híbrida que funciona tanto en navegador web como en aplicación de escritorio (Electron). Su principal función es ayudar a los usuarios a aprender inglés mediante la lectura de textos con funcionalidades de texto-a-voz (TTS) y traducción automática.

## 🏗️ Arquitectura del Sistema

### Aplicación Web (Navegador)
- **URL**: `https://leerentender.infinityfreeapp.com/traductor/`
- **Tecnologías**: PHP, JavaScript, HTML, CSS
- **Funcionalidades**: Lectura de textos, traducción, práctica, progreso

### Aplicación de Escritorio (Electron)
- **Directorio**: `leerEntenderApp/`
- **Tecnologías**: Electron, Node.js, JavaScript
- **Funcionalidades**: Mismas que la web + funcionalidades offline

## 🎯 Funcionalidades Principales

1. **Sistema de Lectura Inteligente**
   - Texto-a-voz en inglés con ResponsiveVoice
   - Traducción línea por línea
   - Control de velocidad de lectura
   - Navegación por páginas

2. **Sistema de Traducción**
   - Traducción automática de párrafos
   - Caché de traducciones
   - Traducción de palabras individuales

3. **Sistema de Práctica**
   - Ejercicios de selección múltiple
   - Ejercicios de escritura
   - Seguimiento de progreso

4. **Gestión de Contenido**
   - Subida de textos
   - Categorización
   - Historial de lectura

## 🔧 Recursos Externos

### ResponsiveVoice (TTS)
- **API Key**: `wJGiW37b`
- **CDN**: `https://code.responsivevoice.org/responsivevoice.js`
- **Funcionalidad**: Texto-a-voz en inglés con voces de alta calidad

### Google Translate
- **Uso**: Traducción de texto y palabras
- **Integración**: API directa para traducciones

## 📁 Estructura de Archivos

```
app_escritorio/
├── leerEntenderApp/          # Aplicación Electron
│   ├── main.js              # Proceso principal
│   ├── package.json         # Configuración del proyecto
│   └── node_modules/        # Dependencias
└── traductor/               # Aplicación web
    ├── index.php            # Página principal
    ├── js/                  # JavaScript
    │   ├── lector.js        # Lógica de lectura
    │   ├── electron-voice-integration.js # Sistema de voz unificado
    │   └── ...              # Otros módulos
    ├── css/                 # Estilos
    ├── includes/            # Funciones PHP
    └── docs/                # Documentación
```

## 🚀 Cómo Usar

### En Navegador
1. Ir a `https://leerentender.infinityfreeapp.com/traductor/`
2. Seleccionar un texto para leer
3. Usar controles de lectura y traducción

### En Escritorio
1. Navegar a `leerEntenderApp/`
2. Ejecutar `npm install` (primera vez)
3. Ejecutar `npm start`
4. La aplicación se abrirá automáticamente

## 📚 Documentación Detallada

- [Sistema de Voz](SISTEMA_VOZ.md) - ResponsiveVoice y TTS
- [Sistema de Traducción](SISTEMA_TRADUCCION.md) - APIs y caché
- [Funciones de Lectura](FUNCIONES_LECTURA.md) - Lógica de lectura
- [Integración Electron](INTEGRACION_ELECTRON.md) - Web + Escritorio
- [Base de Datos](BASE_DATOS.md) - Estructura y consultas
- [API Endpoints](API_ENDPOINTS.md) - Endpoints disponibles

## 🐛 Solución de Problemas

### Problemas Comunes
1. **Voz no funciona**: Verificar API key de ResponsiveVoice
2. **Traducciones no aparecen**: Verificar conexión a internet
3. **App no arranca**: Ejecutar `npm install` en `leerEntenderApp/`

### Logs de Debug
- **Navegador**: F12 → Console
- **Electron**: Menú → Ver → Toggle Developer Tools

## 🔄 Mantenimiento

### Actualizaciones
- **Web**: Subir archivos al servidor
- **Electron**: Recompilar con `npm run build`

### Dependencias
- **Web**: ResponsiveVoice CDN
- **Electron**: `package.json` + `npm install`

---

**Versión**: 1.0.0  
**Última actualización**: Septiembre 2025  
**Mantenido por**: Equipo de Desarrollo
