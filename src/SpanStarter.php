<?php

declare(strict_types=1);

namespace Guandeng\Tracer;

use const OpenTracing\Formats\TEXT_MAP;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

trait SpanStarter
{
    public function formatHttpPath($httpPath)
    {
        $httpPath = preg_replace('/\/\d+$/', '/{id}', $httpPath);
        return preg_replace('/\/\d+\//', '/{id}/', $httpPath);
    }

    protected function startSpan(
        $name,
        $request  = null,
        array $option = [],
        string $kind = SPAN_KIND_RPC_SERVER
    ) {
        $name = $this->formatHttpPath($name);
        if($request){
            $this->request = $request;
        }
        $carrier = array_map(function ($header) {
            return $header[0];
        }, $this->request->headers->all());
        // Extracts the context from the HTTP headers.
        $spanContext = $this->tracer->extract(TEXT_MAP, $carrier);
        if ($spanContext) {
            $option['child_of'] = $spanContext;
        }
        $root = $this->tracer->startSpan($name, $option);
        $root->setTag(SPAN_KIND, $kind);
        return $root;
    }
}
