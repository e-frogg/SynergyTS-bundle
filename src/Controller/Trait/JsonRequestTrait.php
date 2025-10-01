<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Controller\Trait;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

trait JsonRequestTrait
{
    /**
     * @throws \JsonException
     */
    private function extractJson(Request $request): ParameterBag
    {
        /** @var string|false|resource|null $body */
        $body = $request->getContent();
        $data = new ParameterBag();
        if (is_string($body)) {
            $data->add(json_decode($body, true, 512, JSON_THROW_ON_ERROR));
        }

        return $data;
    }
}
