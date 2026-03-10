# Local Development

## Prérequis
- PHP `>= 8.4` (vu dans `composer.json`).
- Dépendances Composer disponibles dans le repo parent (bootstrap `../../../vendor/autoload.php`).

## Setup
1. Depuis ce dossier:
   - `composer validate --no-check-publish`
2. Vérifier outils:
   - `test -f ../../../vendor/bin/phpstan && echo ok`
   - `test -f ../../../vendor/bin/php-cs-fixer && echo ok`

## Run (bundle context)
Ce package est un bundle Symfony, pas une app autonome.
- Intégration routes côté app consommatrice: `src/Resources/config/routes.yaml`.
- Config services/grants: `config/services.yaml`.

## Variables d'environnement
- `MERCURE_ENABLED` (bool) via `config/services.yaml`.

## Seed data
- Unknown au niveau bundle (dépend de l'app consommatrice et de ses entités Doctrine).

## Troubleshooting
- `phpstan` échoue sur autoload: vérifier `../../../vendor/autoload.php`.
- ACL semble inactive: vérifier `AclContext` et subscribers projet (voir `backend-acl.md`).
- Mercure non publié: vérifier `MERCURE_ENABLED` + wiring collector/hub.
