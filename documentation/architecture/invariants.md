# Architecture Invariants

## Invariants encodés aujourd'hui
1. Entités exposées Synergy implémentent `SynergyEntityInterface`.
- Why this matters: discovery + ACL + serialization reposent dessus.
- Where enforced: runtime dans `EntityRepositoryHelper`, autoconfigure tag interface.
- Example: [src/Entity/SynergyEntityInterface.php](../../src/Entity/SynergyEntityInterface.php)

2. Repositories Synergy implémentent `SynergyEntityRepositoryInterface` et étendent `ServiceEntityRepository`.
- Why this matters: mapping `entityName -> entityClass` fiable.
- Where enforced: `EntityHelper::setEntityRepositories` lève une exception sinon.
- Example: [src/Helper/EntityHelper.php](../../src/Helper/EntityHelper.php)

3. ACL write est vérifiée sur événements Doctrine `prePersist/preUpdate/preRemove`.
- Why this matters: empêche les bypass d'autorisation en écriture.
- Where enforced: `EntityAclListener` + `AclManager`.
- Example: [src/EventListener/EntityAclListener.php](../../src/EventListener/EntityAclListener.php)

4. Garde-fous docs harness présents et liés.
- Why this matters: agents reproductibles, moins de dérive documentaire.
- Where enforced: `scripts/check-docs.sh`.
- Example: [scripts/check-docs.sh](../../scripts/check-docs.sh)

## Invariants à encoder (TODO concret)
- Check de dépendances interdites entre namespaces (Controller/Data/Acl/etc.).
- Check de fraîcheur ownership doc (last reviewed) si cadence équipe stable.
