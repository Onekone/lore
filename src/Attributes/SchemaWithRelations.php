<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SchemaWithRelations extends Schema
{
    const SELF = '!';

    public function __construct(array $relations = [], string $baseRef = '', string $refName = 'Linked')
    {
        $newSchema = [];
        $pps = [];

        $newAllOf = [
            new Schema(ref: $baseRef),
            new Schema(properties: $this->recursiveRelationProperties($relations), readOnly: true)
        ];

        $schema = array_reverse(explode('/', $baseRef))[0] . $refName;

        parent::__construct(schema: $schema, allOf: $newAllOf);
    }

    protected function recursiveRelationProperties($relations)
    {
        $pps = [];

        foreach ($relations as $key => $relation) {
            if ($key == self::SELF) {
                continue;
            }
            if (!is_array($relation)) {
                $pps[] = new Property(property: $key, ref: $relation, type: 'object', readOnly: true);
            } else {
                $pps[] = new Property(property: $key, type: 'object', readOnly: true, allOf: [
                    new Schema(ref: $relation[self::SELF]),
                    new Schema(properties: $this->recursiveRelationProperties($relation)),
                ]);
            }
        }

        return $pps;
    }
}
