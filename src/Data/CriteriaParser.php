<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

use Symfony\Component\HttpFoundation\ParameterBag;

class CriteriaParser
{

    public function parse(ParameterBag $body): Criteria
    {
        $criteria = new Criteria($body->get('filters', []), $body->get('orderBy'), $body->get('limit'), $body->get('offset'), $body->get('autoSync', false));

        foreach ($body->get('associations', []) as $associationName => $association) {
            $criteria->addAssociation($associationName, $this->parse(new ParameterBag($association)));
        }
        return $criteria;
    }
}
