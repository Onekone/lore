<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class FormRequestParameter extends OA\Parameter
{
    public function __construct(string $class, string $in = 'query', string $parameter = null)
    {
        return parent::__construct(
            parameter: $parameter,
            name: 'formRequest',
            in: $in,
            schema: new ValidatorSchema($class),
        );
    }
}
