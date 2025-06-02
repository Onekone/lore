<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Discriminator;
use OpenApi\Attributes\ExternalDocumentation;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Xml;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LengthAwarePaginatorJsonContent extends JsonContent
{
    public function __construct(
        string|object|null $ref = null,
        ?string $schema = null,
        ?string $title = null,
        ?string $description = null,
        ?int $maxProperties = null,
        ?int $minProperties = null,
        ?array $required = null,
        ?array $properties = null,
        string|array|null $type = null,
        ?string $format = null,
        ?Items $items = null,
        ?string $collectionFormat = null,
        mixed $default = Generator::UNDEFINED,
                           $maximum = null,
        bool|int|float|null $exclusiveMaximum = null,
        $minimum = null,
        bool|int|float|null $exclusiveMinimum = null,
        ?int $maxLength = null,
        ?int $minLength = null,
        ?int $maxItems = null,
        ?int $minItems = null,
        ?bool $uniqueItems = null,
        ?string $pattern = null,
        array|string|null $enum = null,
        ?Discriminator $discriminator = null,
        ?bool $readOnly = null,
        ?bool $writeOnly = null,
        ?Xml $xml = null,
        ?ExternalDocumentation $externalDocs = null,
        mixed $example = Generator::UNDEFINED,
        ?array $examples = null,
        ?bool $nullable = null,
        ?bool $deprecated = null,
        ?array $allOf = null,
        ?array $anyOf = null,
        ?array $oneOf = null,
        AdditionalProperties|bool|null $additionalProperties = null,
        mixed $const = Generator::UNDEFINED,
        // annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(examples: $examples, ref: $ref,
            schema: $schema,
            title: $title,
            description: $description,
            maxProperties: $maxProperties,
            minProperties: $minProperties,
            required: $required,
            properties: $properties ?: [
                new Property("links", type: "array", items: new Items(properties: [
                    new Property('url', type: 'string', format: 'uri'),
                    new Property('label', type: 'string'),
                    new Property('active', type: 'boolean'),
                ])),
                new Property("current_page", type: "integer"),
                new Property("data", type: "array", items: new Items(ref: $ref)),
                new Property("first_page_url", type: "string", format: 'uri',),
                new Property("from", type: "integer", nullable: true,),
                new Property("last_page", type: "integer"),

                new Property("last_page_url", type: "string", format: 'uri', nullable: true),
                new Property("next_page_url", type: "string", format: 'uri', nullable: true),
                new Property("path", type: "string", format: 'uri',),
                new Property("per_page", type: "integer"),
                new Property("prev_page_url", type: "string", format: 'uri',),
                new Property("to", type: "integer", nullable: true),
                new Property("total", type: "integer"),
            ],
            type: $type,
            format: $format,
            items: $items,
            collectionFormat: $collectionFormat,
            default: $default,
            maximum: $maximum,
            exclusiveMaximum: $exclusiveMaximum,
            minimum: $minimum,
            exclusiveMinimum: $exclusiveMinimum,
            maxLength: $maxLength,
            minLength: $minLength,
            maxItems: $maxItems,
            minItems: $minItems,
            const: $const,
            uniqueItems: $uniqueItems,
            pattern: $pattern,
            enum: $enum,
            discriminator: $discriminator,
            readOnly: $readOnly,
            writeOnly: $writeOnly,
            xml: $xml,
            externalDocs: $externalDocs,
            example: $example,
            nullable: $nullable,
            deprecated: $deprecated,
            allOf: $allOf,
            anyOf: $anyOf,
            oneOf: $oneOf,
            additionalProperties: $additionalProperties,
            x: $x,
            attachables: $attachables,
        );
    }
}
