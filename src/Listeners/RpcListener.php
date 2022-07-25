<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Listeners;

use Guandeng\Tracer\SpanStarter;
use Guandeng\Tracer\SpanTagManager;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

use const OpenTracing\Formats\TEXT_MAP;

class RpcListener extends Context
{
    use Dispatchable;
    use SpanStarter;

    public function __construct()
    {
        $this->spanTagManager = new spanTagManager();
    }

    public function onJobProcessing(Request $request, $path = null)
    {
        if ($path == null) {
            $path = $request->getPathInfo();
        }
        $name = config('opentracing.default');
        $this->tracer = resolve('tracer')->make($name);
        $key = "JsonRPC send [{$path}]";
        $span = $this->startSpan($key, $request);
        $headers = [];
        $this->tracer->inject($span->getContext(), TEXT_MAP, $headers);
        foreach ($headers as $header => $value) {
            $this->request->headers->set($header, $value);
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
}
