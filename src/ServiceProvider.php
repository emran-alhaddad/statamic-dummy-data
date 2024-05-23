<?php

namespace Emran\DummyData;

use Illuminate\Support\ServiceProvider as s;
use Emran\DummyData\Commands\InjectDummyData;

class ServiceProvider extends s
{
    public function register()
    {
        $this->commands([
            InjectDummyData::class,
        ]);
    }

    public function boot()
    {
        // Your boot logic here
    }
}
