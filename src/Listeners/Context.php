<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Listeners;

class Context
{
    public static $span;

    public static $tracers;

    public $tracer;
}
