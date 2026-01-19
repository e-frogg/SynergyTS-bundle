## Checklist dev
- [ ] documentation [doc](documentation/index.md) 
- [ ] tests
- [ ] ACL lecture
- [ ] DataLoader : event avec main entities (search)
- [ ] fichier de config yaml
- [ ] bundles github
- [ ] trouver un nom


# installation
## backEnd
create a file `config/routes/synergy.yaml` with the following content:
```yaml
synergy:
    resource: "@SynergyBundle/Resources/config/routes.yaml"
    prefix: /synergy
```

create a config file `config/packages/synergy.yaml` with the following content:
```yaml
synergy:
    twitter:
        client_id: 123
        client_secret: 'YOUR'
```


a tag `synergy.entity` will automatically be added to the entity when adding an attribute to the entity
```php
<?php

namespace App\Entity;

use Efrogg\Synergy\Entity\AbstractSynergyEntity;

#[ORM\Entity()]
#[SynergyEntity()]
class MyEntity implements SynergyEntityInterface
{
    // [...]
```

All entityRepositories must implement `SynergyEntityRepositoryInterface`
The tag `synergy.entity_repository` will automatically be added to the entityRepository when adding an attribute to the repository
```php
namespace App\Repository;

use App\Entity\MyEntity;
use Doctrine\Persistence\ManagerRegistry;
use Efrogg\Synergy\Entity\SynergyEntityRepository;

/**
 * @extends SynergyEntityRepository<MyEntity>
 */
class MyEntityRepository extends SynergyEntityRepository
{
```

### Grants
By default, all entities are locked. You must add a grant to the entity to allow access to the entity.

file `services.yaml` : 

Todo : passer dans la config yaml
```yaml
parameters:
    synergy.grants.actions:
        read: false
        create: false
        update: false
        delete: false
    synergy.grants.entity:
        MyEntity:
            read: true
```

Or you can manage grants using an eventListener. The event triggered are : 
* `AclEntityGrantEvent` to define entity level grants
* `AclClassGrantEvent` to define class level grants


## FrontEnd
```json
{
    "require": {
        "Synergy": "dev-master"
    }
}
```

```javascript
