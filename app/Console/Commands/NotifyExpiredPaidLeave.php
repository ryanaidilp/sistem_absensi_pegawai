<?php

namespace App\Console\Commands;

use App\Models\PaidLeave;
use App\Notifications\PaidLeaveExpiredNotification;
use Illuminate\Console\Command;

class NotifyExpiredPaidLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paidleave:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired paid leave';

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
        $paid_leaves = PaidLeave::with(['user'])->whereDate('due_date', '=', today()->subDay())->get();
        foreach ($paid_leaves as $paid_leave) {
            $paid_leave->user->notify(new PaidLeaveExpiredNotification($paid_leave));
        }
        return 0;
    }
}
