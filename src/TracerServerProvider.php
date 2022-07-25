<?php

declare(strict_types=1);

namespace Guandeng\Tracer;

use Illuminate\Support\ServiceProvider;

class TracerServerProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadConfig();
    }

    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/opentracing.php', 'opentracing');

        $this->app->singleton('tracer', function ($app) {
            return new TracerManager($app);
        });
        // 驱动注入
        $default = $this->app['config']['opentracing.default'];
        $driver = config('opentracing.tracer.' . $default . '.driver');
        $this->app['tracer']->extend($default, function () use ($driver, $default) {
            return new $driver($default);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['tracer'];
    }

    protected function loadConfig()
    {
        if ($this->app->runningInConsole() && function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/opentracing.php' => config_path('opentracing.php'),
            ]);
        }
    }
}
