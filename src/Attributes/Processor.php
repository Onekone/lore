<?php

namespace Onekone\Lore\Attributes;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Operation;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use OpenApi\Processors\ProcessorInterface;

class Processor
{

    public function __invoke(Analysis &$analysis): void
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        Artisan::call('route:list --json');

        $annotations = $analysis->getAnnotationsOfType(Operation::class);

        /** @var AbstractAnnotation[] $pathList */
        $pathList = [];
        foreach ($annotations as &$operation) {
            $pathList[strtoupper($operation->method) . ' ' . $operation->path] = &$operation;
        }

        foreach (json_decode(Artisan::output()) as $route) {
            if ($route->action != 'Closure' && preg_match('/^api/m', $route->uri)) {
                $oo = explode('|', str_replace('GET|HEAD', 'GET', $route->method))[0];
                $key = $oo.' /'.$route->uri;
                [$className,$classMethod] = explode('@',$route->action);
                [$reflectClass, $reflectMethod] = [new \ReflectionClass($className), new \ReflectionMethod($className, $classMethod)];

                $poop = '\\OpenApi\\Attributes\\'.Str::ucfirst($oo);

                if (!($pathList[$key] ?? null)) {
                    $p = new $poop();
                    $p->path = '/'.$route->uri;
                    $analysis->addAnnotation($p,$analysis->context);
                    $pathList[$key] = $p;
                }

                if ($pathList[$key] ?? null) {
                    /** @var OA\Get $x */
                    $x = &$pathList[$key];

                    if ($x->requestBody == Generator::UNDEFINED) {

                    }

                    $x->operationId = $route->name;
                    $x->x = ["artisan:route" => json_encode($route)];
                }
            }
        }
    }
}
