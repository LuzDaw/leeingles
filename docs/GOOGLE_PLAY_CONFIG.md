# Configuración para Google Play Store - LeerEntender

## 1. Convertir a PWA (Progressive Web App)

### Crear manifest.json
```json
{
  "name": "LeerEntender - Leé en inglés y comprendé en español",
  "short_name": "LeerEntender",
  "description": "Leé en inglés y comprendé en español al instante",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#ca123b",
  "orientation": "portrait",
  "icons": [
    {
      "src": "icons/icon-72x72.png",
      "sizes": "72x72",
      "type": "image/png"
    },
    {
      "src": "icons/icon-96x96.png",
      "sizes": "96x96",
      "type": "image/png"
    },
    {
      "src": "icons/icon-128x128.png",
      "sizes": "128x128",
      "type": "image/png"
    },
    {
      "src": "icons/icon-144x144.png",
      "sizes": "144x144",
      "type": "image/png"
    },
    {
      "src": "icons/icon-152x152.png",
      "sizes": "152x152",
      "type": "image/png"
    },
    {
      "src": "icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "icons/icon-384x384.png",
      "sizes": "384x384",
      "type": "image/png"
    },
    {
      "src": "icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

### Crear Service Worker (sw.js)
```javascript
const CACHE_NAME = 'leer-entender-v1';
const urlsToCache = [
  '/',
  '/css/header-redesign.css',
  '/js/lector.js',
  '/js/fullscreen-translation.js',
  '/js/floating-menu.js',
  '/js/modal-functions.js'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});
```

## 2. Crear iconos necesarios

Necesitas iconos en formato PNG en estos tamaños:
- 72x72px
- 96x96px  
- 128x128px
- 144x144px
- 152x152px
- 192x192px
- 384x384px
- 512x512px

## 3. Configurar hosting HTTPS

Google Play Store requiere HTTPS. Opciones:
- **Netlify** (recomendado): Sube tu carpeta `traductor/`
- **Vercel**: Conecta tu repositorio GitHub
- **Firebase Hosting**: Para PHP necesitarás reestructurar

## 4. Herramientas para generar APK

### Opción 1: PWA Builder (Microsoft)
1. Ve a https://pwabuilder.com
2. Ingresa tu URL HTTPS
3. Descarga el APK generado
4. Sube a Google Play Console

### Opción 2: TWA (Trusted Web Activities)
1. Usa Android Studio
2. Crea un proyecto TWA
3. Configura la URL de tu PWA
4. Genera APK firmado

### Opción 3: Capacitor (Ionic)
```bash
npm install @capacitor/core @capacitor/cli
npx cap init
npx cap add android
npx cap build android
```

## 5. Configurar Google Play Console

1. **Crea cuenta de desarrollador** ($25 único pago)
2. **Sube tu APK** 
3. **Completa información**:
   - Nombre: "LeerEntender"
   - Descripción corta: "Leé en inglés y comprendé en español al instante"
   - Categoría: "Educación"
   - Edad: "Todos"

4. **Screenshots necesarios**:
   - Teléfono: 2-8 capturas (1080x1920px)
   - Tablet 7": 1-8 capturas (1200x1920px)
   - Tablet 10": 1-8 capturas (1920x1200px)

5. **Política de privacidad** (obligatoria)

## 6. Configuraciones adicionales

### Agregar meta tags en index.php:
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#ca123b">
<link rel="manifest" href="manifest.json">
<link rel="icon" sizes="192x192" href="icons/icon-192x192.png">
```

### Registrar Service Worker:
```javascript
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}
```

## 7. Pasos recomendados

1. **Primero**: Convierte a PWA y prueba localmente
2. **Segundo**: Sube a hosting HTTPS (Netlify)
3. **Tercero**: Usa PWA Builder para generar APK
4. **Cuarto**: Sube a Google Play Console
5. **Quinto**: Completa la información y publica

## 8. Costos estimados

- Google Play Developer Account: $25 (una vez)
- Hosting: $0 (Netlify gratis) 
- Iconos/diseño: $0-50 (opcional)

**Total mínimo: $25**
