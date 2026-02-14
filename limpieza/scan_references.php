<?php
// scan_references.php
// Ejecutar desde la raíz del proyecto: php limpieza/scan_references.php

$projectRoot = realpath(__DIR__ . '/..');
if (!$projectRoot) {
    echo "No se pudo determinar la raíz del proyecto.\n";
    exit(1);
}

$excludeDirs = [
    realpath(__DIR__), // evitar escanear la carpeta limpieza
    realpath($projectRoot . DIRECTORY_SEPARATOR . '.git') ?: '',
];

$candidateExt = ['png','jpg','jpeg','gif','svg','css','js'];
$searchExt = ['php','html','htm','js','css','txt','md','json'];

function isExcluded($path, $excludeDirs) {
    foreach ($excludeDirs as $ex) {
        if (!$ex) continue;
        if (strpos($path, $ex) === 0) return true;
    }
    return false;
}

$allFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (isExcluded($path, $excludeDirs)) continue;
    $allFiles[] = $path;
}

$candidates = [];
$searchFiles = [];
foreach ($allFiles as $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, $candidateExt, true)) {
        $candidates[] = $f;
    }
    if (in_array($ext, $searchExt, true)) {
        $searchFiles[] = $f;
    }
}

// Cargar contenidos de búsqueda en memoria de forma segura (solo texto)
$searchContents = [];
foreach ($searchFiles as $sf) {
    $content = @file_get_contents($sf);
    if ($content === false) continue;
    $searchContents[$sf] = $content;
}

$unused = [];
foreach ($candidates as $c) {
    $basename = basename($c);
    $relPath = ltrim(str_replace($projectRoot, '', $c), DIRECTORY_SEPARATOR);
    $found = false;
    foreach ($searchContents as $sf => $content) {
        if (strpos($content, $basename) !== false) { $found = true; break; }
        if (strpos($content, $relPath) !== false) { $found = true; break; }
    }
    if (!$found) {
        $unused[] = [
            'path' => $c,
            'basename' => $basename,
            'relpath' => $relPath,
        ];
    }
}

$report = [
    'generated_at' => date('c'),
    'project_root' => $projectRoot,
    'total_candidates' => count($candidates),
    'unused_count' => count($unused),
    'unused' => $unused,
];

$reportPath = $projectRoot . DIRECTORY_SEPARATOR . 'limpieza' . DIRECTORY_SEPARATOR . 'unused_report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Escaneo completado.\n";
echo "Candidatos analizados: " . count($candidates) . "\n";
echo "Posibles no usados: " . count($unused) . "\n";
echo "Reporte escrito en: limpieza/unused_report.json\n";

// Pequeña muestra por consola (hasta 50)
$maxShow = 50;
$cnt = 0;
foreach ($unused as $u) {
    echo " - " . $u['relpath'] . "\n";
    if (++$cnt >= $maxShow) { if (count($unused) > $maxShow) echo " ... (" . (count($unused)-$maxShow) . " más)\n"; break; }
}

exit(0);
