<?php

namespace App\Console\Commands;

use App\Models\AbsentPermission;
use App\Notifications\AbsentPermissionExpiredNotification;
use Illuminate\Console\Command;

class NotifyExpiredPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired absent permission';

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
        $permissions = AbsentPermission::with(['user'])->whereDate('due_date', '=', today()->subDay())->get();
        foreach ($permissions as $permission) {
            $permission->user->notify(new AbsentPermissionExpiredNotification($permission));
        }
        return 0;
    }
}
