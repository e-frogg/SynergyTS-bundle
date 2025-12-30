<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

use Symfony\Component\HttpFoundation\ParameterBag;

class CriteriaParser
{
    public function parse(ParameterBag $body): Criteria
    {
        $criteria = new Criteria(
            filters: $body->get('filters', []),
            orderBy: $body->get('orderBy'),
            limit: $body->get('limit'),
            offset: $body->get('offset'),
            totalCountNeeded: $body->getBoolean('totalCount')
        );

        foreach ($body->get('associations', []) as $associationName => $association) {
            $criteria->addAssociation($associationName, $this->parse(new ParameterBag($association)));
        }

        return $criteria;
    }
}
