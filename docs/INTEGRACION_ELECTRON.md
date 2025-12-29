# Integración Electron - Web + Escritorio

## 💻 Descripción General

LeerEntender utiliza **Electron** para crear una aplicación de escritorio que carga la aplicación web existente. Esta arquitectura híbrida permite que la misma aplicación funcione tanto en navegador como en escritorio, manteniendo la consistencia de funcionalidades.

## 🏗️ Arquitectura del Sistema

### Estructura de Directorios
```
app_escritorio/
├── leerEntenderApp/          # Aplicación Electron
│   ├── main.js              # Proceso principal
│   ├── package.json         # Configuración del proyecto
│   ├── node_modules/        # Dependencias
│   └── dist/                # Aplicación empaquetada
└── traductor/               # Aplicación web (existente)
    ├── index.php            # Página principal
    ├── js/                  # JavaScript
    └── css/                 # Estilos
```

### Proceso Principal (main.js)
```javascript
const { app, BrowserWindow } = require('electron');

// Permitir reproducción automática de audio
app.commandLine.appendSwitch('autoplay-policy', 'no-user-gesture-required');

function createWindow() {
    const win = new BrowserWindow({
        width: 1200,
        height: 800,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            webSecurity: true,
        }
    });

    // Cargar la web en producción
    win.loadURL("https://leerentender.infinityfreeapp.com/traductor/?i=1");
}

app.whenReady().then(createWindow);
```

## 🔧 Configuración del Proyecto

### package.json
```json
{
  "name": "leerentenderapp",
  "version": "1.0.0",
  "main": "main.js",
  "scripts": {
    "start": "electron .",
    "build": "electron-builder",
    "build:win": "electron-builder --win",
    "build:mac": "electron-builder --mac",
    "build:linux": "electron-builder --linux"
  },
  "devDependencies": {
    "electron": "^37.4.0",
    "electron-builder": "^24.13.3"
  },
  "build": {
    "appId": "com.leerentender.app",
    "productName": "LeerEntender App",
    "directories": {
      "output": "dist"
    },
    "files": [
      "main.js"
    ]
  }
}
```

## 🌐 Carga de Contenido Web

### Estrategia de Carga
1. **URL de Producción**: `https://leerentender.infinityfreeapp.com/traductor/`
2. **Parámetro de Identificación**: `?i=1` para distinguir app de escritorio
3. **Carga Síncrona**: La web se carga completamente antes de mostrar la ventana

### Configuración de Seguridad
```javascript
webPreferences: {
    nodeIntegration: false,        // No acceso a Node.js desde web
    contextIsolation: true,        // Aislamiento de contexto
    webSecurity: true,             // Seguridad web habilitada
}
```

## 🎤 Sistema de Voz Unificado

### ResponsiveVoice en Electron
- **Mismo CDN**: ResponsiveVoice se carga desde CDN
- **Funcionalidad Idéntica**: Mismas funciones que en web
- **API Unificada**: `electron-voice-integration.js` funciona igual

### Ventajas de Electron
- **Autoplay**: Permite reproducción automática sin gesto del usuario
- **Mejor Rendimiento**: Optimizaciones del motor Chromium
- **Acceso a Hardware**: Mejor control de audio

## 🔄 Flujo de Funcionamiento

### 1. Inicio de la Aplicación
```
npm start → Electron inicia → 
Crea ventana → Carga URL web → 
Web se inicializa → ResponsiveVoice se carga → 
Aplicación lista para usar
```

### 2. Funcionamiento Normal
```
Usuario interactúa → JavaScript web se ejecuta → 
ResponsiveVoice funciona → Traducciones aparecen → 
Experiencia idéntica a navegador
```

### 3. Cierre de Aplicación
```
Usuario cierra ventana → Electron termina proceso → 
Aplicación se cierra completamente
```

## 🚀 Comandos de Desarrollo

### Instalación
```bash
cd leerEntenderApp
npm install
```

### Ejecución en Desarrollo
```bash
npm start
```

### Empaquetado para Distribución
```bash
# Windows
npm run build:win

# macOS
npm run build:mac

# Linux
npm run build:linux

# Todas las plataformas
npm run build
```

## 📦 Empaquetado y Distribución

### electron-builder
- **Configuración**: Definida en `package.json`
- **Output**: Carpeta `dist/` con ejecutables
- **Plataformas**: Windows, macOS, Linux

### Archivos Incluidos
- `main.js` - Proceso principal
- Dependencias de `node_modules/`
- Configuración de build

### Archivos Excluidos
- Código fuente de la web (ya está en servidor)
- Archivos de desarrollo
- Documentación

## 🔍 Debugging y Desarrollo

### DevTools en Electron
```javascript
// Abrir DevTools automáticamente
win.webContents.openDevTools();
```

### Logs del Proceso Principal
```javascript
// Logs en consola de terminal
console.log('Página cargada. Inyectando scripts...');
```

### Debugging de la Web
- **F12**: Abrir DevTools de la web
- **Console**: Ver logs de JavaScript
- **Network**: Monitorear requests

## 🐛 Solución de Problemas

### Problema: App no arranca
**Solución**:
1. Verificar que estás en `leerEntenderApp/`
2. Ejecutar `npm install`
3. Verificar `package.json` existe
4. Ejecutar `npm start`

### Problema: Web no carga
**Solución**:
1. Verificar conexión a internet
2. Comprobar URL en `main.js`
3. Verificar logs en consola
4. Comprobar firewall/antivirus

### Problema: Voz no funciona
**Solución**:
1. Verificar API key de ResponsiveVoice
2. Comprobar consola de DevTools
3. Verificar permisos de audio
4. Reiniciar aplicación

## 📊 Ventajas de la Arquitectura Híbrida

### 1. Desarrollo Unificado
- **Mismo código**: Web y escritorio comparten lógica
- **Mantenimiento**: Solo un código base
- **Consistencia**: Funcionalidades idénticas

### 2. Experiencia de Usuario
- **Familiar**: Misma interfaz que en web
- **Offline**: Funciona sin conexión (después de carga)
- **Nativo**: Se siente como aplicación de escritorio

### 3. Distribución
- **Instalable**: Se instala como app nativa
- **Actualizable**: Fácil distribución de actualizaciones
- **Multiplataforma**: Windows, macOS, Linux

## 🔮 Futuras Mejoras

### 1. Funcionalidades Offline
- **Caché local**: Almacenar textos offline
- **Sincronización**: Sincronizar cuando hay conexión
- **Base de datos local**: SQLite para datos offline

### 2. Integración del Sistema
- **Notificaciones**: Notificaciones del sistema
- **Accesos directos**: Atajos de teclado globales
- **Tray**: Icono en bandeja del sistema

### 3. Actualizaciones Automáticas
- **Auto-updater**: Descargar actualizaciones automáticamente
- **Rollback**: Volver a versión anterior si hay problemas
- **Canales**: Versiones beta y estables

## 📋 Checklist de Implementación

### ✅ Completado
- [x] Configuración básica de Electron
- [x] Carga de aplicación web
- [x] Sistema de voz ResponsiveVoice
- [x] Empaquetado con electron-builder
- [x] Configuración de seguridad

### 🔄 En Progreso
- [ ] Testing en diferentes plataformas
- [ ] Optimización de rendimiento
- [ ] Documentación de usuario

### 📋 Pendiente
- [ ] Funcionalidades offline
- [ ] Integración del sistema
- [ ] Actualizaciones automáticas

## 🔗 Enlaces Útiles

### Documentación Electron
- [Electron Documentation](https://www.electronjs.org/docs)
- [electron-builder](https://www.electron.build/)
- [Security Best Practices](https://www.electronjs.org/docs/tutorial/security)

### Recursos de Desarrollo
- [ResponsiveVoice](https://responsivevoice.org/)
- [Google Translate API](https://cloud.google.com/translate)

---

**Archivo principal**: `leerEntenderApp/main.js`  
**Última actualización**: Septiembre 2025  
**Mantenido por**: Sistema de Integración Electron
