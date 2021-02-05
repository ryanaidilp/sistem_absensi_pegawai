<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;
use App\Console\Commands\GenerateHolidays;
use App\Console\Commands\CheckForBirthday;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\NotifyExpiredPaidLeave;
use App\Console\Commands\GenerateAttendeCommand;
use App\Console\Commands\CreateAbsentCodeCommand;
use App\Console\Commands\NotifyExpiredOutstation;
use App\Console\Commands\NotifyExpiredPermission;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GenerateHolidays::class,
        CheckForBirthday::class,
        NotifyExpiredPaidLeave::class,
        GenerateAttendeCommand::class,
        CreateAbsentCodeCommand::class,
        NotifyExpiredPermission::class,
        NotifyExpiredOutstation::class,
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
        $schedule->command('view:clear')->dailyAt('00:50');
        $schedule->command('debugbar:clear')->dailyAt('00:50');
        $schedule->command('birthday:check')->dailyAt('06:00');
        $schedule->command('permission:check')->dailyAt("01:10");
        $schedule->command('outstation:check')->dailyAt("01:15");
        $schedule->command('paidleave:check')->dailyAt("00:00");
        $schedule->command('cache:clear')->weeklyOn(5, '00:50');
        $schedule->command('absent:code')->weekdays()->at('01:00')
            ->onSuccess(function () {
                Log::info('code_generated_successfully');
            })
            ->onFailure(function () {
                Log::info('failed_to_generate_code');
            });
        $schedule->command('absent:attende')->weekdays()->at('01:05')
            ->onSuccess(function () {
                Log::info('attende_list_generated_successfully');
            })
            ->onFailure(function () {
                Log::info('failed_to_generate_attende_list');
            });
        $schedule->command('holiday:generate', [
            '--year' => now()->addYear()->year
        ])->yearlyOn(12, 31, '18:00');
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
