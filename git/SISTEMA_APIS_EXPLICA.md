# 🔍 Sistema de APIs y Funcionamiento del Botón "Explica"

## 📋 Resumen General

El sistema de LeerEntender utiliza múltiples APIs para proporcionar funcionalidades de traducción y diccionario. El botón "Explica" muestra información detallada de palabras en tiempo real. Para reducir llamadas innecesarias a las APIs, la primera traducción obtenida se guarda en base de datos y se reutiliza en visitas posteriores.

## 🌐 APIs Utilizadas

### 1. Sistema Híbrido de Traducción (`translate.php`)
- Propósito: traducción de textos y palabras individuales.
- Uso:
  - Traducción de contenido durante la lectura.
  - Traducción de títulos.
  - Traducción de palabras, sinónimos, antónimos y ejemplos.
- Endpoint: `translate.php` (POST con parámetros `text` o `word`).
- Funcionamiento:
  1) Intenta DeepL (primera opción).
  2) Si DeepL falla o excede timeout, usa Google Translate como respaldo.
  3) Si ambas fallan, devuelve error controlado.

### 2. API de Diccionario Merriam‑Webster (`diccionario.php`)
- Propósito: información de diccionario para el botón "Explica".
- Fuentes: Diccionario (definiciones, categoría, pronunciación, ejemplos) y Tesauro (sinónimos/antónimos).
- Endpoint: `diccionario.php` (GET con `palabra`).
- Funcionamiento: consulta ambas fuentes, procesa los datos relevantes y devuelve una respuesta unificada. Si no hay datos, devuelve un mensaje informativo.

## 🎯 Funcionamiento del Botón "Explica"
Flujo general:
1) El usuario hace clic en una palabra del texto.
2) La interfaz abre el panel lateral y pide datos a `diccionario.php`.
3) La definición y otros elementos (sinónimos, antónimos, ejemplos) se traducen bajo demanda a través de `translate.php`.
4) Se muestra la información (definición, categoría, pronunciación y audio si existe) sin bloquear la lectura.

## 🧠 Persistencia y caché de traducciones (evitar llamadas repetidas)
El sistema aplica una caché en dos niveles: Base de Datos (persistente) y control en el Frontend (evitar retraducir un elemento ya procesado en la sesión).

1) Títulos de textos
- Campo en BD: `texts.title_translation`.
- Carga: cuando se listan textos, si `title_translation` existe se muestra directamente junto al `title`.
- Primera traducción: si no existe, se solicita a `translate.php` y se guarda en `title_translation` para reutilizarla.
- Resultado: futuras visitas no llaman a la API para ese título.

2) Contenido del texto
- Campo en BD: `texts.content_translation` (formato JSON simple para fragmentos/entradas).
- Carga: la interfaz consulta primero `get_content_translation.php`. Si hay traducción, se usa; si no, se pide a `translate.php` y se guarda con `save_content_translation.php`.
- Control de sesión: los elementos traducidos se marcan para no procesarlos de nuevo durante la misma lectura.
- Resultado: el contenido ya traducido se muestra al instante sin llamar a la API.

3) Palabras guardadas (vocabulario)
- Tabla: `saved_words` (por usuario) con `word`, `translation`, `context` y `text_id`.
- Reutilización: alimenta práctica y vistas, evitando retraducir palabras ya aprendidas.

Beneficios del enfoque
- Menor latencia y mejor UX: traducciones aparecen de inmediato si existen en BD.
- Menor coste: se reducen significativamente las solicitudes a APIs externas.
- Robustez: si una API falla, la caché evita bloquear la lectura.

## 🔄 Integración de Sistemas
Flujo completo (alto nivel):
1) Clic en palabra → se activa el panel "Explica".
2) Consulta a `diccionario.php` → datos de definición/categoría/sinónimos/antónimos/ejemplos/pronunciación.
3) Traducciones bajo demanda de esos textos via `translate.php`.
4) Renderizado en el sidebar con actualizaciones progresivas.

## 🚀 Optimizaciones Implementadas
1) Timeouts cortos en llamadas externas para evitar esperas.
2) Manejo de errores y failover: DeepL → Google Translate.
3) Caché persistente en BD para títulos, contenido y palabras.
4) Control de elementos ya traducidos en el frontend para no repetir trabajo en la sesión.

## 🔧 Configuración de APIs
- No incluir claves en el código ni en esta documentación.
- Definir las credenciales en variables de entorno o archivos privados no versionados y cargarlas en tiempo de ejecución.

---

Última actualización: 17/08/2025
