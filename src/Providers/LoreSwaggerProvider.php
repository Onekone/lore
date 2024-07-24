<?php

namespace Onekone\Lore\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Onekone\Lore\Commands\GenerateSwagger;
use Onekone\Lore\Rules\Example;
use Onekone\Lore\Rules\Hidden;
use Onekone\Lore\Rules\Obj;

class LoreSwaggerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Validator::extend('example', Example::class);
        Validator::extend('hidden', Hidden::class);
        Validator::extend('object', Obj::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSwagger::class
            ]);
        }
    }
}
