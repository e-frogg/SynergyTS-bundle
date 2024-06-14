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

## FrontEnd
```json
{
    "require": {
        "Synergy": "dev-master"
    }
}
```

```javascript
