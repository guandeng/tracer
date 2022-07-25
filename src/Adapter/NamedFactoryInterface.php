<?php

declare(strict_types=1);

namespace Guandeng\Tracer\Adapter;

interface NamedFactoryInterface
{
    /**
     * Create the object from factory.
     */
    public function make();
}
