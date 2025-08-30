<?php

namespace jcnghm\ApiScout;

use Illuminate\Support\ServiceProvider;
use jcnghm\ApiScout\Commands\AnalyzeApiCommand;
use jcnghm\ApiScout\Commands\GenerateComponentsCommand;
use jcnghm\ApiScout\Commands\SetupAuthCommand;
use jcnghm\ApiScout\Commands\AddEndpointCommand;

class ApiScoutServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api-scout.php', 'api-scout');
        
        $this->app->singleton('api-scout', function ($app) {
            return new ApiScout();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/api-scout.php' => config_path('api-scout.php'),
        ], 'api-scout-config');

        $this->publishes([
            __DIR__ . '/../resources/stubs' => resource_path('stubs/api-scout'),
        ], 'api-scout-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeApiCommand::class,
                GenerateComponentsCommand::class,
                SetupAuthCommand::class,
                AddEndpointCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'api-scout');
    }
}