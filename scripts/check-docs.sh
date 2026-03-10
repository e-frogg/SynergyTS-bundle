#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

errors=0

error() {
    local msg="$1"
    local next="$2"
    local ref="$3"
    echo "[check-docs] ERROR: ${msg}" >&2
    echo "  Why this matters: le harness doit rester navigable et vérifiable automatiquement." >&2
    echo "  What to do next: ${next}" >&2
    echo "  Reference: ${ref}" >&2
    errors=$((errors + 1))
}

required_files=(
    "AGENTS.md"
    "ARCHITECTURE.md"
    "PLANS.md"
    "documentation/index.md"
    "documentation/core-beliefs.md"
    "documentation/merge-policy.md"
    "documentation/leverage-log.md"
    "documentation/quality-score.md"
    "documentation/run/local-dev.md"
    "documentation/run/testing.md"
    "documentation/run/ci.md"
)

for file in "${required_files[@]}"; do
    if [[ ! -f "$file" ]]; then
        error "fichier requis manquant: $file" "créez le fichier ou ajustez le check après décision explicite." "documentation/index.md"
    fi
done

if [[ -f AGENTS.md ]]; then
    line_count="$(wc -l < AGENTS.md | tr -d ' ')"
    if (( line_count > 100 )); then
        error "AGENTS.md dépasse 100 lignes (${line_count})." "réduire AGENTS.md (TOC/règles seulement) et déplacer le détail vers documentation/." "AGENTS.md"
    fi
fi

if [[ -f documentation/index.md ]]; then
    index_refs=(
        "core-beliefs.md"
        "merge-policy.md"
        "leverage-log.md"
        "quality-score.md"
        "run/local-dev.md"
        "run/testing.md"
        "run/ci.md"
    )

    for ref in "${index_refs[@]}"; do
        if ! grep -Fq "$ref" documentation/index.md; then
            error "documentation/index.md ne référence pas: $ref" "ajouter un lien explicite dans l'index pour le parcours agent." "documentation/index.md"
        fi
    done
fi

check_md_links() {
    local md_file="$1"
    local file_dir
    file_dir="$(dirname "$md_file")"

    while IFS= read -r raw_target; do
        [[ -z "$raw_target" ]] && continue

        # Ignore anchors and external schemes.
        if [[ "$raw_target" == \#* ]]; then
            continue
        fi

        case "$raw_target" in
            http://*|https://*|mailto:*|tel:*)
                continue
                ;;
        esac

        local target
        target="${raw_target%%#*}"
        target="${target%%\?*}"
        [[ -z "$target" ]] && continue

        local resolved
        if [[ "$target" == /* ]]; then
            resolved="${ROOT_DIR}${target}"
        else
            resolved="$(realpath -m "${file_dir}/${target}")"
        fi

        if [[ ! -e "$resolved" ]]; then
            error \
                "lien cassé dans ${md_file}: ${raw_target}" \
                "corriger le lien ou créer la cible attendue." \
                "documentation/run/ci.md"
        fi
    done < <(grep -oE '\[[^][]+\]\(([^)]+)\)' "$md_file" | sed -E 's/.*\(([^)]+)\)/\1/' || true)
}

while IFS= read -r md; do
    check_md_links "$md"
done < <(find . -type f -name '*.md' -not -path './.git/*' | sort)

if (( errors > 0 )); then
    echo "[check-docs] FAILED with ${errors} error(s)." >&2
    exit 1
fi

echo "[check-docs] OK"
