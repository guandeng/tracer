<?php

declare(strict_types=1);

namespace Guandeng\Tracer;

use Illuminate\Support\Manager;

class TracerManager extends Manager
{
    protected $config;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->config = $app->make('config');
    }
    // 这是个类,并且你可以通过$this->app拿到容器实例
    public function getDefaultDriver()
    {
        // info($this->config('opentracing'));
        if (is_null($this->config->get('opentracing.default'))) {
            return 'zipkin';
        }
        return $this->config->get('opentracing.default');
    }
}
