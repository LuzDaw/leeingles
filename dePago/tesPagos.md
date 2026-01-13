# Documentación de Pruebas de Pago - LeeIngles

Este documento detalla el funcionamiento del sistema de pruebas para los pagos y suscripciones en el directorio `dePago`.

## 1. Flujo de Pruebas de PayPal

El sistema utiliza el entorno de PayPal para procesar suscripciones automáticas.

### Archivos Principales de Prueba
- **`dePago/test/paypal_sandbox_test.php`**: Interfaz visual que carga el SDK de PayPal para verificar la integración del botón.
- **`dePago/test/webhook_handler.php`**: Receptor de notificaciones de PayPal (Webhooks) y herramienta de reseteo de usuario para pruebas.
- **`dePago/ajax_confirm_payment.php`**: Endpoint que recibe la confirmación inmediata desde el frontend tras un pago exitoso.

## 2. Funciones de Control de Suscripción

Las funciones principales se encuentran en `dePago/subscription_functions.php`:

- **`getUserSubscriptionStatus($user_id)`**: 
    - Es la función central que determina el estado del usuario.
    - Calcula días desde el registro.
    - Verifica si existe una suscripción activa en la tabla `user_subscriptions`.
    - Actualiza automáticamente a `limitado` si la suscripción ha expirado.
- **`activateUserPlan($user_id, $plan, $paypal_id, $method)`**:
    - Activa un plan y actualiza el rango del usuario.
    - **Lógica de Acumulación:** Si el usuario ya tiene un plan activo, el nuevo tiempo se suma al final de la suscripción actual, evitando que el usuario pierda días por renovar anticipadamente.
- **`checkTranslationLimit($user_id)`**:
    - Verifica si un usuario `limitado` ha superado las 300 palabras semanales.
    - Los usuarios `EnPrueba` o Premium no tienen este límite.

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

Desde `dePago/test/webhook_handler.php` y `dePago/test/test.php` se pueden ejecutar las siguientes acciones:

1.  **Resetear Usuario**: Vuelve al usuario al estado `limitado` y borra su historial de suscripciones.
2.  **Simular Pago PayPal**: Crea un registro de suscripción activo usando la lógica real de activación.
3.  **Simular Webhook de PayPal**: Dispara la lógica de activación automática simulando una notificación de PayPal.
4.  **Simular Vencimiento**: Permite establecer fechas de fin específicas para probar la acumulación de tiempo en renovaciones.

## 5. Estructura de Datos en Base de Datos

- **Tabla `users`**:
    - `tipo_usuario`: Almacena el plan actual (`limitado`, `Inicio`, `Ahorro`, `Pro`).
- **Tabla `user_subscriptions`**:
    - `status`: `active`, `expired`, etc.
    - `payment_method`: `paypal`.
    - `fecha_fin`: Fecha exacta de expiración del acceso Premium.
