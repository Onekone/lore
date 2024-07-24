<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Onekone\Lore\Attributes\TraitSchemas\RulesSchemaBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class FormRequest extends OA\RequestBody
{
    use RulesSchemaBuilder;

    /**
     * @param class-string $class
     * @param string|null $request
     */
    public function __construct(string $class, string $request = null)
    {
        $request = $request ?: str_replace(['\\', '/'], '__', $class);

        return parent::__construct(
            ref: Generator::UNDEFINED,
            request: $request,
            description: $description ?? Generator::UNDEFINED,
            required: Generator::UNDEFINED,
            content: new JsonValidatorSchema($class),
        );
    }
}
