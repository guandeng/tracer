<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Adapter;

use Zipkin\Endpoint;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;
use ZipkinOpenTracing\Tracer;

class ZipkinTracerFactory implements NamedFactoryInterface
{
    private string $prefix = 'opentracing.tracer.';

    private string $name = '';

    public function __construct($name)
    {
        $this->name = $name;
        $this->clientFactory = null;
    }

    /**
     * 实现方法.
     */
    public function make(): \OpenTracing\Tracer
    {
        [$app, $options, $sampler,$rate] = $this->parseConfig();
        // 设置随机率
        $sampler = $this->rateSampler($rate);
        $ipv4 = $app['ipv4'] ?? \Illuminate\Support\Facades\Request::ip();
        $isIpV6 = substr_count($ipv4, ':') > 1;
        $port = $app['port'] ?? \Illuminate\Support\Facades\Request::instance()->server('REMOTE_PORT');
        $endpoint = Endpoint::create(
            $app['name'],
            (! $isIpV6) ? $ipv4 : null,
            $isIpV6 ? $ipv4 : null,
            $port
        );
        $reporter = new Http($options, $this->clientFactory);
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        return new Tracer($tracing);
    }

    private function rateSampler($rate)
    {
        if (mt_rand(0, 100) < $rate) {
            return BinarySampler::createAsAlwaysSample();
        }
        return BinarySampler::createAsNeverSample();
    }

    private function parseConfig(): array
    {
        return [
            $this->getConfig('app', [
                'name' => 'skeleton',
                'ipv4' => '127.0.0.1',
                'ipv6' => null,
                'port' => 9501,
            ]),
            $this->getConfig('options', [
                'timeout' => 1,
            ]),
            $this->getConfig('sampler', BinarySampler::createAsAlwaysSample()),
            $this->getConfig('rate', 0.1),
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
