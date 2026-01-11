# Documentación de Pruebas de Pago - LeeIngles

Este documento detalla el funcionamiento del sistema de pruebas para los pagos y suscripciones en el directorio `dePago`.

## 1. Flujo de Pruebas de PayPal (Sandbox)

El sistema utiliza el entorno Sandbox de PayPal para simular transacciones reales sin dinero real.

### Archivos Principales de Prueba
- **`dePago/test/paypal_sandbox_test.php`**: Interfaz visual que carga el SDK de PayPal. Permite realizar un pago de prueba de 10.00€ para verificar la integración del botón y la captura del pedido.
- **`dePago/test/webhook_handler.php`**: Actúa como el receptor de notificaciones de PayPal (Webhooks) y proporciona una interfaz de administración para forzar estados de prueba.
- **`dePago/ajax_confirm_payment.php`**: Endpoint que recibe la confirmación inmediata desde el frontend tras un pago exitoso en el SDK.

## 2. Funciones de Control de Suscripción

Las funciones principales se encuentran en `dePago/subscription_functions.php`:

- **`getUserSubscriptionStatus($user_id)`**: 
    - Es la función central que determina el estado del usuario.
    - Calcula días desde el registro.
    - Verifica si existe una suscripción activa en la tabla `user_subscriptions`.
    - Actualiza automáticamente a `limitado` si la suscripción ha expirado.
- **`checkTranslationLimit($user_id)`**:
    - Verifica si un usuario `limitado` ha superado las 300 palabras semanales.
    - Los usuarios `EnPrueba` o Premium no tienen este límite.
- **`handleSaleCompleted($resource, $conn)`** (en `webhook_handler.php`):
    - Procesa el objeto de recurso de PayPal.
    - Activa la suscripción en la BD y actualiza el `tipo_usuario` en la tabla `users`.

## 3. Variantes de Datos y Estados del Usuario

El sistema maneja los siguientes estados lógicos (`estado_logico`):

| Estado | Descripción | Límite de Traducción |
| :--- | :--- | :--- |
| **`EnPrueba`** | Usuario en sus primeros 30 días desde el registro. | Ilimitado |
| **`Inicio`** | Suscripción Premium de 1 mes activa. | Ilimitado |
| **`Ahorro`** | Suscripción Premium de 6 meses activa. | Ilimitado |
| **`Pro`** | Suscripción Premium de 12 meses activa. | Ilimitado |
| **`limitado`** | Periodo de prueba finalizado y sin suscripción activa. | 300 palabras/semana |

## 4. Acciones Manuales de Prueba

Desde `dePago/test/webhook_handler.php` se pueden ejecutar las siguientes acciones para facilitar el desarrollo:

1.  **Resetear Usuario**: Vuelve al usuario al estado `limitado` y borra su historial de suscripciones.
2.  **Simular Transferencia**: Crea un registro de suscripción con estado `pending` (esperando pago manual).
3.  **Confirmar Pago**: Cambia una suscripción `pending` a `active`, calculando la fecha de fin según el plan.
4.  **Simular Webhook de PayPal**: Permite introducir un ID de PayPal para disparar la lógica de activación automática (`handleSaleCompleted`).

## 5. Estructura de Datos en Base de Datos

- **Tabla `users`**:
    - `tipo_usuario`: Almacena el plan actual (`limitado`, `Inicio`, `Ahorro`, `Pro`).
    - `fecha_registro`: Base para el cálculo del mes de prueba.
- **Tabla `user_subscriptions`**:
    - `status`: `pending` o `active`.
    - `payment_method`: `paypal` o `transferencia`.
    - `fecha_fin`: Fecha exacta de expiración del acceso Premium.
