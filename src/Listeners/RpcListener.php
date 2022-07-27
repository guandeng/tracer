<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Listeners;

use Guandeng\Tracer\SpanStarter;
use Guandeng\Tracer\SpanTagManager;
use Illuminate\Http\Request;

use const OpenTracing\Formats\TEXT_MAP;

class RpcListener extends Context
{
    use SpanStarter;

    private $request;

    public function __construct()
    {
        $this->spanTagManager = new spanTagManager();
        $this->request = app(Request::class);
    }

    public function onJobProcessing($path = null)
    {
        if ($path == null) {
            $path = $this->request->getPathInfo();
        }
        $name = config('opentracing.default');
        $this->tracer = resolve('tracer')->make($name);
        $key = "JsonRPC send [{$path}]";
        $span = $this->startSpan($key);
        $headers = [];
        $this->tracer->inject($span->getContext(), TEXT_MAP, $headers);
        foreach ($headers as $header => $value) {
            $this->request->headers->set($header, $value);
        }
        $span->setTag($this->spanTagManager->get('rpc', 'path'), $path);
        static::$tracers = $this->tracer;
        static::$span = $span;
        return $headers;
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
