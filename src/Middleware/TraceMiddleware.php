<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Middleware;

use Closure;
use Guandeng\Tracer\SpanStarter;
use Guandeng\Tracer\SpanTagManager;
use Illuminate\Support\Str;
use OpenTracing\Span;

use const OpenTracing\Formats\TEXT_MAP;
use const Zipkin\Tags\ERROR;

class TraceMiddleware
{
    use SpanStarter;

    public const RUNTIME_MEMORY = 'runtime.memory';

    public const HTTP_RESPONSE_BODY_SIZE = 'http.response.body.size';

    public const HTTP_RESPONSE_BODY = 'http.response.body';

    public const HTTP_RESPONSE_HEADERS = 'http.response.headers';

    private $bodySize = 5000;

    public function handle($request, Closure $next)
    {
        // if ($this->shouldBeExcluded($request->path())) {
        //     return $next($request);
        // }
        // if (! $this->shouldBeIncluded($request->path())) {
        //     return $next($request);
        // }
        $this->tracer = resolve('tracer')->make();
        $this->spanTagManager = new SpanTagManager();

        $span = $this->buildSpan();

        $response = null;
        try {
            $headers = [];
            $this->tracer->inject($span->getContext(), TEXT_MAP, $headers);
            foreach ($headers as $header => $value) {
                $request->headers->set($header, $value);
            }
            $span->setTag(static::RUNTIME_MEMORY, round(memory_get_usage() / 1000000, 2) . 'MB');
            $response = $next($request);
            $this->appendErrorToSpan($span, $response);
            return $response;
        } catch (\Exception $e) {
            $this->appendExceptionToSpan($span, $e);
            throw $e;
        } finally {
            $this->spanEnd($span);
        }
        return $response;
    }

    public function spanFinish($span)
    {
        if (! is_null($span)) {
            $span->finish();
        }
    }

    /**
     * SPAN结束
     * @param mixed $span
     */
    public function spanEnd($span)
    {
        $this->spanFinish($span);
        $this->tracerFlush();
    }

    public function tracerFlush()
    {
        if (! is_null($this->tracer)) {
            $this->tracer->flush();
        }
    }

    public function convertToStr($value)
    {
        if (! is_scalar($value)) {
            $value = '';
        } else {
            $value = (string) $value;
        }

        return $value;
    }

    public function formatHttpBody($httpBody, $bodySize = null)
    {
        $httpBody = $this->convertToStr($httpBody);

        if (is_null($bodySize)) {
            $bodySize = strlen($httpBody);
        }

        if ($bodySize > $this->bodySize) {
            $httpBody = mb_substr($httpBody, 0, $this->bodySize, 'utf8') . ' ...';
        }

        return $httpBody;
    }

    protected function appendErrorToSpan(Span $span, $response): void
    {
        if ($response) {
            $span->setTag($this->spanTagManager->get('response', 'status_code'), $response->getStatusCode());
            $httpResponseBody = $this->convertToStr($response->getContent());
            $httpResponseBodyLen = strlen($httpResponseBody);
            $span->setTag(static::HTTP_RESPONSE_BODY_SIZE, $httpResponseBodyLen);
            $span->setTag(static::HTTP_RESPONSE_BODY, $this->formatHttpBody(
                $httpResponseBody,
                $httpResponseBodyLen
            ));
            $span->setTag(static::HTTP_RESPONSE_HEADERS, json_encode($response->headers->all(), JSON_UNESCAPED_UNICODE));
        }
        if ($response->isServerError()) {
            $span->setTag(ERROR, 'server error');
        } elseif ($response->isClientError()) {
            $span->setTag(ERROR, 'client error');
        }
    }

    protected function appendExceptionToSpan(Span $span, \Throwable $exception): void
    {
        $span->setTag('exception', true);
        $span->setTag($this->spanTagManager->get('exception', 'class'), get_class($exception));
        $span->setTag($this->spanTagManager->get('exception', 'code'), $exception->getCode());
        $span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
        $span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
    }

    protected function buildSpan(): Span
    {
        $path = \Request::path();
        $span = $this->startSpan($path);
        $span->setTag($this->spanTagManager->get('request', 'method'), \Request::method());
        foreach (\Request::header() as $key => $value) {
            $span->setTag($this->spanTagManager->get('request', 'header') . '.' . $key, implode(', ', $value));
        }
        return $span;
    }

    protected function shouldBeExcluded(string $path): bool
    {
        if (! config('opentracing.middleware.excluded_paths')) {
            return false;
        }
        $excluded_paths = explode(',', config('opentracing.middleware.excluded_paths'));
        if (! $excluded_paths) {
            return false;
        }
        foreach ($excluded_paths as $excludedPath) {
            if (Str::is($excludedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldBeIncluded(string $path): bool
    {
        if (! config('opentracing.middleware.included_paths')) {
            return true;
        }
        $included_paths = explode(',', config('opentracing.middleware.included_paths'));
        if (! $included_paths) {
            return false;
        }
        foreach ($included_paths as $includedPath) {
            if (Str::is($includedPath, $path)) {
                return true;
            }
        }

        return false;
    }
}
