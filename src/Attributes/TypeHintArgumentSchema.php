<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use Onekone\Lore\Attributes\TraitSchemas\PhpStanTrait;
use OpenApi\Attributes\{Schema};

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class TypeHintArgumentSchema extends Schema
{
    use PhpStanTrait;

    public $x = [];

    /**
     * Build a schema off "@param" annotation for a method inside a class
     *
     * Description will be text attached to `return` annotation
     *
     * `$class`,`$method`,`$argument` can be `null`, but then it will attempt to guess what it's been attached to during validation
     *
     * @param ?class-string $class Fully qualified class name (FQCN) of a target class
     * @param ?string $method Name of a method within
     * @param ?string $argument Name of argument
     * @param array<class-string, null|string|class-string> $refs Map of classes, with keys as FQCN and values being either refs
     *      to schemas (including also FQCN of classes that define those schemas), or null for generic object.
     * @param string|null $schema Name of a schema
     * @return $this
     * @throws \ReflectionException
     */
    public function __construct(?string $class = null, ?string $method = null, ?string $argument = null, protected array $refs = [], string $schema = null)
    {
        if (!$class && !$method && !$argument) {
            return parent::__construct(schema: $schema ?: 'todo_' . md5(random_bytes(50)), x: ['__undefined_class__' => true]);
        }

       return $this->figureItOut($class,$method,$argument,$this->refs,$schema);
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        /** Since refs are still there, assuming that skipped out parsing this schema during initial run */
        if ($this->x['__undefined_class__'] ?? false) {
            $c = $this->_context;

            unset($this->x['__undefined_class__']);

            $this->render($this->_context->reflection_method, $this->_context->argument);

            $this->schema = implode('_', [$c->class, $c->method, $c->argument]);
        }

        return parent::validate($stack, $skip, $ref, $context);
    }

    /**
     * @param string|null $class
     * @param string|null $method
     * @param array $refs
     * @param string|null $schema
     * @return void
     * @throws \ReflectionException
     */
    protected function figureItOut(string $class = null, string $method = null, string $argument = null, array $refs = [], string $schema = null)
    {
        $reflectC = new \ReflectionClass($class);
        $reflectM = $this?->_context->reflection_method ?: $reflectC->getMethod($method);

        $this->render($reflectM, $argument);

        return $this;
    }
}
