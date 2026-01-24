# API Search - Synergy

Recherche avancée d'entités via l'API Synergy.

## Endpoint

```
POST /synergy/entity/search/{entityName}
```

- **entityName** : Nom de l'entité à rechercher (correspond au nom défini dans Synergy)
- **Content-Type** : `application/json`

---

## Structure de la requête

```json
{
  "filters": { ... },
  "orderBy": { ... },
  "limit": 10,
  "offset": 0,
  "totalCount": true,
  "associations": { ... }
}
```

| Paramètre      | Type    | Défaut  | Description                                           |
|----------------|---------|---------|-------------------------------------------------------|
| `filters`      | object  | `{}`    | Filtres à appliquer sur les entités                   |
| `orderBy`      | object  | `null`  | Tri des résultats (`{ "field": "asc|desc" }`)         |
| `limit`        | integer | `null`  | Nombre maximum de résultats                           |
| `offset`       | integer | `0`     | Décalage pour la pagination                           |
| `totalCount`   | boolean | `false` | Retourne le nombre total d'éléments (sans pagination) |
| `associations` | object  | `{}`    | Relations à charger avec les résultats                |

---

## Filtres

### Filtre simple (égalité)

```json
{
  "filters": {
    "status": "active",
    "categoryId": 5
  }
}
```

Génère : `WHERE status = 'active' AND categoryId = 5`

### Filtre IN (valeurs multiples)

```json
{
  "filters": {
    "status": ["active", "pending", "draft"]
  }
}
```

Génère : `WHERE status IN ('active', 'pending', 'draft')`

---

## Types de filtres avancés

Utilisez un objet avec une clé `type` pour des filtres avancés :

```json
{
  "filters": {
    "field": {
      "type": "filter_type",
      "value": "..."
    }
  }
}
```

### Liste des types de filtres

| Type                   | Description                       | Paramètres                          | Exemple                                              |
|------------------------|-----------------------------------|-------------------------------------|------------------------------------------------------|
| `equals`               | Égalité stricte                   | `value`: any                        | `{"type": "equals", "value": 42}`                    |
| `not_equals`           | Différent de                      | `value`: any                        | `{"type": "not_equals", "value": 42}`                |
| `null`                 | Est NULL                          | aucun                               | `{"type": "null"}`                                   |
| `not_null`             | N'est pas NULL                    | aucun                               | `{"type": "not_null"}`                               |
| `in` / `equals_any`    | Dans une liste de valeurs         | `value`: array                      | `{"type": "in", "value": [1, 2, 3]}`                 |
| `not_in` / `not_equals_any` | Pas dans une liste           | `value`: array                      | `{"type": "not_in", "value": [1, 2, 3]}`             |
| `contains`             | Contient (LIKE %...%)             | `value`: string                     | `{"type": "contains", "value": "search"}`            |
| `starts_with`          | Commence par (LIKE ...%)          | `value`: string                     | `{"type": "starts_with", "value": "prefix"}`         |
| `ends_with`            | Termine par (LIKE %...)           | `value`: string                     | `{"type": "ends_with", "value": "suffix"}`           |
| `greater_than`         | Supérieur à (>)                   | `value`: int\|float                 | `{"type": "greater_than", "value": 100}`             |
| `less_than`            | Inférieur à (<)                   | `value`: int\|float                 | `{"type": "less_than", "value": 50}`                 |
| `greater_than_or_equal`| Supérieur ou égal (>=)            | `value`: int\|float                 | `{"type": "greater_than_or_equal", "value": 100}`    |
| `less_than_or_equal`   | Inférieur ou égal (<=)            | `value`: int\|float                 | `{"type": "less_than_or_equal", "value": 50}`        |
| `between`              | Entre deux valeurs                | `from`: int\|float, `to`: int\|float| `{"type": "between", "from": 10, "to": 100}`         |
| `and`                  | Combinaison ET sur un champ       | `filters`: array                    | Voir section [Filtres combinés](#filtres-combinés-sur-un-champ) |
| `or`                   | Combinaison OU sur un champ       | `filters`: array                    | Voir section [Filtres combinés](#filtres-combinés-sur-un-champ) |

---

## Filtres sur les relations (jointures)

Utilisez la notation pointée `relation.field` pour filtrer sur les propriétés d'une relation Doctrine :

```json
{
  "filters": {
    "type.project": 11,
    "category.name": "Electronics"
  }
}
```

Génère automatiquement un `JOIN` et filtre sur la relation.

> **Note** : L'alias de jointure est généré à partir de la première lettre du nom de la relation (ex: `type` → `t`, `project` → `p`).

---

## Filtres sur les champs JSON

Utilisez la notation avec deux-points (`:`) pour accéder aux propriétés d'un champ JSON :

```json
{
  "filters": {
    "data:reference": "ABC123",
    "metadata:config.enabled": true
  }
}
```

- `data:reference` accède à `$.reference` dans le champ `data`
- Peut être combiné avec des filtres avancés :

```json
{
  "filters": {
    "data:reference": {
      "type": "contains",
      "value": "KIOSK"
    }
  }
}
```

### Combinaison relation + champ JSON

Vous pouvez combiner la notation pointée (jointure) et la notation deux-points (JSON) :

```json
{
  "filters": {
    "category.metadata:settings.enabled": true,
    "type.config:display.color": {
      "type": "contains",
      "value": "blue"
    }
  }
}
```

- `category.metadata:settings.enabled` :
  - Joint la relation `category`
  - Accède au champ JSON `metadata`
  - Filtre sur `$.settings.enabled`

- `type.config:display.color` :
  - Joint la relation `type`
  - Accède au champ JSON `config`
  - Filtre sur `$.display.color` avec un filtre `contains`

---

## Groupement de filtres (OR / AND)

### Au niveau racine des filtres

Vous pouvez grouper plusieurs filtres avec `or` ou `and` :

```json
{
  "filters": {
    "status": "active",
    "or": [
      { "category": 1 },
      { "category": 2 },
      { "priority": "high" }
    ]
  }
}
```

Génère : `WHERE status = 'active' AND (category = 1 OR category = 2 OR priority = 'high')`

### Combinaison AND au niveau racine

```json
{
  "filters": {
    "and": [
      { "status": "active" },
      { "verified": true }
    ],
    "or": [
      { "type": 1 },
      { "type": 2 }
    ]
  }
}
```

---

## Filtres combinés sur un champ

Pour appliquer plusieurs conditions sur un même champ :

### Combinaison OR sur un champ

```json
{
  "filters": {
    "myfield": {
      "type": "or",
      "filters": [
        { "type": "equals", "value": 261 },
        { "type": "equals", "value": 262 }
      ]
    }
  }
}
```

Génère : `WHERE (type = 261 OR type = 262)`

### Combinaison AND sur un champ

```json
{
  "filters": {
    "price": {
      "type": "and",
      "filters": [
        { "type": "greater_than_or_equal", "value": 10 },
        { "type": "less_than_or_equal", "value": 100 }
      ]
    }
  }
}
```

Génère : `WHERE (price >= 10 AND price <= 100)`

> **Tip** : Pour ce cas précis, préférez le filtre `between` qui est plus lisible.

---

## Tri des résultats

```json
{
  "orderBy": {
    "createdAt": "desc",
    "name": "asc"
  }
}
```

| Direction | Description |
|-----------|-------------|
| `asc`     | Ascendant   |
| `desc`    | Descendant  |

---

## Pagination

```json
{
  "limit": 20,
  "offset": 40
}
```

Pour obtenir le nombre total de résultats (utile pour la pagination) :

```json
{
  "limit": 20,
  "offset": 0,
  "totalCount": true
}
```

La réponse contiendra un champ `totalCount` avec le nombre total d'éléments correspondant aux filtres (sans tenir compte de `limit` et `offset`).

---

## Associations (chargement de relations)

Permet de charger des entités liées en une seule requête :

```json
{
  "associations": {
    "mappings": {},
    "blocks": {},
    "blockedBy": {}
  }
}
```

Chaque clé correspond au nom de la propriété de relation Doctrine. L'objet peut contenir ses propres critères :

```json
{
  "associations": {
    "comments": {
      "filters": {
        "status": "published"
      },
      "orderBy": {
        "createdAt": "desc"
      },
      "limit": 5
    }
  }
}
```

> **Note** : L'utilisation de `limit` sur les associations entraîne des requêtes supplémentaires par entité parente (performances réduites).

---

## Exemple complet

```json
{
  "filters": {
    "type.project": 11,
    "or": [
      {
        "data:reference": {
          "type": "contains",
          "value": "KIOSK"
        }
      },
      {
        "data:reference": {
          "type": "contains",
          "value": "K17"
        }
      }
    ],
    "type": {
      "type": "or",
      "filters": [
        { "type": "equals", "value": 261 },
        { "type": "equals", "value": 262 }
      ]
    },
    "status": {
      "type": "not_null"
    },
    "priority": {
      "type": "in",
      "value": [1, 2, 3]
    }
  },
  "orderBy": {
    "id": "asc"
  },
  "limit": 10,
  "offset": 0,
  "totalCount": true,
  "associations": {
    "mappings": {},
    "blocks": {},
    "blockedBy": {}
  }
}
```

Cette requête :
1. Filtre les entités dont `type.project` = 11
2. Avec un champ JSON `data.reference` contenant "KIOSK" **OU** "K17"
3. Avec un `type` égal à 261 **OU** 262
4. Avec un `status` non NULL
5. Avec une `priority` de 1, 2 ou 3
6. Triées par `id` ascendant
7. Limitées à 10 résultats
8. Avec le comptage total activé
9. En chargeant les associations `mappings`, `blocks` et `blockedBy`
