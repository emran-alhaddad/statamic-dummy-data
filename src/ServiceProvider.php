<?php

namespace Emran\DummyData;

use Emran\DummyData\Commands\InjectDummyData;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{

    public function register()
    {
        $this->commands([
            InjectDummyData::class,
        ]);
    }

    public function bootAddon()
    {
        //
    }
}
