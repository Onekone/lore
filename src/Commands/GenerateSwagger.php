<?php

namespace Onekone\Lore\Commands;

use Illuminate\Console\Command;
use Onekone\Lore\Attributes\Processor;
use OpenApi\Generator;

class GenerateSwagger extends Command
{
    protected $signature = 'openapi:gen {--J|json : Generate as JSON} {path? : Output path}';
    protected $description = 'Generate new OpenAPI spec file';

    protected $aliases = ['swagger:gen'];

    public function handle()
    {
        $generator = new Generator();

        error_reporting(0);

        $generator->setProcessorPipeline($generator->getProcessorPipeline()->add(new Processor()));

        $p = $generator->generate([app_path()]);
        $json = $this->option('json');
        file_put_contents($this->argument('path') ?: 'swagger.yaml', $p->toYaml());
    }
}
