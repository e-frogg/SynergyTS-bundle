# Synergy Backend et ACL

Cette page documente le fonctionnement backend de `Efrogg/SynergyTS-bundle` avec un focus securite.

## 1. Vue d ensemble backend

SynergyBundle expose une API generic Doctrine pour:
- lire des entites (`GET`, `POST /search`),
- creer/modifier/supprimer des entites,
- serialiser les graphes d entites pour le front Vue/TS,
- diffuser les changements en temps reel via Mercure.

### 1.1 Routing

Le bundle expose des routes attributes dans `CrudController`.
Dans Alfred, ces routes sont montees via:

```yaml
# config/routes/synergy.yaml
synergy:
    resource: "@SynergyBundle/Resources/config/routes.yaml"
    prefix: /synergy
```

Endpoints principaux:
- `GET /synergy/entity/full`
- `GET /synergy/entity/{entityName}`
- `GET /synergy/entity/{entityName}/{id}`
- `POST /synergy/entity/search/{entityName}`
- `POST /synergy/entity/{entityName}`
- `PUT /synergy/entity/{entityName}/{id}`
- `DELETE /synergy/entity/{entityName}/{id}`

### 1.2 Resolution des entites

`EntityHelper` maintient la table `entityName -> entityClass` a partir:
- des services tagges `synergy.entity` (entites implementant `SynergyEntityInterface`),
- des services tagges `synergy.entity-repository` (repositories implementant `SynergyEntityRepositoryInterface`).

Les interfaces ajoutent le tag automatiquement via `#[AutoconfigureTag(...)]`.

## 2. Flux backend principal

## 2.1 Lecture simple (GET)

`CrudController`:
1. active ACL (`AclManager::setEnabled(true)`),
2. resolve l entite via `EntityHelper`,
3. charge en repository Doctrine,
4. construit la reponse via `EntityResponseBuilder`.

## 2.2 Recherche avancee (POST /search)

`CriteriaParser` convertit le JSON entrant en objet `Criteria`:
- `filters`, `orderBy`, `limit`, `offset`,
- `associations`,
- `totalCount`.

`EntityRepositoryHelper::search()`:
1. verifie ACL de classe sur `read`,
2. construit un QueryBuilder Doctrine,
3. applique les filtres simples/complexes,
4. gere les associations recursees,
5. filtre le resultat final par ACL entite (`isEntityGranted(..., read)`),
6. retourne `SearchResult`.

## 2.3 Ecriture (create/edit/delete)

Create/Edit:
1. `CrudController` decode le JSON,
2. `SynergyEnricher` hydrate l entite (proprietes + relations via suffixe `Id`),
3. Doctrine `persist + flush`.

Delete:
1. chargement entite,
2. `remove + flush`.

Le controle ACL write est fait par listener Doctrine `EntityAclListener`:
- `prePersist` => action `create`,
- `preUpdate` => action `update`,
- `preRemove` => action `delete`.

## 2.4 Serialization backend vers front

`EntityNormalizer`:
- serialise les champs scalaires,
- transforme les relations `ManyToOne/OneToOne` en `<property>Id`,
- peut "discover" les entites reliees,
- ignore certains champs techniques.

`EntityCollectionNormalizer`:
- regroupe par type d entite,
- evite les doublons,
- applique la decouverte recursive avec limite de profondeur.

## 2.5 Temps reel Mercure

`EntityMercureListener` ecoute les events Doctrine post-persist/update/remove.
Il construit des `EntityAction` puis:
- dispatch event `MercureEntityActionEvent`,
- resolution topic(s) via subscribers (`TopicEntityDispatchEvent`),
- accumulation par topic dans `ActionCollectorInterface`,
- publication Mercure (`ActionCollector::flush`).

Dans Alfred, `SynergySubscriber` mappe les entites vers des topics projet.

## 3. ACL: modele de securite

## 3.1 Actions supportees

`AclManager::ACTION_LIST`:
- `create`
- `read`
- `update`
- `delete`

## 3.2 Niveaux de decision

Synergy combine 2 niveaux:
1. **Class-level** (`AclClassGrantEvent`): regle globale pour une classe d entite + action.
2. **Entity-level** (`AclEntityGrantEvent`): regle sur l instance d entite.

Ordre d evaluation:
1. Calcul du grant par defaut (entity override > action default > global default).
2. Dispatch event class-level.
3. Dispatch event entity-level.
4. Si refuse et mode check, leve `GrantException`.

## 3.3 Parametrage des grants par defaut

Injection via config services:

```yaml
parameters:
    synergy.grants.actions:
        read: false
        create: false
        update: false
        delete: false
    synergy.grants.entity: {}
```

`synergy.grants.entity` accepte une cle nom d entite ou FQCN (resolu par `EntityHelper`).

Exemple:

```yaml
parameters:
    synergy.grants.actions:
        read: false
        create: false
        update: false
        delete: false
    synergy.grants.entity:
        Project:
            read: true
```

## 3.4 Evenements ACL a implementer

Le projet consommateur doit implementer des subscribers/listeners pour injecter ses regles metier.

- `AclClassGrantEvent`: autoriser/refuser une action sur une classe.
- `AclEntityGrantEvent`: autoriser/refuser selon l etat de l entite courante.

API utile:
- `setGranted(bool)`
- `addViolation(string, int $httpCode = 401|403)`

## 3.5 Condition critique: contexte ACL

`AclManager` bypass tous les checks si:
- ACL non active (`enabled=false`), ou
- `AclContext::isSystemContext() === true`.

Par defaut, `AclContext` est initialise a `systemContext=true`.

Implication: tant que le projet ne bascule pas explicitement le contexte en mode "user/request" (`setSystemContext(false)`), les ACL ne s appliquent pas, meme si `setEnabled(true)` est appele.

## 4. Hardening recommande pour un projet Symfony

## 4.1 Proteger les routes Synergy au niveau Security Symfony

Minimum:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/synergy, roles: ROLE_USER }
```

## 4.2 Activer le contexte ACL user sur les requetes HTTP

Ajouter un listener/subscriber request pour passer `AclContext` a `false` sur les requetes API Synergy front.

Exemple (principe):

```php
<?php

namespace App\Security\Synergy;

use Efrogg\Synergy\Acl\AclContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class SynergyAclContextSubscriber
{
    public function __construct(private AclContext $aclContext)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 200)]
    public function onRequest(RequestEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/synergy/')) {
            $this->aclContext->setSystemContext(false);
        }
    }
}
```

## 4.3 Implementer vos regles ACL metier

Exemple class-level + entity-level:

```php
<?php

namespace App\Security\Synergy;

use App\Entity\Project;
use App\Entity\User;
use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Event\AclClassGrantEvent;
use Efrogg\Synergy\Event\AclEntityGrantEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SynergyAclSubscriber
{
    public function __construct(private Security $security)
    {
    }

    #[AsEventListener]
    public function onClassGrant(AclClassGrantEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $event->addViolation('Authentication required', AclClassGrantEvent::UNAUTHORIZED);
            return;
        }

        if ($event->getEntityClass() === Project::class && $event->getAction() === AclManager::READ) {
            $event->setGranted(true);
        }
    }

    #[AsEventListener]
    public function onEntityGrant(AclEntityGrantEvent $event): void
    {
        $entity = $event->getEntity();
        $user = $this->security->getUser();

        if ($entity instanceof Project && !$user?->hasRole('ROLE_ADMIN')) {
            // Exemple: refuser update/delete hors admin
            if (in_array($event->getAction(), [AclManager::UPDATE, AclManager::DELETE], true)) {
                $event->addViolation('Admin role required', AclEntityGrantEvent::FORBIDDEN);
            }
        }
    }
}
```

## 4.4 Durcir l ecriture de champs

Utiliser `#[WriteProtected]` sur les proprietes sensibles pour empecher leur hydration via payload Synergy (`SynergyEnricher`).

## 4.5 Tester les ACL

Couvrir au minimum:
- utilisateur anonyme => 401/403,
- utilisateur authentifie sans droits => refus read/write,
- admin => acces attendu,
- tests create/update/delete verifies via listener Doctrine ACL,
- verification des `violations` de `GrantException`.

## 5. Points d attention observes dans Alfred (etat actuel)

Ce constat decrit l etat de l integration actuelle, pas la cible de securite:
- Les endpoints Synergy sont exposes sous `/synergy/*`.
- Les ACL sont activees dans le controller (`setEnabled(true)`), mais ne seront effectives que si `AclContext` sort du mode system.
- La configuration de grants dans Alfred autorise `read: true` par defaut (`config/services.yaml`).
- Aucun subscriber ACL metier n est actuellement present dans `src/` pour `AclClassGrantEvent` / `AclEntityGrantEvent`.

## 6. Checklist integration projet

1. Restreindre firewall/access_control sur `/synergy`.
2. Basculer `AclContext` en mode user sur les requetes Synergy.
3. Definir des grants par defaut deny (read/create/update/delete=false).
4. Implementer subscribers ACL class-level et entity-level.
5. Ajouter `WriteProtected` sur les champs critiques.
6. Ajouter tests fonctionnels ACL (lecture + ecriture + suppression).
