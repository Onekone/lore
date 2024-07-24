<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LengthAwarePaginatorSchema extends Schema
{
    public function __construct(string $ref, string $schema)
    {
        parent::__construct(schema: $schema, properties: [
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
        ]);
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        return parent::validate($stack, $skip, $ref, $context); // TODO: Change the autogenerated stub
    }
}
