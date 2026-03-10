# Architecture

## Purpose
`SynergyTS-bundle` is a Symfony bundle exposing generic CRUD/search APIs for Synergy entities, with ACL enforcement and Mercure-based realtime updates.

## Codemap (stable)
- HTTP/API entrypoints: `src/Controller/Api/CrudController.php`
- Access control: `src/Acl/*`, `src/EventListener/EntityAclListener.php`, `src/Event/Acl*`
- Query/search pipeline: `src/Data/*`
- Entity discovery/hydration: `src/Helper/EntityHelper.php`, `src/Entity/SynergyEnricher.php`, `src/Mapping/*`
- Serialization: `src/Serializer/*`
- Realtime dispatch: `src/Mercure/*`, `src/EventListener/EntityMercureListener.php`, `src/EventListener/WorkerSubscriber.php`
- DI and bootstrapping: `src/DependencyInjection/*`, `src/SynergyBundle.php`, `config/services.yaml`

## Flow (text diagram)
`HTTP request -> CrudController -> CriteriaParser/EntityRepositoryHelper -> ACL checks -> Doctrine -> Serializer/ResponseBuilder -> Mercure dispatcher`

## Layering Rules
- Allowed:
  - `Controller -> Data|Entity|Helper|Acl|Mercure`
  - `Data -> Acl|Entity|Helper|Event`
  - `EventListener -> Acl|Mercure|Event`
  - `Mercure -> Event`
- Forbidden (design intent):
  - `Acl -> Controller`
  - `Entity -> Controller|EventListener`
  - `Serializer -> Controller`

## Invariants
- Detailed modules: [documentation/architecture/modules.md](documentation/architecture/modules.md)
- Verifiable rules and checks: [documentation/architecture/invariants.md](documentation/architecture/invariants.md)
