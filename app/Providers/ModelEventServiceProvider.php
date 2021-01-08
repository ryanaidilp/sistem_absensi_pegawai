<?php

namespace App\Providers;

use App\Models\Outstation;
use App\Models\AbsentPermission;
use App\Models\PaidLeave;
use App\Observers\OutstationObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\AbsentPermissionObserver;
use App\Observers\PaidLeaveObserver;

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
        Outstation::observe(new OutstationObserver);
        PaidLeave::observe(new PaidLeaveObserver);
    }
}
