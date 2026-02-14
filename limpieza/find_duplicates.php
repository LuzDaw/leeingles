<?php
// find_duplicates.php
// Ejecutar desde la raíz del proyecto: php limpieza/find_duplicates.php

$projectRoot = realpath(__DIR__ . '/..');
if (!$projectRoot) {
    echo "No se pudo determinar la raíz del proyecto.\n";
    exit(1);
}

$excludeDirs = [
    realpath(__DIR__),
    realpath($projectRoot . DIRECTORY_SEPARATOR . '.git') ?: '',
];

function isExcluded($path, $excludeDirs) {
    foreach ($excludeDirs as $ex) {
        if (!$ex) continue;
        if (strpos($path, $ex) === 0) return true;
    }
    return false;
}

$textExts = ['php','js','css','html','htm','txt','md','json'];
$allFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (isExcluded($path, $excludeDirs)) continue;
    $allFiles[] = $path;
}

// Exact duplicates by hash
$hashes = [];
foreach ($allFiles as $f) {
    // salt: include file size and mtime to reduce collisions risk
    $size = @filesize($f);
    $hash = @md5_file($f);
    if ($hash === false) continue;
    $key = $hash;
    $hashes[$key][] = $f;
}

$exactDuplicates = [];
foreach ($hashes as $h => $group) {
    if (count($group) > 1) {
        $exactDuplicates[] = $group;
    }
}

// Similar files for textual types (pairwise, O(n^2) but limited to text files)
$textFiles = [];
foreach ($allFiles as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, $textExts, true)) {
        $textFiles[] = $f;
    }
}

$similarPairs = [];
$limitSize = 200 * 1024; // 200 KB
$threshold = 80; // porcentaje de similitud para reportar
$n = count($textFiles);
for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        $a = $textFiles[$i];
        $b = $textFiles[$j];
        $sa = @filesize($a);
        $sb = @filesize($b);
        if ($sa === false || $sb === false) continue;
        if ($sa > $limitSize || $sb > $limitSize) continue;
        $extA = strtolower(pathinfo($a, PATHINFO_EXTENSION));
        $extB = strtolower(pathinfo($b, PATHINFO_EXTENSION));
        if ($extA !== $extB) continue; // comparar solo mismo tipo
        $ca = @file_get_contents($a);
        $cb = @file_get_contents($b);
        if ($ca === false || $cb === false) continue;
        // Normalizar: eliminar espacios en exceso y comentarios sencillos para mejorar comparación
        $na = preg_replace('/\s+/', ' ', strip_tags($ca));
        $nb = preg_replace('/\s+/', ' ', strip_tags($cb));
        similar_text($na, $nb, $percent);
        if ($percent >= $threshold) {
            $similarPairs[] = [
                'file_a' => $a,
                'file_b' => $b,
                'percent' => round($percent, 2),
            ];
        }
    }
}

$report = [
    'generated_at' => date('c'),
    'project_root' => $projectRoot,
    'total_files' => count($allFiles),
    'exact_duplicate_groups' => $exactDuplicates,
    'similar_pairs' => $similarPairs,
];

$reportPath = $projectRoot . DIRECTORY_SEPARATOR . 'limpieza' . DIRECTORY_SEPARATOR . 'duplicates_report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Resumen por consola
echo "Detector de duplicados completado.\n";
echo "Archivos analizados: " . count($allFiles) . "\n";
echo "Grupos exactos de duplicados: " . count($exactDuplicates) . "\n";
echo "Pares similares reportados: " . count($similarPairs) . "\n";
echo "Reporte escrito en: limpieza/duplicates_report.json\n";

$maxShow = 50;
$cnt = 0;
if (count($exactDuplicates) > 0) {
    echo "\nGrupos exactos (muestra):\n";
    foreach ($exactDuplicates as $group) {
        echo " - Grupo:\n";
        foreach ($group as $f) echo "    * " . ltrim(str_replace($projectRoot, '', $f), DIRECTORY_SEPARATOR) . "\n";
        if (++$cnt >= $maxShow) break;
    }
}

if (count($similarPairs) > 0) {
    echo "\nPares similares (muestra):\n";
    $cnt = 0;
    foreach ($similarPairs as $p) {
        echo " - " . ltrim(str_replace($projectRoot, '', $p['file_a']), DIRECTORY_SEPARATOR) . " <-> " . ltrim(str_replace($projectRoot, '', $p['file_b']), DIRECTORY_SEPARATOR) . " ({$p['percent']}%)\n";
        if (++$cnt >= $maxShow) break;
    }
}

exit(0);
