<?php
// fix_wrappers_to_relative.php
// Lee apply_similar_actions.json y reescribe los wrappers reemplazando
// require_once 'C:/.../actions/delete_text.php' por una ruta relativa desde el archivo.

$projectRoot = realpath(__DIR__ . '/..');
$actionsPath = __DIR__ . DIRECTORY_SEPARATOR . 'apply_similar_actions.json';
if (!file_exists($actionsPath)) {
    echo "No se encontró apply_similar_actions.json.\n";
    exit(1);
}

$data = json_decode(file_get_contents($actionsPath), true);
if (!$data || !isset($data['actions'])) {
    echo "JSON inválido en apply_similar_actions.json\n";
    exit(1);
}

function make_relative_path($from, $to) {
    // from: directory path where wrapper lives
    // to: absolute canonical file path
    $from = str_replace('\\', '/', rtrim($from, '/'));
    $to = str_replace('\\', '/', $to);
    $fromParts = explode('/', $from);
    $toParts = explode('/', $to);

    // Remove common base
    while(count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]){
        array_shift($fromParts);
        array_shift($toParts);
    }
    $rel = str_repeat('../', count($fromParts));
    $rel .= implode('/', $toParts);
    return $rel;
}

$updated = 0; $skipped = 0; $failed = 0;
foreach ($data['actions'] as $act) {
    if (!isset($act['status']) || $act['status'] !== 'replaced_with_require') continue;
    $target = $act['target'];
    $canonical = $act['canonical'];
    if (!file_exists($target) || !file_exists($canonical)) {
        $skipped++; continue;
    }
    $targetDir = dirname($target);
    $relative = make_relative_path($targetDir, $canonical);
    // Normalize to forward slashes
    $relative = str_replace('\\', '/', $relative);
    // If relative does not start with . or .., prefix with './'
    if (!preg_match('/^(\.|\.\.)/', $relative)) {
        $relative = './' . $relative;
    }
    $wrapper = "<?php\n// Consolidated (relative): este archivo fue reemplazado para requerir el canonical relativo.\nrequire_once __DIR__ . '/" . addslashes($relative) . "';\n";
    if (@file_put_contents($target, $wrapper) === false) {
        $failed++; continue;
    }
    $updated++;
}

echo "Reescritos: $updated, Saltados: $skipped, Fallos: $failed\n";
exit(0);
