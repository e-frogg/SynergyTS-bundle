# SynergyTS-bundle - Documentation backend

## Documents disponibles

- [Backend + ACL](backend-acl.md)
- [API Search](search-api.md)

## Perimetre

Cette documentation couvre le backend Symfony/Doctrine de Synergy:
- exposition API CRUD/search,
- normalisation des entites,
- diffusion Mercure,
- securite ACL et recommandations de hardening.

## Demarrage rapide

1. Monter les routes du bundle sous un prefixe (ex: `/synergy`).
2. Proteger ce prefixe avec `access_control` Symfony.
3. Definir des grants ACL deny-by-default.
4. Implementer des subscribers `AclClassGrantEvent` + `AclEntityGrantEvent`.
5. Basculer `AclContext` hors mode system pour les requetes Synergy frontend.
