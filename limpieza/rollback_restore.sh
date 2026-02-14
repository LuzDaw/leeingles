#!/usr/bin/env bash
set -eu

DRY_RUN=1
if [[ "${1-}" == "--apply" ]]; then
  DRY_RUN=0
fi

echo "Rollback restore script - busca archivos '*.bak.similar' y los restaura sobre el original."
echo "Directorio actual: $(pwd)"

mapfile -t files < <(find . -type f -name "*.bak.similar" 2>/dev/null)
if [ ${#files[@]} -eq 0 ]; then
  echo "No se encontraron archivos .bak.similar"
  exit 0
fi

for f in "${files[@]}"; do
  orig="${f%.bak.similar}"
  if [ "$DRY_RUN" -eq 1 ]; then
    echo "DRY RUN: $f -> $orig"
  else
    echo "Restaurando: $f -> $orig"
    mv -f -- "$f" "$orig" || echo "Error moviendo $f"
  fi
done

if [ "$DRY_RUN" -eq 1 ]; then
  echo "Dry run completado. Para aplicar, ejecuta: ./rollback_restore.sh --apply"
fi
