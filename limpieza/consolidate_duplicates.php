<?php
// consolidate_duplicates.php
// Reemplaza archivos PHP exactos duplicados por un wrapper que requiere el archivo canónico.
// Ejecutar desde la raíz del proyecto con PHP de XAMPP si usas Windows:
// & "C:\\xampp\\php\\php.exe" "limpieza/consolidate_duplicates.php"

$projectRoot = realpath(__DIR__ . '/..');
$reportPath = __DIR__ . DIRECTORY_SEPARATOR . 'duplicates_report.json';
if (!file_exists($reportPath)) {
    echo "No se encontró duplicates_report.json. Ejecuta primero find_duplicates.php\n";
    exit(1);
}

$report = json_decode(file_get_contents($reportPath), true);
if (!$report) {
    echo "No se pudo leer el JSON de duplicados.\n";
    exit(1);
}

$groups = $report['exact_duplicate_groups'] ?? [];
$actions = [];

foreach ($groups as $group) {
    if (!is_array($group) || count($group) < 2) continue;
    // Tomar el primer archivo como canónico
    $canonical = $group[0];
    $canonicalReal = realpath($projectRoot . DIRECTORY_SEPARATOR . $canonical) ?: realpath($canonical);
    if (!$canonicalReal) {
        // intentar ruta relativa directa
        $canonicalReal = realpath($canonical);
    }
    if (!$canonicalReal) {
        $actions[] = ['group' => $group, 'status' => 'skipped', 'reason' => 'canonical_not_found'];
        continue;
    }

    foreach ($group as $i => $file) {
        // saltar el canónico
        if ($i === 0) continue;
        $filePath = realpath($projectRoot . DIRECTORY_SEPARATOR . $file) ?: realpath($file);
        if (!$filePath) {
            $actions[] = ['file' => $file, 'status' => 'skipped', 'reason' => 'file_not_found'];
            continue;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== 'php') {
            $actions[] = ['file' => $file, 'status' => 'skipped', 'reason' => 'non_php'];
            continue;
        }

        // crear backup si no existe
        $backupPath = $filePath . '.bak';
        if (!file_exists($backupPath)) {
            if (!@copy($filePath, $backupPath)) {
                $actions[] = ['file' => $file, 'status' => 'skipped', 'reason' => 'backup_failed'];
                continue;
            }
        }

        // crear wrapper que requiera la ruta canónica absoluta (convertir a slashes)
        $canonicalForRequire = str_replace('\\', '/', $canonicalReal);
        $wrapper = "<?php\n// Consolidated: este archivo fue reemplazado para requerir el canonical.\nrequire_once '" . addslashes($canonicalForRequire) . "';\n";
        if (@file_put_contents($filePath, $wrapper) === false) {
            $actions[] = ['file' => $file, 'status' => 'failed_write'];
            continue;
        }

        $actions[] = ['file' => $file, 'status' => 'replaced_with_require', 'canonical' => $canonicalReal, 'backup' => $backupPath];
    }
}

// Escribir reporte de acciones
$actionsReportPath = __DIR__ . DIRECTORY_SEPARATOR . 'consolidation_actions.json';
file_put_contents($actionsReportPath, json_encode(['generated_at' => date('c'), 'actions' => $actions], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Mostrar resumen
$replaced = 0; $skipped = 0; $failed = 0;
foreach ($actions as $a) {
    if (isset($a['status']) && $a['status'] === 'replaced_with_require') $replaced++;
    elseif (isset($a['status']) && strpos($a['status'], 'skipped') === 0) $skipped++;
    else $failed++;
}

echo "Consolidación completada. Reemplazados: $replaced, Saltados: $skipped, Fallos: $failed\n";
echo "Reporte de acciones: limpieza/consolidation_actions.json\n";
exit(0);
