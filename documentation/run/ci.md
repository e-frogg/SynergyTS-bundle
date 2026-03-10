# CI

## Jobs connus dans ce package
- `check-docs` via `.github/workflows/check-docs.yml`.

## Reproduire localement
- `bash scripts/check-docs.sh`

## Politique actuelle
- Compat legacy: job en mode informatif (`continue-on-error: true`) pour ne pas casser les pipelines existants.
- Cible: rendre bloquant après stabilisation des liens/docs.

## En cas d'échec
1. Lire le message pédagogique du script.
2. Corriger le fichier manquant/lien cassé.
3. Rejouer `bash scripts/check-docs.sh`.

## Docs-garden routine
Rythme recommandé: hebdomadaire ou mensuel.
- Commande: `bash scripts/docs-garden.sh`
- Objectif:
  - détecter liens cassés,
  - détecter docs potentiellement orphelines,
  - pousser un mini lot de maintenance.
