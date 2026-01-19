<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Helper;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;

class TypeHelper
{
    public static function getInnerType(Type $type): Type
    {
        if ($type instanceof NullableType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof Type\BuiltinType && $innerType->isNullable()) {
                    continue;
                }

                return $innerType;
            }
        } elseif ($type instanceof Type\UnionType) {
            throw new \LogicException('Union type not supported for now in TypeHelper::getInnerType');
        }

        return $type;
    }
}
