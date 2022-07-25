<?php

declare(strict_types=1);
/**
 * This file is part of hk8591/im.
 *
 * @link     https://code.addcn.com/hk8591/services/im
 * @document https://code.addcn.com/hk8591/services/blob/master/README.md
 * @contact  hdj@addcn.com
 */
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
        $endpoint = Endpoint::create($app['name'], $app['ipv4'], $app['ipv6'], $app['port']);
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
