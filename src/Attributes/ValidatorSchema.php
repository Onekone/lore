<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use Onekone\Lore\Attributes\TraitSchemas\RulesSchemaBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ValidatorSchema extends OA\Schema
{
    use RulesSchemaBuilder;

    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $schema = null, string $description = null, string $title = null)
    {
        parent::__construct(schema: $schema, title: $title, description: $description, properties: $this->buildProperties($this->parse($class)));
    }
}
