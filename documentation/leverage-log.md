# Leverage Log

Chaque friction agent doit devenir un levier structurel cumulatif.

## Entrée type
- Date:
- Symptom:
- Root cause:
- Fix encoded (doc/script/lint):
- Verification (command/CI):

---

## 2026-03-10 - Bootstrap Harness v2
- Date: 2026-03-10
- Symptom: navigation documentaire dispersée, pas de garde-fou docs, pas de standard ExecPlan.
- Root cause: absence de système harness versionné (connaissance + contraintes + feedback loop).
- Fix encoded: `AGENTS.md`, `ARCHITECTURE.md`, `PLANS.md`, index/runbooks, `scripts/check-docs.sh`, workflow CI.
- Verification: `bash scripts/check-docs.sh`.

## 2026-03-10 - Unknown tests automation
- Date: 2026-03-10
- Symptom: aucune suite de tests locale explicitement déclarée dans ce package.
- Root cause: conventions de tests potentiellement portées par le repo parent.
- Fix encoded: section Unknowns + commandes de découverte dans `documentation/index.md` et `documentation/run/testing.md`.
- Verification: `rg -n "phpunit|pest|behat" .`.
