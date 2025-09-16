<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use Onekone\Lore\Attributes\TraitSchemas\PhpStanTrait;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class TypeHintSchema extends Schema
{
    use PhpStanTrait;

    public $x = [];

    /**
     * Build a schema off "return" annotation for a method inside a class
     *
     * Description will be text attached to `@ return` annotation
     *
     * $class and $method can be `null`, but then it will attempt to guess what it's been attached to during validation
     *
     * @param ?class-string $class Fully qualified class name (FQCN) of a target class
     * @param ?string $method Name of a method within
     * @param array<class-string, null|string|class-string> $refs Map of classes, with keys as FQCN and values being either refs
     *      to schemas (including also FQCN of classes that define those schemas), or null for generic object.
     * @param string|null $schema Name of a schema
     * @return $this
     * @throws \ReflectionException
     */
    public function __construct(string $class = null, string $method = null, private array $refs = [], string $schema = null)
    {
        if (!$class && !$method) {
            return parent::__construct(schema: $schema ?: 'todo_'.md5(random_bytes(50)), x: []);
        }

       return $this->figureItOut($class,$method,$this->refs,$schema);
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        /** Since refs are still there, assuming that skipped out parsing this schema during initial run */
        if (isset($this->refs)) {
            $this->figureItOut($this->_context->namespace . '\\' . $this->_context->class, $this->_context->method, $this->refs, $this->schema);
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
    protected function figureItOut(string $class = null, string $method = null, array $refs = [], string $schema = null)
    {
        $reflectC = new \ReflectionClass($class);
        $reflectM = $reflectC->getMethod($method);

        $this->render($this->_context->reflection_method->getDocComment(),
            'return',
            fn($i) => true,
            "/** \n /* @return {$reflectM->getType()} \n **/"
        );
        $this->schema = implode('_', [$class, $method]);
    }
}
