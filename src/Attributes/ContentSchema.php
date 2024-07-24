<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Discriminator;
use OpenApi\Attributes\ExternalDocumentation;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Schema;
use OpenApi\Attributes\Xml;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ContentSchema extends Schema
{
    /**
     * Reference a schema on Response or RequestBody
     */
    public function __construct(object|string|null $ref = null, ?string $schema = null, ?string $title = null, ?string $description = null, ?int $maxProperties = null, ?int $minProperties = null, ?array $required = null, ?array $properties = null, array|string|null $type = null, ?string $format = null, ?Items $items = null, ?string $collectionFormat = null, mixed $default = Generator::UNDEFINED, $maximum = null, float|bool|int|null $exclusiveMaximum = null, $minimum = null, float|bool|int|null $exclusiveMinimum = null, ?int $maxLength = null, ?int $minLength = null, ?int $maxItems = null, ?int $minItems = null, ?bool $uniqueItems = null, ?string $pattern = null, array|string|null $enum = null, ?Discriminator $discriminator = null, ?bool $readOnly = null, ?bool $writeOnly = null, ?Xml $xml = null, ?ExternalDocumentation $externalDocs = null, mixed $example = Generator::UNDEFINED, ?array $examples = null, ?bool $nullable = null, ?bool $deprecated = null, ?array $allOf = null, ?array $anyOf = null, ?array $oneOf = null, AdditionalProperties|bool|null $additionalProperties = null, mixed $const = Generator::UNDEFINED, ?array $x = null, ?array $attachables = null)
    {
        if (is_string($ref)) {
            $ref .= '/content/application~1json/schema';
        }

        parent::__construct($ref, $schema, $title, $description, $maxProperties, $minProperties, $required, $properties, $type, $format, $items, $collectionFormat, $default, $maximum, $exclusiveMaximum, $minimum, $exclusiveMinimum, $maxLength, $minLength, $maxItems, $minItems, $uniqueItems, $pattern, $enum, $discriminator, $readOnly, $writeOnly, $xml, $externalDocs, $example, $examples, $nullable, $deprecated, $allOf, $anyOf, $oneOf, $additionalProperties, $const, $x, $attachables);
    }
}
