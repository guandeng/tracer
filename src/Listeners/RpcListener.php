<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Listeners;

use Guandeng\Tracer\SpanStarter;
use Guandeng\Tracer\SpanTagManager;
use Illuminate\Http\Request;

use const OpenTracing\Formats\TEXT_MAP;

class RpcListener
{
    use SpanStarter;

    private $request;
    private $span;
    private $tracer;
    private $path = null;

    public function __construct($path = null)
    {
        $this->spanTagManager = new spanTagManager();
        $this->request = app(Request::class);
        $this->path = $path;
        if ($path == null) {
            $this->path = $this->request->getPathInfo();
        }
        $this->initTracer();
    }

    public function onJobProcessing()
    {
        $headers = [];
        $this->tracer->inject($this->span->getContext(), TEXT_MAP, $headers);
        foreach ($headers as $header => $value) {
            $this->request->headers->set($header, $value);
        }
        $this->span->setTag($this->spanTagManager->get('rpc', 'path'), $this->path);
        return $headers;
    }

    public function onJobProcessed($result = true)
    {
        $this->span->setTag($this->spanTagManager->get('rpc', 'status'), $result ? 'OK' : 'Failed');
        $this->span->finish();
        $this->tracer->flush();
    }

    public function onJobProcessException(\Throwable $exception)
    {
        $this->appendExceptionToSpan($exception);
        $this->span->finish();
        $this->tracer->flush();
    }

    protected function appendExceptionToSpan(\Throwable $exception): void
    {
        $this->span->setTag('exception', true);
        $this->span->setTag($this->spanTagManager->get('exception', 'class'), get_class($exception));
        $this->span->setTag($this->spanTagManager->get('exception', 'code'), $exception->getCode());
        $this->span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
        $this->span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
    }
    
    protected function initTracer()
    {
        $name = config('opentracing.default');
        $this->tracer = resolve('tracer')->make($name);
        $key = "JsonRPC send [{$this->path}]";
        $this->span = $this->startSpan($key);
    }

}