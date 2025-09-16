<?php

namespace Onekone\Lore\Attributes;

use Onekone\Lore\Attributes\TraitSchemas\PhpStanTrait;
use OpenApi\Attributes\Schema;

abstract class AbstractTypeHintSchema extends Schema
{
    use PhpStanTrait;

    public $x = [];

    public function __construct(string $class = null, string $method = null, ?string $argument = null, protected array $refs = [], string $schema = null)
    {
        if (!$class && !$method) {
            return parent::__construct(schema: $schema ?: 'todo_' . md5(random_bytes(50)), x: []);
        }

        return $this->figureItOut($class, $method, $this->refs, $schema);
    }

    abstract protected function figureItOut(string $class = null, string $method = null, array $refs = [], string $schema = null);

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        if ($this->x['__undefined_class__'] ?? false) {
            $c = $this->_context;

            unset($this->x['__undefined_class__']);

            $this->render($this->_context->reflection_method, $this->_context->argument);

            $this->schema = implode('_', [$c->class, $c->method, $c->argument]);
        }

        return parent::validate($stack, $skip, $ref, $context);
    }

    abstract protected function __which($lexer, $constExprParser, $typeParser, $phpDocParser, $tokens, $phpDocNode, array|object $extras = []);
}
