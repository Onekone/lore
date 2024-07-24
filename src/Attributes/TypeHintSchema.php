<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use OpenApi\Attributes\Schema;
use Onekone\Lore\Attributes\TraitSchemas\ParsePsalmStringTrait;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class TypeHintSchema extends Schema
{
    use ParsePsalmStringTrait;

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
    public function __construct(string $class = null, string $method = null, protected array $refs = [], string $schema = null)
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

        $returning = false;
        $returnLine = '';
        $x = null;

        foreach (explode(PHP_EOL,$reflectM->getDocComment()) as $line) {
            if ($returning && preg_match('/\* @\w*/m',$line)) {
                $returning = false;
            }
            if (preg_match('/\* @return/m',$line)) {
                $returning = true;
            }
            if ($returning) {
                $returnLine .= ' '.trim(preg_replace('/^\s*\*\s*/m','',$line));
            }
        }

        if (preg_match('/@return (?<typeline>.*\{.*\}) (?<summary>.*)/m',$returnLine,$matches)) {
            $typeLine = $matches['typeline'] ?? '';
            $summary = $matches['summary'] ?? '';

            $typeLine = preg_replace('/ {2,}/',' ',$typeLine);

            $x = $this->parsePhase1($typeLine);
            $x = $this->parsePhase2($x);

            $this->wrap($this,$x);
        }

        unset($this->refs);
    }
}
