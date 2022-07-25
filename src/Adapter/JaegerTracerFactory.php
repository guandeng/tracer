<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Adapter;

use Jaeger\Config;
use OpenTracing\Tracer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

use const Jaeger\SAMPLER_TYPE_CONST;

class JaegerTracerFactory implements NamedFactoryInterface
{
    private string $prefix = 'opentracing.tracer.';

    private string $name = '';

    public function __construct($name, LoggerInterface $logger = null, CacheItemPoolInterface $cache = null)
    {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->name = $name;
    }

    public function make(): Tracer
    {
        [$name, $options] = $this->parseConfig();

        $jaegerConfig = new Config(
            $options,
            $name,
            $this->logger,
            $this->cache
        );
        return $jaegerConfig->initializeTracer();
    }

    private function parseConfig(): array
    {
        return [
            $this->getConfig('name', 'skeleton'),
            $this->getConfig('options', [
                'sampler' => [
                    'type' => SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                'logging' => false,
            ]),
        ];
    }

    private function getConfig(string $key, $default)
    {
        return config($this->getPrefix() . $key, $default);
    }

    private function getPrefix(): string
    {
        return rtrim($this->prefix . $this->name, '.') . '.';
    }
}
