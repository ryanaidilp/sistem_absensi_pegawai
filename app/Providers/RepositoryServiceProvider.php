<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\AttendeRepository;
use App\Repositories\HolidayRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\PaidLeaveRepository;
use App\Repositories\OutstationRepository;
use App\Repositories\AttendeCodeRepository;
use App\Repositories\AbsentPermissionRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\AttendeRepositoryInterface;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use App\Repositories\Interfaces\PaidLeaveRepositoryInterface;
use App\Repositories\Interfaces\OutstationRepositoryInterface;
use App\Repositories\Interfaces\AttendeCodeRepositoryInterface;
use App\Repositories\Interfaces\AbsentPermissionRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
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
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AttendeRepositoryInterface::class, AttendeRepository::class);
        $this->app->bind(HolidayRepositoryInterface::class, HolidayRepository::class);
        $this->app->bind(PaidLeaveRepositoryInterface::class, PaidLeaveRepository::class);
        $this->app->bind(OutstationRepositoryInterface::class, OutstationRepository::class);
        $this->app->bind(AttendeCodeRepositoryInterface::class, AttendeCodeRepository::class);
        $this->app->bind(AbsentPermissionRepositoryInterface::class, AbsentPermissionRepository::class);
    }
}
