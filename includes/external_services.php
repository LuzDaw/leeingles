<?php
// includes/external_services.php
// Wrapper centralizado para llamadas a servicios externos (traducción, email, pagos)

require_once __DIR__ . '/translation_service.php';
require_once __DIR__ . '/email_service.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/helpers.php';
// payment functions are in dePago/
require_once __DIR__ . '/../dePago/payment_functions.php';

/**
 * Traduce un texto usando el servicio configurado.
 * Devuelve el array resultado de `translateText` o ['error'=>...]
 */
function external_translate_text(string $text, array $opts = []) {
    // Opciones posibles: target_lang, use_cache (true|false)
    $use_cache = array_key_exists('use_cache', $opts) ? (bool)$opts['use_cache'] : true;
    $ttl = getenv('CACHE_TTL_TRANSLATIONS') ? intval(getenv('CACHE_TTL_TRANSLATIONS')) : 86400;

    // Normalizar clave
    $key = 'translate_' . md5($text);

    if ($use_cache) {
        $cached = cache_get($key);
        if ($cached !== null) {
            return $cached;
        }
    }

    try {
        $res = translateText($text);
        if ($use_cache && !isset($res['error'])) {
            safe_cache_set($key, $res, $ttl);
        }
        return $res;
    } catch (Exception $e) {
        error_log('external_services::translateText error: ' . $e->getMessage());
        return ['error' => 'Error de traducción'];
    }
}

/**
 * Envío de email via email_service.php
 * Mantiene la compatibilidad con la antigua función `sendEmail`
 */
function external_send_email(string $toEmail, string $toName, string $subject, string $body) {
    try {
        return sendEmail($toEmail, $toName, $subject, $body);
    } catch (Exception $e) {
        error_log('external_services::sendEmail error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Interfaz simple para pagos. Delegará a `payment_functions.php` si existe.
 * $method puede ser 'paypal', 'stripe', 'subscription', etc. $metadata libre.
 */
function external_charge(array $paymentPayload) {
    // Ejemplo de payload: ['user_id'=>1, 'amount'=>100, 'method'=>'paypal', 'metadata'=>[]]
    try {
        if (function_exists('processPayment')) {
            return processPayment($paymentPayload);
        }
        // Si no hay implementacion, devolver error controlado
        return ['success' => false, 'error' => 'Servicio de pagos no disponible'];
    } catch (Exception $e) {
        error_log('external_services::charge error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>
