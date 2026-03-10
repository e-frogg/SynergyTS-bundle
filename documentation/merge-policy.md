# Merge Policy (Agent Throughput)

## Doctrine
- PR petites et courtes (scope explicite, diff lisible).
- Gates minimales mais réelles: style, analyse statique, doc checks.
- Fix-forward par défaut: corriger dans une PR suivante plutôt que bloquer longtemps.

## Flakiness
- Rerun une fois si échec non déterministe.
- Si flake confirmé: merger avec risque contrôlé + ouvrir follow-up PR immédiatement.
- Consigner le pattern dans [leverage-log.md](leverage-log.md).

## Housekeeping PR
- Peut être auto-mergeable si:
  - revue < 1 minute,
  - aucun changement fonctionnel,
  - checks verts (ou check informatif explicitement accepté).

## Stop The Line (pas de merge)
- Risque sécurité/auth/ACL non résolu.
- Risque perte de données.
- Risque compliance/légal.
- Changement cassant sans plan de migration approuvé.

## Evidence PR minimale
- Commandes exécutées.
- Résultat checks/tests.
- Risques et rollback.
- Liens vers docs/decisions si impact architecture.
