<?php

namespace EmranAlhaddad\SatamicDummyData;

use Statamic\Providers\AddonServiceProvider;


class ServiceProvider extends AddonServiceProvider
{


    public function bootAddon()
    {
        $this->commands([
            InjectDummyData::class,
        ]);
    }
}
