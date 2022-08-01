
# Laravel Tracer
![](https://img.shields.io/badge/stable-1.0.0-brightgreen.svg)
![](https://img.shields.io/badge/autor-guandeng-red.svg)
![](https://img.shields.io/badge/license-MIT-green.svg)
#### 通过 Composer 安装组件
> composer require guandeng/tracer

#### 配置
在app/Http/Kernel.php下$middleware加入下面代码
>  \Guandeng\Tracer\Middleware\TraceMiddleware::class

配置config/opentracing.php
```
<?php

declare(strict_types=1);

use Zipkin\Samplers\BinarySampler;

return [
    'default' => env('TRACER_DRIVER', 'zipkin'),
    'middleware' => [
        'excluded_paths' => env('TRACER_EXCLUDED_PATHS', ''), // 路径黑名单
        'included_paths' => env('TRACER_INCLUDED_PATHS', null), // 路径白名单
    ],
    'tracer' => [
        'zipkin' => [
            'driver' => Guandeng\Tracer\Adapter\ZipkinTracerFactory::class,
            'app' => [
                'name' => env('APP_NAME', 'tracer-zipkin'),
            ],
            'options' => [
                'endpoint_url' => env('ZIPKIN_ENDPOINT_URL', 'http://localhost:9411/api/v2/spans'),
                'timeout' => env('ZIPKIN_TIMEOUT', 1),
            ],
            'rate' => env('ZIPKIN_SAMPLE_RATE', 1) // 采样率 0-100%
        ]
    ]
];

```
上面配置未生成，可以执行下面命令手动生成
> php artisan vendor:publish --provider="Guandeng\Tracer\TracerServerProvider"
#### 程序内部监听
```
use Guandeng\Tracer\Listeners\RpcListener;

$subscriber = new RpcListener();
// $name 定义的Span名称, $request  
$subscriber->onJobProcessing($request);// 监听开始

// your code

$subscriber->onJobProcessed();// 监听结束
```


#### zipkin
安装
> docker run -d --restart always -p 9411:9411 --name zipkin openzipkin/zipkin

访问
> http://localhost:9411/