<?php

namespace Onekone\Lore\Attributes\TraitSchemas;

use Illuminate\Support\Collection;
use OpenApi\Attributes\{Items, Property, Schema};
use OpenApi\Generator;
use PHPStan\PhpDocParser\Ast\ConstExpr\{ConstExprIntegerNode, ConstExprStringNode};
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\{ArrayShapeItemNode,
    ArrayShapeNode,
    ArrayTypeNode,
    ConditionalTypeForParameterNode,
    ConstTypeNode,
    GenericTypeNode,
    IdentifierTypeNode,
    IntersectionTypeNode,
    NullableTypeNode,
    ObjectShapeItemNode,
    ObjectShapeNode,
    UnionTypeNode};
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\{ConstExprParser, PhpDocParser, TokenIterator, TypeParser};

trait PhpStanTrait
{
    protected function render(\ReflectionMethod $reflectionMethod, $which)
    {
        $string = $reflectionMethod->getDocComment();
        $lexer = new Lexer();
        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $phpDocParser = new PhpDocParser($typeParser, $constExprParser);

        $tokens = new TokenIterator($lexer->tokenize(trim($string)));

        $pp = [];

        $phpDocNode = $phpDocParser->parse($tokens);
        $paramTags = $phpDocNode->getParamTagValues();

        $unset = true;
        foreach ($paramTags as $k => $tag) {
            if ($tag->parameterName == '$' . $which) {
                $unset = false;
                $this->parseNode($tag, $this);
            }
        }

        if ($unset) {
            foreach ($reflectionMethod->getParameters() as $parameter) {
                if ($which === $parameter->getName()) {
                    $string = '/**' . PHP_EOL . '* @param ' . $parameter->getType() . ' $' . $parameter->getName() . PHP_EOL . '**/';
                }
            }

            $tokens = new TokenIterator($lexer->tokenize(trim($string)));

            $phpDocNode = $phpDocParser->parse($tokens);
            $paramTags = $phpDocNode->getParamTagValues();

            $this->parseNode($paramTags[0], $this);
        }

        foreach (['allOf', 'oneOf', 'anyOf'] as $prop) {
            $this->deduplicate($this, $prop);
        }
    }

    protected function deduplicate($schema, $property)
    {
        $p = [];
        if ($schema->$property === Generator::UNDEFINED) {
            return;
        }

        foreach ($schema->$property as $ofs) {
            $p[] = json_encode($ofs);
        }

        if (count(array_unique($p)) == 1) {
            foreach ($schema->{$property}[0] as $key => $value) {
                if ($schema->$key === Generator::UNDEFINED) {
                    $schema->$key = $value;
                }
            }

            $schema->$property = Generator::UNDEFINED;
        }
    }

    protected function parseNode($data, Property|Schema|Items &$schema, $depth = 0)
    {
        if ($depth > 16) {
            throw new \ErrorException('Too deep, sorry. Offending object was ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if ($schema->x === Generator::UNDEFINED) {
            $schema->x = [];
        }

        if ($data instanceof ParamTagValueNode) {
            if ($data->description) {
                $schema->description = $data->description;
            }
            $this->parseNode($data->type, $schema, $depth + 1);
        } elseif ($data instanceof ObjectShapeNode || $data instanceof ArrayShapeNode) {
            $schema->type = 'object';
            $schema->required = [];
            $schema->properties = [];
            foreach ($data->items as $item) {
                $property = new Property();
                $this->parseNode($item, $property, $depth + 1);
                $schema->properties[] = $property;
                if (!(($property->x['optional']) ?? true)) {
                    $schema->required[] = $property->property;
                    unset($property->x['optional']);
                }
            }
        } elseif ($data instanceof NullableTypeNode) {
            $schema->nullable = true;
            $this->parseNode($data->type, $schema, $depth + 1);
        } elseif ($data instanceof ObjectShapeItemNode || $data instanceof ArrayShapeItemNode) {
            $schema->property = $data->keyName?->name;
            $this->parseNode($data->valueType, $schema, $depth + 1);
            //$schema->x['optional'] = $data->optional;
        } elseif ($data instanceof ConditionalTypeForParameterNode) {
            $oneOf = [];
            foreach (['if' => $data->if, 'else' => $data->else] as $when => $type) {
                $_schema = new Schema(x: ['when' => $when]);
                $this->parseNode($type, $_schema, $depth + 1);
                $oneOf[] = $_schema;
            }
            $schema->x['condition'] = ['parameter' => $data->parameterName, 'type' => $data->targetType];
            $schema->oneOf = $oneOf;
        } elseif ($data instanceof IntersectionTypeNode) {
            $oneOf = [];
            foreach ($data->types as $type) {
                if (!$type instanceof ConstTypeNode) {
                    $_schema = new Schema();
                    $this->parseNode($type, $_schema, $depth + 1);
                    $oneOf[md5(json_encode($_schema))] = $_schema;
                }
            }

            $oneOf = array_values($oneOf);
            if (count($oneOf) > 0) {
                if (count($oneOf) > 1) {
                    $schema->allOf = $oneOf;
                } else {
                    foreach ($oneOf[0] as $property => $value) {
                        $schema->$property = $value;
                    }
                }
            }

        } elseif ($data instanceof UnionTypeNode) {
            $oneOf = [];
            $const = [];
            foreach ($data->types as $type) {
                if (!$type instanceof ConstTypeNode) {
                    $_schema = new Schema();
                    $this->parseNode($type, $_schema, $depth + 1);
                    $oneOf[md5(json_encode($_schema))] = $_schema;
                } else {
                    $const[$type->constExpr::class][] = $type->constExpr->value;
                }
            }
            foreach ($const as $k => $c) {
                $_schema = new Schema(type: '', x: ['optional' => false]);
                $_schema->type = $this->translateClassnamesAndTypesToOpenApiSpec($k, $_schema);
                if (count($c) > 1) {
                    $_schema->enum = $c;
                } else {
                    $_schema->example = $c[0];
                }
                $oneOf[md5(json_encode($_schema))] = $_schema;
            }
            $oneOf = array_values($oneOf);
            if (count($oneOf) > 0) {
                if (count($oneOf) > 1) {
                    $schema->oneOf = $oneOf;
                } else {
                    foreach ($oneOf[0] as $property => $value) {
                        $schema->$property = $value;
                    }
                }
            }


        } elseif ($data instanceof ArrayTypeNode) {
            $schema->type = 'array';
            $_items = new Items();

            $this->parseNode($data->type, $_items, $depth + 1);

            $schema->items = $_items;
        } elseif ($data instanceof ConstTypeNode) {
            $this->parseNode($data->constExpr, $schema, $depth + 1);
        } elseif ($data instanceof ConstExprStringNode) {
            $schema->type = 'string';
            $schema->example = $data->value;
        } elseif ($data instanceof IdentifierTypeNode) {
            $schema->type = $this->translateClassnamesAndTypesToOpenApiSpec($data->name, $schema);
        } elseif ($data instanceof GenericTypeNode) {
            $this->parseNode($data->type, $schema, $depth + 1);
            $generics = $data->genericTypes;
            switch ($schema->type) {
                case 'integer':
                    if ($generics[0] instanceof ConstTypeNode) $schema->minimum = (int)$generics[0]->constExpr->value;
                    if ($generics[1] instanceof ConstTypeNode) $schema->maximum = (int)$generics[1]->constExpr->value;
                    break;
                case 'array':
                case 'Array':
                case 'Collection':
                case Collection::class:
                    $schema->items = new Items();
                    $this->parseNode($generics[0], $schema->items);
                    break;
                default:
                    $schema->allOf = [];
                    foreach ($generics as $generic) {
                        $s = new Schema();
                        $this->parseNode($generic, $s);

                        $schema->allOf[] = $s;
                    }
                    break;
            }
        }
    }

    protected function translateClassnamesAndTypesToOpenApiSpec(string $type, Items|Property|Schema $schema): string
    {
        switch ($type) {
            case 'array-key':
                $schema->oneOf = [
                    new Schema(type: 'string'),
                    new Schema(type: 'integer'),
                ];
                break;
            case 'non-empty-string':
                $schema->minLength = 1;
                break;
            case 'positive-int':
                $schema->exclusiveMinimum = 0;
                break;
            case 'negative-int':
                $schema->exclusiveMaximum = 0;
                break;
            case 'non-positive-int':
                $schema->maximum = 0;
                break;
            case 'non-negative-int':
                $schema->minimum = 0;
                break;
            case 'non-zero-int':
                $schema->not = [0];
                break;
            case 'true':
                $schema->example = true;
                break;
            case 'false':
                $schema->example = false;
                break;
        }

        $return = match ($type) {
            ConstExprIntegerNode::class,
            'int' => 'integer',
            'double',
            'float' => 'number',
            'true', 'false' => 'bool',
            ConstExprStringNode::class, 'non-empty-string' => 'string',
            'iterable' => 'array',
            'bool' => 'boolean',
            default => $type
        };

        switch ($return) {
            case 'array':
                if ($schema->items == Generator::UNDEFINED) {
                    $schema->items = new Items();
                }
        }

        return $return;
    }

    protected function isBasicType(string $type): bool
    {
        return in_array($type, ['int', 'integer',
            'string',
            'array-key',
            'bool', 'boolean',
            'true',
            'false',
            'null',
            'float',
            'double',
            'number',
            'scalar',
            'array',
            'iterable',
            'callable', 'pure-callable',
            'resource', 'closed-resource', 'open-resource',
            'void',
            'object',
            'positive-int',
            'negative-int',
            'non-positive-int',
            'non-negative-int',
            'non-zero-int',
            'list',
            'non-empty-list', 'key-of', 'value-of',
            'iterable', 'Collection', 'callable-string', 'numeric-string', 'non-empty-string', 'non-falsy-string', 'truthy-string', 'literal-string', 'lowercase-string',
        ]);
    }
}
