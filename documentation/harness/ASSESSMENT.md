# Harness Assessment (2026-03-10)

## What's legible today
- Bundle Symfony PHP avec zones nettes: Controller, ACL, Data, Mercure, Serializer.
- Documentation fonctionnelle existante sur ACL et search API.
- Outils statiques disponibles (`phpstan`, `php-cs-fixer`) via vendor parent.

## What's missing for agent effectiveness
- Pas d'index d'orientation agent.
- Pas de standard ExecPlan.
- Pas de checks doc/invariants mécaniques.
- Pas de workflow PR evidence-first standardisé.
- Pas de boucle explicite de doc gardening.

## Top 10 leviers docs/guardrails ajoutés
1. `AGENTS.md` court + signature harness.
2. `documentation/index.md` (3 parcours).
3. `ARCHITECTURE.md` codemap stable.
4. `PLANS.md` standard ExecPlan.
5. `documentation/run/*` pour setup/tests/CI.
6. `documentation/core-beliefs.md` (principes enforceables).
7. `documentation/merge-policy.md` throughput doctrine.
8. `documentation/leverage-log.md` capability gaps -> fixes.
9. `documentation/quality-score.md` scoring + backlog.
10. `scripts/check-docs.sh` + workflow CI informatif.

## Unknowns + discovery paths
- Suite de tests locale officielle du package: Unknown.
- Workflow CI global du mono-repo parent: Unknown.
- Ownership/review cadence des docs: Unknown.

Méthodes de levée:
- `find . -maxdepth 3 -type d -name tests`
- `rg -n "phpunit|pest|behat|workflow" .`
- alignement humain sur politique de review doc.
