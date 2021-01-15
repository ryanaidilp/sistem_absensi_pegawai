<?php

namespace App\Console\Commands;

use App\Models\Outstation;
use App\Notifications\OutstationExpiredNotification;
use Illuminate\Console\Command;

class NotifyExpiredOutstation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outstation:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired outstation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $outstations = Outstation::with(['user'])->whereDate('due_date', '=', today()->subDay())->get();
        foreach ($outstations as $outstation) {
            $outstation->user->notify(new OutstationExpiredNotification($outstation));
        }
        return 0;
    }
}
