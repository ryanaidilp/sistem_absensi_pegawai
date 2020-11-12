<?php

namespace App\Providers;

use App\Models\AbsentPermission;
use App\Observers\AbsentPermissionObserver;
use Illuminate\Support\ServiceProvider;

class ModelEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        AbsentPermission::observe(new AbsentPermissionObserver);
    }
}
