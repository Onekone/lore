<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAa;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\XmlContent;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ItemResponse extends OA\Response
{
    public function __construct(
        string|object|null                                     $ref = null,
        int|string|null                                        $response = 200,
        ?string                                                $description = 'Успешно',
        ?array                                                 $headers = null,
        MediaType|JsonContent|XmlContent|Attachable|array|null $content = null,
        ?array                                                 $links = null,
        // annotation
        ?array                                                 $x = null,
        ?array                                                 $attachables = null,
        string                                                 $c = null,
    )
    {
        if (is_string($c) && $content == null) {
            $content = new OAa\JsonContent(
                ref: $c
            );
        }

        parent::__construct([
            'ref' => $ref ?? Generator::UNDEFINED,
            'response' => $response ?? Generator::UNDEFINED,
            'description' => $description ?? Generator::UNDEFINED,
            'x' => $x ?? Generator::UNDEFINED,
            'value' => $this->combine($headers, $content, $links, $attachables),
        ]);
    }
}
