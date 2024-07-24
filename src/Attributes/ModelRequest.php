<?php

namespace Onekone\Lore\Attributes;

use Attribute;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Unique;
use OpenApi\Attributes as OA;
use OpenApi\Generator;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ModelRequest extends OA\RequestBody
{
    const ITEMS = '*';

    const SELF = '!';

    const RAW = '?';

    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $request = null)
    {
        $rules = (new $class())->rules();

        $ruleArray = $this->array_undot($rules);

        $baseArr = [];
        $parsed = $this->eitherOr($ruleArray, $baseArr);

        $request = $request ?: str_replace(['\\', '/'], '__', $class);

        return parent::__construct(
            ref: Generator::UNDEFINED,
            request: $request,
            description: $description ?? Generator::UNDEFINED,
            required: Generator::UNDEFINED,
            content: new OA\JsonContent(properties: $this->buildProperties($parsed)),
        );
    }

    protected function array_undot($dottedArray)
    {
        $array = [];
        foreach ($dottedArray as $key => $value) {
            if (is_array($value)) {
                Arr::set($array, $key . '.?', $value);
            }
        }
        foreach ($dottedArray as $key => $value) {
            if (!Arr::get($array, $key . '.?', false)) {
                Arr::set($array, $key, $value);
            }
        }
        foreach ($dottedArray as $key => $value) {
            if (is_array(Arr::get($array, $key)) && !is_array($value)) {
                Arr::set($array, $key . '.!', $value);
            }
        }
        return $array;
    }

    /**
     * @param $properties
     * @param class-string $schemaType Имя класса схемы
     * @return mixed
     */
    protected function eitherOr($properties, &$baseArr = null)
    {
        $newProps = [];

        foreach ($properties as $key => $property) {


            $hidden = false;

            $newProp = [
                'property' => $key,
            ];

            $items = $property[self::ITEMS] ?? null;
            $raw = $property[self::RAW] ?? null;
            $self = $property[self::SELF] ?? null;

            if (is_array($property) && !$raw) {
                if ($items) {
                    if (is_array($items)) {
                        $newProp['type'] = 'array';
                        $newProp['items'] = [];
                        $newProp['items']['properties'] = $this->eitherOr($property['*'], $newProp['items']);
                    } else {
                        $newProp['type'] = 'array';
                        $newProp['items'] = [];
                        $this->parseRules(explode('|', $items), $key, $newProp['items'], $newProp);
                    }
                } else {
                    $newProp['type'] = 'object';
                    $newProp['properties'] = $this->eitherOr($property, $newProp);
                }
            }

            $rules = $property[self::RAW] ?? [];

            if (is_string($property)) {
                $rules = [...explode('|', $property), ...$rules];
            }
            if (is_array($property)) {
                $rules = [...explode('|', $self), ...$rules];
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

    protected function parseRules($rules, $key, &$newProp, &$baseArr)
    {
        foreach ($rules as $rule) {

            $ruleString = explode(':', $rule);
            $ruleArgs = explode(',', $ruleString[1] ?? '');

            switch ($ruleString[0]) {
                case 'string':
                    $newProp['type'] = 'string';
                    break;
                case 'integer':
                    $newProp['type'] = 'integer';
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
                    $hidden = true;
                    break;
                case 'ipv4':
                    $newProp['format'] = 'ipv4';
                    break;
                case 'unique':
                    $newProp['unique'] = 4;
                    break;
                case 'nullable':
                    $newProp['nullable'] = true;
                    break;
                case 'required':
                    $baseArr['required'][] = $key;
                    break;
                case 'object':
                    $newProp['type'] = 'object';
                    break;


                case 'ulid':
                    break;
            }

            match (true) {
                ($rule instanceof Unique) => $newProp['unique'] = true,
                default => '',
            };
        }
    }

    protected function buildProperties($parsed)
    {
        $props = [];
        foreach ($parsed ?: [] as $key => $prop) {

            if ($prop['x']['hidden'] ?? false) {
                continue;
            }


            $newProp = new OA\Property($key, type: $prop['type'] ?? null);

            foreach ($newProp as $k => $p) {
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
