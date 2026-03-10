#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

bash scripts/check-docs.sh

echo "[docs-garden] scanning potential orphan docs..."

all_docs_file="$(mktemp)"
refs_file="$(mktemp)"
trap 'rm -f "$all_docs_file" "$refs_file"' EXIT

find documentation -type f -name '*.md' | sort > "$all_docs_file"

while IFS= read -r md_file; do
    md_dir="$(dirname "$md_file")"
    while IFS= read -r target; do
            [[ -z "$target" ]] && continue
            [[ "$target" == \#* ]] && continue
            case "$target" in
                http://*|https://*|mailto:*|tel:*)
                    continue
                    ;;
            esac
            target="${target%%#*}"
            target="${target%%\?*}"
            [[ -z "$target" ]] && continue
            if [[ "$target" == /* ]]; then
                resolved=".${target}"
            else
                resolved="$(realpath --relative-to="$ROOT_DIR" -m "${md_dir}/${target}")"
            fi
            if [[ "$resolved" == documentation/* && -f "$resolved" ]]; then
                echo "$resolved"
            fi
        done < <(grep -oE '\[[^][]+\]\(([^)]+)\)' "$md_file" | sed -E 's/.*\(([^)]+)\)/\1/' || true)

done < <(find . -type f -name '*.md' -not -path './.git/*' | sort) | sort -u > "$refs_file"

orphan_count=0
while IFS= read -r doc; do
    if [[ "$doc" == "documentation/index.md" ]]; then
        continue
    fi
    if ! grep -Fxq "$doc" "$refs_file"; then
        echo "[docs-garden] potential orphan: $doc"
        orphan_count=$((orphan_count + 1))
    fi
done < "$all_docs_file"

if (( orphan_count == 0 )); then
    echo "[docs-garden] no orphan docs detected."
else
    echo "[docs-garden] ${orphan_count} potential orphan(s). Link or archive them in a small PR."
fi

echo "[docs-garden] update quality score after review: documentation/quality-score.md"
