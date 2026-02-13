# Memoria de Refactorización Estructural — LeeIngles

**Fecha de cierre:** 12 de febrero de 2026  
**Objetivo General:**  
Reducir deuda técnica eliminando código muerto, duplicaciones y centralizando lógica (BD, API, sesiones), mejorando mantenibilidad y asegurando portabilidad mediante `.env`.

**Restricción:**  
No se introducirán nuevas funcionalidades ni se cambiará el comportamiento actual.

---

## FASE 1 — AUDITORÍA TÉCNICA

**Acciones:**
- Detectar funciones, archivos, includes y variables no utilizados.
- Identificar duplicaciones en:
  - Conexión a base de datos
  - Inicialización de sesiones
  - Llamadas a API externa
  - Validaciones repetidas
  - Manejo de errores
  - Consultas SQL repetidas
  - Sanitización de inputs

**Resultado esperado:**
- Lista de código muerto.
- Lista de bloques duplicados, clasificados por tipo.

---

## FASE 2 — DISEÑO DE NUEVA ARQUITECTURA

**Propuesta de estructura mínima:**
- `/includes`: utilidades y lógica común (`Database.php`, `Env.php`, `Session.php`, `Helpers.php`)
- `/services`: lógica de negocio y acceso a APIs externas (`TranslationService.php`, `UserService.php`)
- `/repositories`: acceso a datos (`UserRepository.php`, `TranslationRepository.php`)

**Reglas:**
- Ninguna vista debe contener SQL ni llamar directamente a APIs externas.
- Toda llamada externa pasa por un Service.
- Todo acceso a BD pasa por un Repository.

---

## FASE 3 — CENTRALIZACIÓN

- Un único punto de conexión a base de datos (PDO).
- Centralizar cliente de API en `TranslationService`.
- Centralizar validaciones de sesión y autenticación.

---

## FASE 4 — LIMPIEZA Y ESTANDARIZACIÓN

- Unificar estilo y nombres.
- Eliminar comentarios obsoletos y debug temporal.
- Revisar y limpiar includes/requires redundantes.

---

## FASE 5 — OPTIMIZACIÓN

- Detectar y evitar N+1 queries.
- Proponer y documentar índices.
- Evitar `SELECT *` innecesarios.
- Revisar duplicación de CSS/JS y buenas prácticas de caché.

---

## FASE 6 — SEGURIDAD Y ENTORNO

- Todas las credenciales y rutas sensibles desde `.env`.
- Ningún secreto hardcodeado.
- Validación y sanitización consistente.

---

## FASE 7 — VERIFICACIÓN

- Pruebas manuales de todos los flujos críticos.
- Confirmar funcionamiento en local y producción.
- Logs sin errores.

---

## METODOLOGÍA

- Refactorización incremental, archivo por archivo.
- No reescritura completa.
- Cada fase documentada y validada antes de avanzar.

---

**Próximo paso:**  
Analizar el código real que se pase y proponer un plan detallado adaptado antes de modificar nada.
