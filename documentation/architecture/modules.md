# Modules Map

## Bounded Areas
- `Controller`: endpoints CRUD/search (`src/Controller/Api/CrudController.php`).
- `Acl`: décision d'accès (`src/Acl/*`, `src/Event/Acl*`, `src/EventListener/EntityAclListener.php`).
- `Data`: parsing criteria + query builder + result wrapping (`src/Data/*`).
- `Entity/Mapping`: hydration + conventions interface/tags (`src/Entity/*`, `src/Mapping/*`).
- `Serializer`: normalisation entité/collection (`src/Serializer/*`).
- `Mercure`: action model + collector + dispatch (`src/Mercure/*`, listeners).
- `DependencyInjection`: extension/config/compiler passes (`src/DependencyInjection/*`).

## Main Dependency Directions
- Controller dépend de Data/Entity/Helper/Acl.
- Data dépend de Acl/Helper/Event/Doctrine.
- Event listeners orchestrent Acl/Mercure.
- Mercure dépend des events et de Symfony Mercure.

## Sensitive Zones
- ACL (`AclManager`, listeners doctrine): sécurité d'accès.
- Data filters/query builder: risques de requêtes incorrectes/coûteuses.
- Mercure dispatch: cohérence temps réel et charge.
