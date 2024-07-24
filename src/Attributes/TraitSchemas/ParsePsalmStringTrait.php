<?php

namespace Onekone\Lore\Attributes\TraitSchemas;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

trait ParsePsalmStringTrait
{
    /**
     * @param class-string $babah
     * @param bool $asArray
     * @return Schema|array
     */
    public static function parseTypeHintString(string $babah, bool $asArray = true)
    {
        $p = new self();

        $obj = (object)[];

        $x = $p->parsePhase1($babah);
        $x = $p->parsePhase2($x);

        if (!$asArray) {
            $p->wrap($p,$x);
            return $p;
        }

        return $x;
    }

    /**
     * @param Schema|Property|Items $schema
     * @param array $properties
     */
    protected function wrap(Schema|Property|Items &$schema, array $properties)
    {
        foreach ($properties as $key => $property) {
            switch ($key) {
                case 'oneOf':
                case 'allOf':
                    $schema->{$key} = [];
                    foreach ($property as $oneOf) {
                        $_subschema = new Schema();
                        $this->wrap($_subschema, $oneOf);
                        $schema->{$key}[] = $_subschema;
                    }
                    break;
                case 'properties':
                    $schema->{$key} = [];
                    foreach ($property as $oneOf) {
                        $_subschema = new Property();
                        $this->wrap($_subschema, $oneOf);
                        $schema->{$key}[] = $_subschema;
                    }
                    break;
                case 'items':
                    $_subitems = new Items();
                    $this->wrap($_subitems,$property);
                    $schema->items = $_subitems;
                    break;
                default:
                    $schema->{$key} = $property;
                    break;
            }
        }
    }

    protected function parsePhase1($typeLine, &$context = null)
    {
        $regex = '((?<brackets>\(.+?\))|(?<type>.+?)(?<generator>\<.*?\>|{.*?})?)(?<selector>\||\&|$)';
        $matchCount = preg_match_all("/$regex/m",$typeLine,$matches);

        if (!$context) {
            $context = ["oneOf" => []];
        }
        $_ctx = &$context['oneOf'];
        $anding = false;
        $recovering = false;

        foreach ($matches as $k => &$match){
            foreach ($match as &$i) {
                $i = trim($i);
            }
            if (!$match) {
                foreach ($matches as &$j) {
                    unset($j[$k]);
                }
            }
        }
        unset($i);
        for($i = 0; $i < $matchCount; $i++) {
            if (!$matches[0][$i]) {
                continue;
            }
            if ($recovering) {
                $_ctx = &$context['oneOf'];
                $recovering = false;
            }

            if ($matches['selector'][$i] == '&' && !$anding) {
                $_ctx = &$context['oneOf'][]['allOf'];
                $anding = true;
            } elseif ($anding && $matches['selector'][$i] != '&') {
                $anding = false;
                $recovering = true;
            }

            if ($matches['brackets'][$i]) {
                $_ctx[] = $this->parsePhase1(substr($matches['brackets'][$i],1,-1));
            } elseif ($matches['generator'][$i]) {
                $p = [
                    'x' => ['parser' => ['typeline' => $matches[0][$i]]]
                ];
                $this->parseGenerator($matches['generator'][$i],$matches['type'][$i],$p, trim($matches['generator'][$i])[0]);
                $_ctx[] = $p;
            } else {
                $type = $matches['type'][$i];

                if ($type[0] == $type[-1] && in_array($type[0],['"',"'"])) {
                    $_ctx['literal_string'] = $_ctx['literal_string'] ?? [
                        'type' => 'string',
                        'enum' => []
                    ];
                    $_ctx['literal_string']['enum'][] = substr($type,1,-1);
                } elseif (filter_var($type,FILTER_VALIDATE_INT)) {
                    $_ctx['literal_int'] = $_ctx['literal_int'] ?? [
                        'type' => 'integer',
                        'enum' => []
                    ];
                    $_ctx['literal_int']['enum'][] = (int)$type;
                }
                else {
                    $prop = [
                        'x' => ['parser' => ['class-line' => $type]]
                    ];

                    $__ctx = &$prop;

                    $this->parseType($type,$__ctx);

                    $_ctx[] = $prop;
                }
            }
        }

        return $context;
    }

    protected function parseGenerator($generator, $type, &$contextSchema, $bracket)
    {
        $regex = '(\s*(?<property>[^,]*?)(?<optional>\?)?\:)?\s*(?<value>(?<value_type>.*?)(?<generator>\<.*?\>|\{.*?\})?\s*)(,|$)';

        $generator = substr(trim($generator),1,-1);

        preg_match_all("/$regex/m",$generator,$matches);


        foreach ($matches as $k => &$match){
            foreach ($match as &$kk) {
                $kk = trim($kk);
            }
        }
        foreach ($matches[0] as $k => $t) {
            if (!$t) {
                foreach ($matches as $kk=>$m) {
                    unset($matches[$kk][$k]);
                }
            }
        }

        $matchCount = count($matches[0]);

        $items = [];

        switch ($type) {
            case 'int':case 'integer':
            if ($bracket == '<') {
                $contextSchema['type'] = 'integer';
                [$min, $max] = [$matches['value'][0] ?? 'min', $matches['value'][1] ?? 'max'];

                foreach (['exclusiveMinimum' => [$min, 'min'], 'exclusiveMaximum' => [$max, 'max']] as $k => $v) {
                    if (
                        (filter_var($v[0], FILTER_VALIDATE_INT) ||
                            filter_var($v[0], FILTER_VALIDATE_FLOAT)) &&
                        ($v[0] != $v[1])
                    ) {
                        $contextSchema[$k] = $v[0];
                    }
                }
            }
            break;
            case '...':
                $this->parseType($matches['value'][1],$contextSchema);
                break;
            case 'Collection': case 'array':
            if ($bracket == '<') {
                if ($matchCount <= 2) {
                    $contextSchema['type'] = 'array';
                    $p = [];
                    if ($matches['generator'][$matchCount-1]) {
                        $this->parseGenerator($matches['generator'][$matchCount-1], $matches['value_type'][$matchCount-1], $p, trim($matches['generator'][$matchCount-1])[0]);
                    } else {
                        $this->parsePhase1($matches['value'][$matchCount-1],$p);
                    }
                    $contextSchema['items'] = $p;
                }
                break;
            }
            case 'object':

                $pipi = function($i) use ($matches) {
                    return filter_var($matches['property'][$i],FILTER_VALIDATE_INT);
                };


                switch ($bracket) {
                    case '{':
                        $contextSchema['type'] = 'array';

                        foreach($matches[0] as $i => $m) {
                            if (!$m) {
                                continue;
                            }

                            $p = [
                                'x' => ['parser' => ['value' => $matches['value'][$i]]]
                            ];

                            if ($matches['property'][$i] && !$pipi($i)) {
                                $contextSchema['type'] = 'object';
                            }

                            if ($contextSchema['type'] == 'object') {
                                $p['property'] = $matches['property'][$i] ?: $i;
                            }

                            if ($matches['generator'][$i]) {
                                $this->parseGenerator($matches['generator'][$i], $matches['value_type'][$i], $p, trim($matches['generator'][$i])[0]);
                            } else {
                                $this->parsePhase1($matches['value'][$i],$p);
                            }

                            if ($matches['value_type'][$i] != '...') {
                                $items[] = $p;
                            } else {
                                $contextSchema['additionalProperties'][0]['oneOf'][] = $p;
                            }
                        }
                }

                if ($contextSchema['type'] == 'array') {
                    $contextSchema['items']['oneOf'] = $items;
                } else {
                    $contextSchema['properties'] = $items;
                }
                break;
        }
    }

    protected function parseType($type, &$contextSchema = null)
    {
        $p = &$contextSchema;

        $type = trim($type);

        if (preg_match('/\[\]$/m',$type)) {
            $p['type'] = 'array';
            $p['items'] = [];
            $p['x']['parser']['plural'] = true;
            $p = &$p['items'];

            $type = preg_replace('/\[\]/m','',$type);
        }

        $p['x']['parser']['parsed-type'] = $type;

        switch ($type) {
            case 'int': case 'integer':
            $p['type'] = 'integer';
            break;
            case 'positive-int':
                $p['type'] = 'integer';
                $p['minimum'] = 1;
                break;
            case 'negative-int':
                $p['type'] = 'integer';
                $p['maximum'] = -1;
                break;
            case 'non-positive-int':
                $p['type'] = 'integer';
                $p['minimum'] = 0;
                break;
            case 'non-negative-int':
                $p['type'] = 'integer';
                $p['maximum'] = 0;
                break;
            case 'non-zero-int':
                $p['type'] = 'integer';
                $p['not']  = ['enum' => [0]];
                break;
            case 'pure-callable':
            case 'resource':
            case 'closed-resource':
            case 'open-resource':
            case 'callable':
            case 'string':
                $p['type'] = 'string';
                break;
            case 'array-key':
                $p['oneOf'] = [
                    ['type' => 'integer'],
                    ['type' => 'string'],
                ];
                break;
            case 'bool':
            case 'boolean':
                $p['type'] = 'boolean';
                break;
            case 'true':
                $p['type'] = 'boolean';
                $p['enum'] = [true];
                break;
            case 'false':
                $p['type'] = 'boolean';
                $p['enum'] = [false];
                break;
            case 'null':
                $p['nullable'] = true;
                break;
            case 'float':
            case 'double':
                $p['type'] = 'number';
                break;
            case 'scalar':
                $p['oneOf'] = [
                    ['type' => 'array'],
                    ['type' => 'integer'],
                    ['type' => 'number'],
                    ['type' => 'boolean'],
                    ['type' => 'string'],
                ];
                break;
            case 'iterable':
            case 'array':
                $p['type'] = 'array';
                $p['items'] = [];
                break;
            case 'void':
                break;
            case 'object': default:
            $p['type'] = 'object';
            break;
            case 'non-empty-string':
                $p['type'] = 'string';
                $p['not'] = [''];
                break;
            case 'numeric-string':
                $p['type'] = 'string';
                $p['pattern'] = '/-?\d+(\.\d*)?/';
                break;
        }
    }

    protected function parsePhase2(&$x)
    {
        if (!is_array($x)) {
            return $x;
        };

        foreach ($x as &$oneOfProp) {
            $this->parsePhase2($oneOfProp);
        }

        if (is_array($x['oneOf'] ?? null) && count($x['oneOf']) == 1) {
            foreach ($x['oneOf'][0] as $oneOfKey => &$oneOfProp) {
                $x[$oneOfKey] = $oneOfProp;
            }
            unset($x['oneOf']);
        }

        if (is_array($x['oneOf'] ?? null)) {
            $x['oneOf'] = array_values($x['oneOf']);
        }


        return $x;
    }
}
