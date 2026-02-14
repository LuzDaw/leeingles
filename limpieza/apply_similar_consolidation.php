<?php
// apply_similar_consolidation.php
// Aplica las sugerencias de `similar_consolidation_suggestions.json` creando backups
// y reemplazando archivos PHP por un wrapper que requiere el canonical.

$projectRoot = realpath(__DIR__ . '/..');
$suggestionsPath = __DIR__ . DIRECTORY_SEPARATOR . 'similar_consolidation_suggestions.json';

// Permitir --no-backup para omitir creación de copias de seguridad (ya hechas por el usuario)
$createBackups = true;
if (isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--no-backup') {
            $createBackups = false;
        }
    }
}
if (!file_exists($suggestionsPath)) {
    echo "No se encontró similar_consolidation_suggestions.json. Ejecuta summarize_similar.php primero.\n";
    exit(1);
}

$data = json_decode(file_get_contents($suggestionsPath), true);
if (!$data || !isset($data['suggestions'])) {
    echo "JSON inválido en similar_consolidation_suggestions.json\n";
    exit(1);
}

$actions = [];
foreach ($data['suggestions'] as $sugg) {
    $canonical = $sugg['canonical'];
    $canonicalReal = realpath($canonical) ?: realpath($projectRoot . DIRECTORY_SEPARATOR . $canonical);
    if (!$canonicalReal) {
        $actions[] = ['canonical' => $canonical, 'status' => 'skipped', 'reason' => 'canonical_not_found'];
        continue;
    }
    foreach ($sugg['replace_with_require'] as $target) {
        $targetReal = realpath($target) ?: realpath($projectRoot . DIRECTORY_SEPARATOR . $target);
        if (!$targetReal) {
            $actions[] = ['target' => $target, 'status' => 'skipped', 'reason' => 'target_not_found'];
            continue;
        }
        $ext = strtolower(pathinfo($targetReal, PATHINFO_EXTENSION));
        if ($ext !== 'php') {
            $actions[] = ['target' => $targetReal, 'status' => 'skipped', 'reason' => 'non_php'];
            continue;
        }
        // Crear backup si no existe y si está permitido
        $backup = $targetReal . '.bak.similar';
        if ($createBackups) {
            if (!file_exists($backup)) {
                if (!@copy($targetReal, $backup)) {
                    $actions[] = ['target' => $targetReal, 'status' => 'skipped', 'reason' => 'backup_failed'];
                    continue;
                }
            }
        }
        // Crear wrapper que requiera el canonical absoluto (usando forward slashes)
        $canonicalForRequire = str_replace('\\', '/', $canonicalReal);
        $wrapper = "<?php\n// Consolidated (similar): este archivo fue reemplazado para requerir el canonical.\nrequire_once '" . addslashes($canonicalForRequire) . "';\n";
        if (@file_put_contents($targetReal, $wrapper) === false) {
            $actions[] = ['target' => $targetReal, 'status' => 'failed_write'];
            continue;
        }
        $actions[] = ['target' => $targetReal, 'status' => 'replaced_with_require', 'canonical' => $canonicalReal, 'backup' => $backup];
    }
}

$outPath = __DIR__ . DIRECTORY_SEPARATOR . 'apply_similar_actions.json';
file_put_contents($outPath, json_encode(['generated_at' => date('c'), 'actions' => $actions], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$replaced = count(array_filter($actions, fn($a)=>isset($a['status']) && $a['status']==='replaced_with_require'));
$skipped = count(array_filter($actions, fn($a)=>isset($a['status']) && strpos($a['status'],'skipped')===0));
$failed = count(array_filter($actions, fn($a)=>isset($a['status']) && in_array($a['status'], ['failed_write'])));

echo "Aplicación completada. Reemplazados: $replaced, Saltados: $skipped, Fallos: $failed\n";
echo "Reporte: limpieza/apply_similar_actions.json\n";
exit(0);
