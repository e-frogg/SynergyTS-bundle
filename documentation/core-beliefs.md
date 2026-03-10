# Core Beliefs (Harness v2)

Principes directeurs: chaque principe vital doit être encodé en vérification mécanique ou routine explicite.

## 1) Validation aux frontières
- Why: les payloads externes sont la principale source d'incohérence.
- How to enforce:
  - Validation/parsing centralisés (`CriteriaParser`, `JsonRequestTrait`).
  - Ajouter des tests ciblés quand de nouveaux filtres/types sont introduits.
- Example in repo:
  - [src/Controller/Trait/JsonRequestTrait.php](../src/Controller/Trait/JsonRequestTrait.php)
  - [src/Data/CriteriaParser.php](../src/Data/CriteriaParser.php)

## 2) ACL deny-by-default
- Why: éviter l'exposition accidentelle de données.
- How to enforce:
  - `config/services.yaml` garde `read/create/update/delete=false` par défaut.
  - Vérification dans listeners Doctrine via `AclManager`.
- Example in repo:
  - [config/services.yaml](../config/services.yaml)
  - [src/EventListener/EntityAclListener.php](../src/EventListener/EntityAclListener.php)
  - [src/Acl/AclManager.php](../src/Acl/AclManager.php)

## 3) Pas de bypass de validation PR
- Why: la vitesse sans signal fiable augmente le risque de régression.
- How to enforce:
  - Exécuter `phpstan`, `php-cs-fixer --dry-run`, `check-docs` avant PR.
  - CI `check-docs` systématique (informatif au départ).
- Example in repo:
  - [documentation/run/testing.md](run/testing.md)
  - [.github/workflows/check-docs.yml](../.github/workflows/check-docs.yml)

## 4) Connaissance versionnée in-repo
- Why: réduire la dépendance au tribal knowledge.
- How to enforce:
  - Toute règle opérationnelle est documentée ici avec lien vers check/script.
  - `check-docs` vérifie présence et liens des fichiers clés.
- Example in repo:
  - [scripts/check-docs.sh](../scripts/check-docs.sh)
  - [documentation/index.md](index.md)

## 5) Feedback récurrent => invariant
- Why: un incident répété est un gap de capacité.
- How to enforce:
  - Enregistrer dans `leverage-log.md`.
  - Encoder le correctif en doc/script/lint + preuve de vérification.
- Example in repo:
  - [documentation/leverage-log.md](leverage-log.md)
  - [scripts/docs-garden.sh](../scripts/docs-garden.sh)
