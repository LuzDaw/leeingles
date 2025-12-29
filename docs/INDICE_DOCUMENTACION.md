# 📚 Índice de Documentación - LeerEntender App

## 🏠 Documentación Principal

### [README.md](./README.md)
**Descripción**: Documentación general y guía de inicio
**Contenido**:
- Descripción general de la aplicación
- Arquitectura del sistema (Web + Electron)
- Funcionalidades principales
- Recursos externos
- Estructura de archivos
- Guía de uso básica

## 🎤 Sistema de Voz

### [SISTEMA_VOZ.md](./SISTEMA_VOZ.md)
**Descripción**: Documentación completa del sistema de voz ResponsiveVoice
**Contenido**:
- Configuración técnica (API Key, CDN)
- Arquitectura del sistema
- Funciones principales y callbacks
- Integración Web vs Electron
- Flujo de lectura y manejo de errores
- Fallbacks y optimizaciones
- Debugging y solución de problemas

## 🌐 Sistema de Traducción

### [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md)
**Descripción**: Documentación del sistema de traducción con Google Translate
**Contenido**:
- Configuración técnica y proveedores
- Arquitectura del sistema
- Traducción línea por línea
- Sistema de caché inteligente
- Manejo de errores y timeouts
- Optimizaciones y métricas
- Endpoints de API disponibles

## 💻 Integración Electron

### [INTEGRACION_ELECTRON.md](./INTEGRACION_ELECTRON.md)
**Descripción**: Documentación de la aplicación de escritorio Electron
**Contenido**:
- Arquitectura del sistema híbrido
- Configuración del proyecto
- Carga de contenido web
- Sistema de voz unificado
- Comandos de desarrollo
- Empaquetado y distribución
- Debugging y solución de problemas

## 🔧 Documentación Técnica (Existente)

### [ESQUEMA_APLICACION.md](./ESQUEMA_APLICACION.md)
**Descripción**: Visión general y funcionalidades de la aplicación web
**Contenido**:
- Estructura técnica
- Características principales
- Sistema de práctica
- Lectura interactiva

### [ESTRUCTURA_ARCHIVOS.md](./ESTRUCTURA_ARCHIVOS.md)
**Descripción**: Organización detallada de archivos y directorios
**Contenido**:
- Estructura de directorios
- Archivos principales
- Organización de módulos

### [FUNCIONES_COMUNES.md](./FUNCIONES_COMUNES.md)
**Descripción**: Funciones PHP centralizadas y reutilizables
**Contenido**:
- Funciones de autenticación
- Funciones de palabras
- Funciones de práctica

### [ESTRUCTURA_CSS.md](./ESTRUCTURA_CSS.md)
**Descripción**: Documentación completa del sistema CSS
**Contenido**:
- Estilos base y variables
- Estilos por funcionalidad
- Sistema de colores y tema

### [ESTRUCTURA_JAVASCRIPT.md](./ESTRUCTURA_JAVASCRIPT.md)
**Descripción**: Documentación completa del sistema JavaScript
**Contenido**:
- Módulos principales
- Funciones y utilidades
- Patrones de código

### [COMENTARIOS_PRACTICE.md](./COMENTARIOS_PRACTICE.md)
**Descripción**: JSDoc para el sistema de práctica
**Contenido**:
- Funciones de práctica
- Documentación de código
- Ejemplos de uso

### [PLAN_REFACTORING.md](./PLAN_REFACTORING.md)
**Descripción**: Plan de mejoras y limpieza del código
**Contenido**:
- Objetivos de refactoring
- Plan de implementación
- Métricas y seguimiento

## 📋 Guías de Uso

### Para Desarrolladores
1. **Empezar con**: [README.md](./README.md)
2. **Entender voz**: [SISTEMA_VOZ.md](./SISTEMA_VOZ.md)
3. **Entender traducción**: [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md)
4. **Entender Electron**: [INTEGRACION_ELECTRON.md](./INTEGRACION_ELECTRON.md)

### Para Mantenimiento
1. **Estructura**: [ESTRUCTURA_ARCHIVOS.md](./ESTRUCTURA_ARCHIVOS.md)
2. **Funciones**: [FUNCIONES_COMUNES.md](./FUNCIONES_COMUNES.md)
3. **CSS**: [ESTRUCTURA_CSS.md](./ESTRUCTURA_CSS.md)
4. **JavaScript**: [ESTRUCTURA_JAVASCRIPT.md](./ESTRUCTURA_JAVASCRIPT.md)

### Para Testing
1. **Funciones**: [COMENTARIOS_PRACTICE.md](./COMENTARIOS_PRACTICE.md)
2. **Plan**: [PLAN_REFACTORING.md](./PLAN_REFACTORING.md)

## 🚀 Implementación Rápida

### Configuración Inicial
```bash
# 1. Clonar/descargar proyecto
# 2. Navegar a leerEntenderApp/
cd leerEntenderApp

# 3. Instalar dependencias
npm install

# 4. Ejecutar aplicación
npm start
```

### Verificación de Funcionalidades
1. **Voz**: Verificar que ResponsiveVoice funcione
2. **Traducción**: Comprobar traducciones línea por línea
3. **Electron**: Verificar que la app se abra correctamente

## 🔍 Búsqueda en Documentación

### Por Funcionalidad
- **Voz**: [SISTEMA_VOZ.md](./SISTEMA_VOZ.md)
- **Traducción**: [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md)
- **Escritorio**: [INTEGRACION_ELECTRON.md](./INTEGRACION_ELECTRON.md)
- **Web**: [ESQUEMA_APLICACION.md](./ESQUEMA_APLICACION.md)

### Por Archivo
- **main.js**: [INTEGRACION_ELECTRON.md](./INTEGRACION_ELECTRON.md)
- **electron-voice-integration.js**: [SISTEMA_VOZ.md](./SISTEMA_VOZ.md)
- **lector.js**: [SISTEMA_VOZ.md](./SISTEMA_VOZ.md) + [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md)
- **translate.php**: [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md)

### Por Problema
- **App no arranca**: [INTEGRACION_ELECTRON.md](./INTEGRACION_ELECTRON.md#problema-app-no-arranca)
- **Voz no funciona**: [SISTEMA_VOZ.md](./SISTEMA_VOZ.md#problema-voz-no-funciona)
- **Traducciones no aparecen**: [SISTEMA_TRADUCCION.md](./SISTEMA_TRADUCCION.md#problema-traducciones-no-aparecen)

## 📊 Estado de la Documentación

### ✅ Completado
- [x] README principal
- [x] Sistema de voz
- [x] Sistema de traducción
- [x] Integración Electron
- [x] Documentación técnica existente

### 🔄 En Progreso
- [ ] Guías de usuario final
- [ ] Tutoriales paso a paso
- [ ] Videos de demostración

### 📋 Pendiente
- [ ] Documentación de API completa
- [ ] Guías de deployment
- [ ] Documentación de contribución

## 🔗 Enlaces Externos

### Recursos de Desarrollo
- [ResponsiveVoice](https://responsivevoice.org/) - Sistema de voz
- [Google Translate](https://cloud.google.com/translate) - Traducción
- [Electron](https://www.electronjs.org/) - Framework de escritorio

### Documentación de Referencia
- [Electron Documentation](https://www.electronjs.org/docs)
- [electron-builder](https://www.electron.build/)
- [ResponsiveVoice API](https://responsivevoice.org/api/)

---

**Última actualización**: Septiembre 2025  
**Versión de documentación**: 1.0.0  
**Mantenido por**: Equipo de Desarrollo LeerEntender

## 📞 Contacto y Soporte

Para dudas sobre la documentación o problemas técnicos:
- **Issues**: Crear issue en el repositorio
- **Documentación**: Revisar archivos correspondientes
- **Desarrollo**: Seguir guías de implementación
