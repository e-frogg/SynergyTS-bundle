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

## FrontEnd
```json
{
    "require": {
        "Synergy": "dev-master"
    }
}
```

```javascript
