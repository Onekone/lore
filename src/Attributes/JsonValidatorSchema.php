<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use Onekone\Lore\Attributes\TraitSchemas\RulesSchemaBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class JsonValidatorSchema extends OA\JsonContent
{
    use RulesSchemaBuilder;

    /**
     * @param class-string $class
     */
    public function __construct(string $class)
    {
        $this->properties = $this->buildProperties($this->parse($class), $this);
    }
}
