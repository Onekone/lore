<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Schema;
use OpenApi\Attributes\XmlContent;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class FormRequestParameter extends OA\Parameter
{
    public function __construct(string $class = null, string $in = 'query', ?string $parameter = null,
        ?string $name = null,
        ?string $description = null,
        ?bool $required = null,
        ?bool $deprecated = null,
        ?bool $allowEmptyValue = null,
        string|object|null $ref = null,
        ?Schema $schema = null,
        mixed $example = Generator::UNDEFINED,
        ?array $examples = null,
        array|JsonContent|XmlContent|Attachable|null $content = null,
        ?string $style = null,
        ?bool $explode = null,
        ?bool $allowReserved = null,
        ?array $spaceDelimited = null,
        ?array $pipeDelimited = null,
        // annotation
        ?array $x = null,
        ?array $attachables = null,
    )
    {
        $x = $x ?: [];
        if (!$class && !$schema) {
            $x ['__undefined_class__'] = true;
        }

        return parent::__construct(
            parameter: $parameter,
            name: $name ?: 'formRequest',
            description: $description,
            in: $in,
            required: $required,
            deprecated: $deprecated,
            allowEmptyValue: $allowEmptyValue,
            ref: $ref,
            schema: $class ? new ValidatorSchema($class) : $schema,
            example: $example,
            examples: $examples,
            content: $content,
            style: $style,
            explode: $explode,
            allowReserved: $allowReserved,
            spaceDelimited: $spaceDelimited,
            pipeDelimited: $pipeDelimited,
            x: $x,
            attachables: $attachables,
        );
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        /** Since refs are still there, assuming that skipped out parsing this schema during initial run */
        if ($this->x['__undefined_class__'] ?? false) {
            /** @todo: need method argument from zircote/swagger-php */
            unset($this->x['__undefined_class__']);
        }


        return parent::validate($stack, $skip, $ref, $context);
    }
}
