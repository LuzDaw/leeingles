param(
    [switch]$Apply
)

Write-Output "Rollback restore script - busca archivos '*.bak.similar' y los restaura sobre el original."
Write-Output "Directorio actual: $(Get-Location)"

$files = Get-ChildItem -Path . -Recurse -Filter '*.bak.similar' -File -ErrorAction SilentlyContinue
if (-not $files) {
    Write-Output "No se encontraron archivos .bak.similar"
    exit 0
}

foreach ($f in $files) {
    $orig = $f.FullName -replace '\.bak\.similar$',''
    if ($Apply) {
        Write-Output "Restaurando: $($f.FullName) -> $orig"
        try {
            Move-Item -LiteralPath $f.FullName -Destination $orig -Force
        } catch {
            Write-Error "Error restaurando $($f.FullName): $_"
        }
    } else {
        Write-Output "DRY RUN: $($f.FullName) -> $orig"
    }
}

if (-not $Apply) {
    Write-Output "Dry run completado. Para aplicar, ejecuta: .\rollback_restore.ps1 -Apply"
}
