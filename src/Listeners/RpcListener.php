<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Listeners;

use Guandeng\Tracer\SpanStarter;
use Guandeng\Tracer\SpanTagManager;

use const OpenTracing\Formats\TEXT_MAP;

class RpcListener extends Context
{
    use SpanStarter;

    public function __construct()
    {
        $this->spanTagManager = new spanTagManager();
    }

    public function onJobProcessing($path = null)
    {
        if ($path == null) {
            $path = \Request::path();
        }
        $name = config('opentracing.default');
        $this->tracer = resolve('tracer')->make($name);
        $key = "JsonRPC send [{$path}]";
        $span = $this->startSpan($key);
        $headers = [];
        $this->tracer->inject($span->getContext(), TEXT_MAP, $headers);
        foreach ($headers as $header => $value) {
            \Request::header($header, $value);
        }
        $span->setTag($this->spanTagManager->get('rpc', 'path'), $path);
        static::$tracers = $this->tracer;
        static::$span = $span;
    }

    public function onJobProcessed($result = true)
    {
        $tracers = static::$tracers;
        $span = static::$span;
        $span->setTag($this->spanTagManager->get('rpc', 'status'), $result ? 'OK' : 'Failed');
        $span->finish();
        $tracers->flush();
    }

    public function onJobProcessException(\Throwable $exception)
    {
        $tracers = static::$tracers;
        $span = static::$span;
        $this->appendExceptionToSpan($span, $exception);
        $span->finish();
        $tracers->flush();
    }

    protected function appendExceptionToSpan($span, \Throwable $exception): void
    {
        $span->setTag('exception', true);
        $span->setTag($this->spanTagManager->get('exception', 'class'), get_class($exception));
        $span->setTag($this->spanTagManager->get('exception', 'code'), $exception->getCode());
        $span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
        $span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
    }
}
