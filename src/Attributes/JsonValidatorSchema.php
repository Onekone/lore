<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use Onekone\Lore\Attributes\TraitSchemas\RulesSchemaBuilder;
use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Discriminator;
use OpenApi\Attributes\ExternalDocumentation;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Xml;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class JsonValidatorSchema extends OA\JsonContent
{
    use RulesSchemaBuilder;

    /**
     * @param class-string $class
     */
    public function __construct(string $class)
    {
        $properties = $this->buildProperties($this->parse($class), $this);

        parent::__construct(properties: $properties);
    }
}
