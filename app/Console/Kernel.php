<?php

namespace App\Console;

use App\Models\User;
use App\Models\Attende;
use App\Models\AttendeCode;
use App\Models\AttendeType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CreateAbsentCodeCommand;
use App\Console\Commands\GenerateAttendeCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CreateAbsentCodeCommand::class,
        GenerateAttendeCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('cache:clear')->dailyAt('00:50');
        $schedule->command('view:clear')->dailyAt('00:50');
        $schedule->command('debugbar:clear')->dailyAt('00:50');
        $schedule->command('absent:code')->dailyAt('01:00')
            ->onSuccess(function () {
                Log::info('code_generated_successfully');
            })
            ->onFailure(function () {
                Log::info('failed_to_generate_code');
            });
        $schedule->command('absent:attende')->dailyAt('01:05')
            ->onSuccess(function () {
                Log::info('attende_list_generated_successfully');
            })
            ->onFailure(function () {
                Log::info('failed_to_generate_attende_list');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
