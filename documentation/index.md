# Synergy Harness Index

Ce dossier est la base de connaissances opérationnelle pour humains et agents.

## Parcours 1: Nouveau dans le repo
1. Lire [AGENTS.md](../AGENTS.md)
2. Lire [ARCHITECTURE.md](../ARCHITECTURE.md)
3. Lire [documentation/run/local-dev.md](run/local-dev.md)
4. Lire [documentation/run/testing.md](run/testing.md)

## Parcours 2: Contribution rapide (change + PR)
1. Identifier la zone via [documentation/architecture/modules.md](architecture/modules.md)
2. Vérifier les invariants via [documentation/architecture/invariants.md](architecture/invariants.md)
3. Exécuter les commandes canoniques ([run/testing.md](run/testing.md))
4. Ouvrir PR avec preuve selon [documentation/merge-policy.md](merge-policy.md)

## Parcours 3: Investigation / Debug
1. Reproduire localement: [documentation/run/local-dev.md](run/local-dev.md)
2. Vérifier pipeline/checks: [documentation/run/ci.md](run/ci.md)
3. Références produit/tech:
- [backend-acl.md](backend-acl.md)
- [search-api.md](search-api.md)
- [globals.md](globals.md)

## Sources de vérité
- Code source `src/`
- Config DI: `config/services.yaml`
- Checks mécaniques: `scripts/check-docs.sh`, `scripts/docs-garden.sh`
- CI harness: `.github/workflows/check-docs.yml`

## Glossaire minimal
- `Synergy entity`: entité exposée via `SynergyEntityInterface`.
- `ACL class-level`: autorisation au niveau classe (`AclClassGrantEvent`).
- `ACL entity-level`: autorisation au niveau instance (`AclEntityGrantEvent`).
- `Mercure action`: événement de diffusion temps réel sur topic.

## Unknowns (et comment lever)
- Couverture de tests automatisés: **Unknown**.
  - Lever via: `find . -maxdepth 3 -type d -name tests` et `rg -n "phpunit|pest|behat" .`
- Politique CI globale du mono-repo parent: **Unknown**.
  - Lever via: inspecter les workflows/runners au niveau racine du repo parent.
- SLO/SLI runtime du bundle en production: **Unknown**.
  - Lever via: observabilité projet consommateur (logs/metrics/traces).

## Liens clés harness
- [core-beliefs.md](core-beliefs.md)
- [merge-policy.md](merge-policy.md)
- [leverage-log.md](leverage-log.md)
- [quality-score.md](quality-score.md)
- [harness/ASSESSMENT.md](harness/ASSESSMENT.md)
- [run/local-dev.md](run/local-dev.md)
- [run/testing.md](run/testing.md)
- [run/ci.md](run/ci.md)
