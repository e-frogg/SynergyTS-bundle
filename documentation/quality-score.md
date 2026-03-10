# Quality Score

Barème: `0` absent, `3` partiel, `5` robuste et vérifié.

| Domaine | Score (0-5) | Evidence | Gaps | Next actions |
|---|---:|---|---|---|
| Harness navigation | 4 | [documentation/index.md](index.md), [AGENTS.md](../AGENTS.md) | Besoin de revue périodique | [docs-garden routine](run/ci.md#docs-garden-routine) |
| Architecture map | 4 | [ARCHITECTURE.md](../ARCHITECTURE.md), [architecture/modules.md](architecture/modules.md) | Règles dépendances non outillées | ExecPlan pour check dépendances |
| Mechanical invariants | 3 | [scripts/check-docs.sh](../scripts/check-docs.sh) | Pas encore de check layering code-level | Ajouter outil de dépendances (TODO) |
| Testing confidence | 1 | [run/testing.md](run/testing.md) | Suite de tests package inconnue | Découvrir et encoder commande canonique |
| CI reliability | 2 | [.github/workflows/check-docs.yml](../.github/workflows/check-docs.yml) | Job informatif uniquement | Passer bloquant après stabilisation |
| Security/ACL clarity | 4 | [backend-acl.md](backend-acl.md), [architecture/invariants.md](architecture/invariants.md) | Runbook incident sécurité manquant | Ajouter `documentation/security/security.md` |

## Backlog Garbage Collection
- Consolider les sections legacy ambiguës de `readme.md` avec liens vers docs canoniques.
- Encoder un check d'architecture de dépendances (optionnel stack).
- Clarifier la commande de tests de référence du package ou du mono-repo parent.

## Liens ExecPlans
- Actifs: [documentation/exec-plans/active/README.md](exec-plans/active/README.md)
- Terminés: [documentation/exec-plans/completed/README.md](exec-plans/completed/README.md)
