# Esquema de la Aplicación - LeerEntender

## Descripción General
Aplicación web para aprender inglés mediante lectura y práctica de vocabulario.

## Funcionalidades Principales

### 1. Gestión de Textos
- **Subir textos**: Los usuarios pueden subir textos en inglés
- **Mis textos**: Visualizar y gestionar textos subidos
- **Eliminar textos**: Borrar textos no deseados
- **Categorización**: Organizar textos por categorías

### 2. Lectura Interactiva
- **Traductor inline**: Hacer clic en palabras para ver traducción
- **Modo fullscreen**: Lectura a pantalla completa
- **Guardado de palabras**: Guardar vocabulario para práctica posterior
- **Navegación fluida**: Entre diferentes textos

### 3. Sistema de Práctica
- **Selección múltiple**: Elegir la traducción correcta de palabras
- **Escribir palabra**: Completar frases escribiendo la palabra faltante
- **Escribir frases**: Traducir frases completas del español al inglés
- **Sistema de hints**: Ayudas automáticas después de errores
- **Progreso y estadísticas**: Seguimiento del aprendizaje

### 4. Gestión de Usuario
- **Registro/Login**: Sistema de autenticación
- **Panel de admin**: Gestión administrativa
- **Progreso personal**: Seguimiento individual

## Estructura Técnica

### Frontend
- **HTML/CSS/JavaScript** puro (sin frameworks)
- **Diseño responsive** para móviles y desktop
- **Interfaz moderna** con componentes modulares

### Backend
- **PHP** con MySQL
- **AJAX** para comunicación asíncrona
- **Google Translate API** para traducciones

### Base de Datos
- **Textos**: Almacena contenido subido
- **Usuarios**: Gestión de cuentas
- **Palabras guardadas**: Vocabulario para práctica
- **Progreso**: Estadísticas de aprendizaje

## Flujo de Usuario

1. **Registro/Login** → Acceso a la aplicación
2. **Subir texto** → Añadir contenido para leer
3. **Leer y traducir** → Interactuar con el texto, guardar palabras
4. **Practicar** → Ejercicios con vocabulario guardado
5. **Progreso** → Seguimiento del aprendizaje

## Características Especiales
- **Sin frameworks**: Código vanilla para máximo control
- **Modo offline parcial**: Funcionalidades básicas sin conexión
- **Adaptable**: Diseño que funciona en cualquier dispositivo
- **Pedagógico**: Enfoque en el aprendizaje efectivo del inglés
