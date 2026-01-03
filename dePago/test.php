<?php
/**
 * Archivo de prueba para el sistema de suscripción y control de tiempo (SEMANAL)
 * Ubicación: dePago/test.php
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/subscription_functions.php';

session_start();

// Para pruebas, si no hay sesión, intentamos pillar el primer usuario de la BD
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $res = $conn->query("SELECT id, username FROM users LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['id'];
        $test_username = $row['username'];
    }
} else {
    $test_username = $_SESSION['username'] ?? 'Usuario';
}

// Procesar cambio de fecha para pruebas (Simulador de tiempo)
if (isset($_POST['action']) && $_POST['action'] === 'update_date' && $user_id) {
    $days = (int)$_POST['days_ago'];
    $new_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    debugUpdateRegistrationDate($user_id, $new_date);
    header("Location: test.php?updated=1");
    exit;
}

// Procesar simulación de uso
if (isset($_POST['action']) && $_POST['action'] === 'simulate_usage' && $user_id) {
    $words = (int)$_POST['words'];
    debugAddUsage($user_id, $words);
    header("Location: test.php?usage_updated=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Suscripción Semanal - LeeIngles</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f9; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 600px; margin: auto; margin-bottom: 20px; }
        h1, h2, h3 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .data-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9f9f9; }
        .label { font-weight: bold; color: #555; }
        .value { color: #000; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .gratis { background: #e3f2fd; color: #1976d2; }
        .limitado { background: #fff3e0; color: #f57c00; }
        .premium { background: #e8f5e9; color: #388e3c; }
        .debug { margin-top: 20px; font-size: 0.8em; color: #888; background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn-test { width:100%; padding:10px; cursor:pointer; font-weight:bold; border-radius: 4px; border: 1px solid; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="card">
    <h1>Control de Suscripción (Semanal)</h1>
    
    <?php if ($user_id): 
        $status = getUserSubscriptionStatus($user_id);
        $limit_info = checkTranslationLimit($user_id);
    ?>
        <p>Mostrando datos de prueba para: <strong><?php echo htmlspecialchars($test_username); ?></strong> (ID: <?php echo $user_id; ?>)</p>
        
        <div class="data-row">
            <span class="label">Estado Actual:</span>
            <span class="value status-badge <?php echo $status['estado_logico']; ?>">
                <?php echo strtoupper($status['estado_logico']); ?>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Fecha de Registro:</span>
            <span class="value"><?php echo $status['fecha_registro']; ?></span>
        </div>

        <div class="data-row">
            <span class="label">Días Transcurridos:</span>
            <span class="value"><?php echo $status['dias_transcurridos']; ?> días</span>
        </div>

        <div class="data-row">
            <span class="label">Mes de Uso (Relativo):</span>
            <span class="value">Mes <?php echo $status['mes_de_uso']; ?> 
                <?php echo ($status['mes_de_uso'] == 0) ? '(Periodo Gratuito)' : '(Periodo de Pago)'; ?>
            </span>
        </div>

        <div class="data-row">
            <span class="label">Fin Mes Gratuito:</span>
            <span class="value"><?php echo $status['fin_mes_gratuito']; ?></span>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 5px solid #1976d2;">
            <h3 style="margin-top:0;">Consumo Semanal (Límite 300)</h3>
            <div class="data-row">
                <span class="label">Semana Actual:</span>
                <span class="value"><?php echo $status['semana_iso']; ?> (Año <?php echo $status['anio_iso']; ?>)</span>
            </div>
            <div class="data-row">
                <span class="label">Próximo Reinicio (Domingo):</span>
                <span class="value" style="font-weight:bold; color:#d32f2f;"><?php echo $status['proximo_reinicio_semanal']; ?></span>
            </div>
            <div class="data-row">
                <span class="label">Palabras Traducidas:</span>
                <span class="value" style="font-size: 1.2em; font-weight: bold;">
                    <?php echo getWeeklyUsage($user_id); ?>
                </span>
            </div>
            <div class="data-row">
                <span class="label">Límite Semanal:</span>
                <span class="value">
                    <?php echo ($limit_info['reason'] === 'unlimited') ? 'ILIMITADO' : '300 palabras'; ?>
                </span>
            </div>
            <?php if (isset($limit_info['remaining'])): ?>
            <div class="data-row">
                <span class="label">Palabras Restantes:</span>
                <span class="value" style="color: <?php echo ($limit_info['remaining'] < 50) ? 'red' : 'green'; ?>;">
                    <?php echo $limit_info['remaining']; ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!$limit_info['can_translate']): ?>
                <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-top: 10px; font-weight: bold; text-align: center;">
                    ⚠️ LÍMITE ALCANZADO: Las traducciones se bloquearán hasta el domingo.
                </div>
            <?php endif; ?>
        </div>

        <div class="debug">
            <strong>Raw Data:</strong><br>
            <pre><?php print_r($status); ?></pre>
        </div>

    <?php else: ?>
        <p style="color: red;">No se encontró ningún usuario para realizar la prueba.</p>
    <?php endif; ?>
</div>

<?php if ($user_id): ?>
<div class="card">
    <h2>Panel de Pruebas (Simulador de Tiempo)</h2>
    <p>Usa estos botones para cambiar tu fecha de registro:</p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <form method="POST">
            <input type="hidden" name="action" value="update_date">
            <input type="hidden" name="days_ago" value="0">
            <button type="submit" class="btn-test" style="background:#e3f2fd; border-color:#1976d2; color:#1976d2;">
                Simular Registro HOY (Mes Gratis)
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="update_date">
            <input type="hidden" name="days_ago" value="35">
            <button type="submit" class="btn-test" style="background:#fff3e0; border-color:#f57c00; color:#f57c00;">
                Simular Hace 35 Días (Fuera de Mes Gratis)
            </button>
        </form>
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <form method="POST">
            <input type="hidden" name="action" value="update_date">
            <label>O introduce días exactos atrás:</label><br>
            <input type="number" name="days_ago" placeholder="Ej: 45" required style="padding:8px; width:100px;">
            <button type="submit" style="padding:8px 20px; cursor:pointer;">Cambiar Fecha</button>
        </form>
    </div>
</div>

<div class="card">
    <h2>Simulador de Consumo Semanal</h2>
    <p>Simula traducciones para ver cómo sube el contador (Límite 300):</p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
        <form method="POST">
            <input type="hidden" name="action" value="simulate_usage">
            <input type="hidden" name="words" value="1">
            <button type="submit" class="btn-test" style="background:#fff; border-color:#ccc; color:#333;">
                +1 Palabra
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="simulate_usage">
            <input type="hidden" name="words" value="50">
            <button type="submit" class="btn-test" style="background:#fff; border-color:#ccc; color:#333;">
                +50 Palabras
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="simulate_usage">
            <input type="hidden" name="words" value="100">
            <button type="submit" class="btn-test" style="background:#fff; border-color:#ccc; color:#333;">
                +100 Palabras
            </button>
        </form>
    </div>
    
    <div style="margin-top: 15px;">
        <form method="POST">
            <input type="hidden" name="action" value="simulate_usage">
            <label>Añadir cantidad exacta:</label><br>
            <input type="number" name="words" placeholder="Ej: 250" required style="padding:8px; width:100px;">
            <button type="submit" style="padding:8px 20px; cursor:pointer;">Añadir Uso</button>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
