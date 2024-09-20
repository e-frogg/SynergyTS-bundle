## Checklist dev
- [ ] documentation [doc](documentation/index.md) 
- [ ] tests
- [ ] ACL lecture
- [ ] DataLoader : event avec main entities (search)
- [ ] fichier de config yaml
- [ ] bundles github
- [x] trouver un nom : synergy
- [ ] faire une démo]


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

### Entities
All entities must implement be declared to the ... through a tag in the service declaration
```yaml
services:
    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            # - '../src/Entity/' # REMOVE THIS LINE
            - '../src/Kernel.php'
```

a tag `synergy.entity` will automatically be added to the entity when adding an attribute to the entyty
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
