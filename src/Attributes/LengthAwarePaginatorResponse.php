<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\Attachable;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\XmlContent;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class LengthAwarePaginatorResponse extends OA\Response
{
    /**
     * Create a length-aware paginator response object.
     *
     * Resulting response is an application/json schema of Laravel paginator with $items
     */
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
        string                                                 $items = null,
    )
    {
        if (is_string($items) && $content == null) {
            $content = new LengthAwarePaginatorJsonContent(
                ref: $items
            );
        }


        parent::__construct(
            ref: $ref,
            response: $response,
            description: $description,
            headers: $headers,
            content: $content,
            links: $links,
            x: $x,
            attachables: $attachables
        );
    }
}
