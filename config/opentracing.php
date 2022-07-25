<?php

declare(strict_types=1);

use Zipkin\Samplers\BinarySampler;

return [
    'default' => env('TRACER_DRIVER', 'zipkin'),
    'enable' => [
        'guzzle' => env('TRACER_ENABLE_GUZZLE', false),
    ],
    'tracer' => [
        'zipkin' => [
            'driver' => Guandeng\Tracer\Adapter\ZipkinTracerFactory::class,
            'app' => [
                'name' => env('APP_NAME', 'tracer-zipkin'),
                'ipv4' => '127.0.0.1',
                'ipv6' => null,
                'port' => 9501,
            ],
            'options' => [
                'endpoint_url' => env('ZIPKIN_ENDPOINT_URL', 'http://localhost:9411/api/v2/spans'),
                'timeout' => env('ZIPKIN_TIMEOUT', 1),
            ],
            'rate' => 100 // 采样率 0-100%
        ],
        'jaeger' => [
            'driver' => Hyperf\Tracer\Adapter\JaegerTracerFactory::class,
            'name' => env('APP_NAME', 'tracer-jaeger'),
            'options' => [
                'local_agent' => [
                    'reporting_host' => env('JAEGER_REPORTING_HOST', 'localhost'),
                    'reporting_port' => env('JAEGER_REPORTING_PORT', 5775),
                ],
            ],
        ],
    ],
    'tags' => [
        'http_client' => [
            'http.url' => 'http.url',
            'http.method' => 'http.method',
            'http.status_code' => 'http.status_code',
        ],
        'redis' => [
            'arguments' => 'arguments',
            'result' => 'result',
        ],
        'db' => [
            'db.query' => 'db.query',
            'db.statement' => 'db.statement',
            'db.query_time' => 'db.query_time',
        ],
        'exception' => [
            'class' => 'exception.class',
            'code' => 'exception.code',
            'message' => 'exception.message',
            'stack_trace' => 'exception.stack_trace',
        ],
        'request' => [
            'path' => 'request.path',
            'method' => 'request.method',
            'header' => 'request.header',
        ],
        'coroutine' => [
            'id' => 'coroutine.id',
        ],
        'response' => [
            'status_code' => 'response.status_code',
        ],
    ],
];
