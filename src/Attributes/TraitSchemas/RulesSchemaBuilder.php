<?php

namespace Onekone\Lore\Attributes\TraitSchemas;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationRuleParser;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

trait RulesSchemaBuilder
{
    const ITEMS = '*';
    const SELF = '!';
    const RAW = '?';

    protected function parse($class, string $rulesMethod = 'rules', string $descriptionsMethod = 'descriptions', string $examplesMethod = 'examples')
    {
        $object = (new $class());

        foreach (['rules','descriptions','examples'] as $prop) {
            ${$prop} = [];
            if (method_exists($object,${$prop.'Method'})) {
                ${$prop} = $object->{${$prop . 'Method'}}();
            }
            ${$prop.'Array'} = $this->array_undot(${$prop});
        }

        $baseArr = [];
        $r = $this->buildPropertiesList($rulesArray ?? [], $this);

        return $r;
    }

    protected function array_undot($dottedArray)
    {
        $array = [];
        foreach ($dottedArray as $key => $value) {
            if (is_array($value)) {
                Arr::set($array, $key . '.'.self::RAW, $value);
            }
        }
        foreach ($dottedArray as $key => $value) {
            if (!Arr::get($array, $key . '.'.self::RAW, false)) {
                Arr::set($array, $key, $value);
            }
        }
        foreach ($dottedArray as $key => $value) {
            if (is_array(Arr::get($array, $key)) && !is_array($value)) {
                Arr::set($array, $key . '.'.self::SELF, $value);
            }
        }
        return $array;
    }

    /**
     * @param $properties
     * @param class-string $schemaType Имя класса схемы
     * @return mixed
     */
    protected function buildPropertiesList($properties, &$baseArr)
    {
        $newProps = [];

        foreach ($properties as $key => $property) {
            $hidden = false;

            $newProp = [
                'property' => $key,
            ];

            $items = $property[self::ITEMS] ?? null;
            $raw   = $property[self::RAW] ?? null;
            $self  = $property[self::SELF] ?? null;

            if (is_array($property) && !$raw) {
                if ($items) {
                    if (is_array($items)) {
                        $newProp['type'] = 'array';
                        $newProp['items'] = [];
                        $newProp['items']['properties'] = $this->buildPropertiesList($property['*'], $newProp['items']);
                    } else {
                        $newProp['type'] = 'array';
                        $newProp['items'] = [];
                        $this->parseRules(explode('|', $items), $key, $newProp['items'], $newProp);
                    }
                } else {
                    $newProp['type'] = 'object';
                    $newProp['properties'] = $this->buildPropertiesList($property, $newProp);
                }
            }

            $rules = $property[self::RAW] ?? [];

            if (is_string($property)) {
                $rules = [...explode('|', $property), ...$rules];
            }
            if (is_array($property)) {
                $rules = [...explode('|', $self), ...$rules];
            }
            foreach ($rules as $k => $v) {
                if (!$v) {
                    unset($rules[$k]);
                }
            }

            $this->parseRules($rules, $key, $newProp, $baseArr);

            if (in_array($key, ['!', '*'])) {
                continue;
            }

            if (!($newProp['x']['hidden'] ?? false)) {
                $newProps[$key] = $newProp;
            }
        }

        return $newProps;
    }

    protected function parseRules($rules, $key, &$newProp, &$schema)
    {
        foreach ($rules as $rule) {
            $rule = (string)$rule;
            [$ruleStr, $ruleArgs] = ValidationRuleParser::parse($rule);

            switch (strtolower($ruleStr)) {
                case 'nullable':
                    $newProp['nullable'] = true;
                    break;
                case 'string':
                    $newProp['type'] = 'string';
                    break;
                case 'integer':
                    $newProp['type'] = 'integer';
                    break;
                case 'file':
                    $newProp['type'] = 'string';
                    $newProp['format'] = 'binary';
                    break;
                case 'numeric':
                    $newProp['type'] = 'number';
                    break;
                case 'accepted':
                case 'accepted_if':
                    $newProp['enum'] = ['yes', 'on', 1, '1', 'true', true];
                    break;
                case 'active_url':
                    $newProp['type'] = 'string';
                    $newProp['format'] = 'uri';
                    break;
                case 'hidden':
                    $newProp['x']['hidden'] = true;
                    break;
                case 'ipv4':
                    $newProp['format'] = 'ipv4';
                    break;
                case 'unique':
                    $newProp['unique'] = true;
                    break;
                case 'required':
                    if ($this->required == Generator::UNDEFINED) {
                        $this->required = [];
                    }
                    $this->required[] = $key;
                    break;
                case 'object':
                    $newProp['type'] = 'object';
                    break;
                case 'in':
                    $newProp['enum'] = $ruleArgs;
                    break;
                case 'ulid':
                    $newProp['format'] = $ruleStr;
                    break;
            }
        }
    }

    protected function buildProperties($parsed, OA\Schema|OA\Property|OA\JsonContent|null &$schema = null)
    {
        $props = [];
        foreach ($parsed ?: [] as $key => $prop) {
            if ($prop['x']['hidden'] ?? false) {
                continue;
            }
            $newProp = new OA\Property($key, type: $prop['type'] ?? null);
            foreach ($prop as $k => $p) {
                if ($k == 'hidden') {
                    continue 2;
                }
                if (in_array($k, ['properties', 'items'])) {
                    continue;
                }

                $newProp->{$k} = $p;
            }
            if ($prop['properties'] ?? null) {
                $newProp->properties = $this->buildProperties($prop['properties']);
            }
            if (!is_null($prop['items'] ?? null)) {

                if (!($prop['items'] ?? null)) {
                    $prop['items'] = [];
                }

                $newProp->items = new OA\Items();
                foreach ($prop['items'] as $kk => $pp) {
                    $newProp->items->{$kk} = $pp;
                }
                if ($prop['items']['properties'] ?? null) {
                    $newProp->items->properties = $this->buildProperties($prop['items']['properties'] ?? null);
                } else {
                    $newProp->items->properties = Generator::UNDEFINED;
                }
            }
            if ($newProp->items && $newProp->type != 'array') {
                $newProp->items = Generator::UNDEFINED;
            }
            if (!($prop['hidden'] ?? false)) {
                $props[] = $newProp;
            }
        }
        return $props;
    }
}
