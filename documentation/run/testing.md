# Testing & Validation

## Stratégie
- Ce package dispose surtout de contrôles statiques/lint à ce stade.
- Les tests fonctionnels sont principalement attendus dans l'app Symfony consommatrice.

## Commandes canoniques
- Style:
  - `php ../../../vendor/bin/php-cs-fixer fix --dry-run --diff`
- Analyse statique:
  - `php ../../../vendor/bin/phpstan analyse -c phpstan.neon`
- Harness docs:
  - `bash scripts/check-docs.sh`

## Validation PR (minimum)
1. Exécuter les 3 commandes ci-dessus.
2. Joindre preuves de sortie dans la PR.
3. Si changement d'API/ACL: ajouter preuve de comportement avant/après.

## Ajouter un test
- Unknown pour ce package isolé.
- Méthode de découverte:
  - `find . -maxdepth 3 -type d -name tests`
  - `rg -n "phpunit|pest|behat" .`
- Une fois découverte confirmée, mettre à jour ce fichier avec la commande officielle.
