<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use Onekone\Lore\Attributes\TraitSchemas\PhpStanTrait;
use OpenApi\Attributes\{Parameter, Schema};

#[Attribute(\Attribute::TARGET_PARAMETER)]
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
        if (!$class || !$method || !$argument) {
            return parent::__construct(schema: $schema ?: 'todo_'.md5(random_bytes(50)), x: []);
        }

       return $this->figureItOut($class,$method,$argument,$this->refs,$schema);
    }

    public function validate(array $stack = [], array $skip = [], string $ref = '', $context = null): bool
    {
        /** Since refs are still there, assuming that skipped out parsing this schema during initial run */
        if (isset($this->refs)) {
            $this->schema = preg_replace('[\W]','_', $this->_context->class .'@'. $this->_context->method .'_'. $this->_context->argument);
            $this->figureItOut($this->_context->namespace . '\\' . $this->_context->class, $this->_context->method, $this->_context->argument, $this->refs, $this->schema);
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
        $reflectM = $reflectC->getMethod($method);

        $this->render($reflectM->getDocComment(),$argument);

        unset($this->refs);
    }
}
