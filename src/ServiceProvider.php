
<?php

namespace Emran\DummyDataInject;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any application services.
        // Example: $this->app->singleton(SomeService::class, function ($app) {
        //     return new SomeService();
        // });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InjectDummyData::class,
            ]);
        }
    }
}

