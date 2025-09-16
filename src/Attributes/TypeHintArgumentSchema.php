<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use Onekone\Lore\Attributes\TraitSchemas\PhpStanTrait;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class TypeHintArgumentSchema extends Schema
{
    use PhpStanTrait;

    public $x = [];

    public function __construct(?string $class = null, ?string $method = null, ?string $argument = null, protected array $refs = [], string $schema = null)
    {
        if (!$class && !$method && !$argument) {
            return parent::__construct(schema: $schema ?: 'todo_' . md5(random_bytes(50)), x: ['__undefined_class__' => true]);
        }

        $this->figureItOut($class, $method, $argument, $this->refs, $schema);

        return $this;
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        /** Since refs are still there, assuming that skipped out parsing this schema during initial run */
        if ($this->x['__undefined_class__'] ?? false) {
            $c = $this->_context;
            unset($this->x['__undefined_class__']);

            $this->render($this->_context->reflection_method->getDocComment(),
                'param',
                fn($i) => $i->parameterName == '$' . $this->_context->argument,
                "/** \n */ @param {$this->_context->reflection_argument->getType()} \${$this->_context->reflection_argument->getName()} \n **/"
            );
            $this->schema = implode('_', [$c->class, $c->method, $c->argument]);
        }

        return parent::validate($stack, $skip, $ref, $context);
    }

    protected function figureItOut(string $class, string $method, string $argument, array $refs = [], string $schema = null)
    {
        $reflectC = new \ReflectionClass($class);
        $reflectM = $reflectC->getMethod($method);
        $reflectA = null;
        $reflectAs = $reflectM->getParameters();
        foreach ($reflectAs as $a) {
            if ($a->getName() == $argument) {
                $reflectA = $a;
            }
        }

        $this->render($this->_context->reflection_method->getDocComment(),
            'param',
            fn($i) => $i->parameterName == '$' . $argument,
            "/** \n */ @param {$reflectA->getType()} \${$reflectA->getName()} \n **/"
        );
        $this->schema = implode('_', [$class, $method, $argument]);

        return $this;
    }
}
