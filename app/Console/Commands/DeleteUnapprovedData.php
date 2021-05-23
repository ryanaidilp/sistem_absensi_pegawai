<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\PaidLeave;
use App\Models\Outstation;
use Illuminate\Console\Command;
use App\Models\AbsentPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteUnapprovedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:unapproved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unapproved Permissions, Leave, & Outstations';

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
        $outstations = Outstation::where('is_approved', 0)
            ->whereDate('created_at', '<=', Carbon::now()->subWeeks(2))
            ->get();

        foreach ($outstations as  $outstation) {
            if (Storage::disk('public')->exists($outstation->photo)) {
                Storage::disk('public')->delete($outstation->photo);
            }

            $outstation->delete() ?
                info('Successfully delete Permission') :
                info('Failed to delete Permission ' . $outstation->id);
        }

        $leaves = PaidLeave::where('is_approved', 0)
            ->whereDate('created_at', '<=', Carbon::now()->subWeeks(2))
            ->get();

        foreach ($leaves as $leave) {
            if (Storage::disk('public')->exists($leave->photo)) {
                Storage::disk('public')->delete($leave->photo);
            }

            $leave->delete() ?
                info('Successfully delete Leave') :
                info('Failed to delete Leave ' . $leave->id);
        }

        $permissions = AbsentPermission::where('is_approved', 0)
            ->whereDate('created_at', '<=', Carbon::now()->subWeeks(2))
            ->get();

        foreach ($permissions as $permission) {
            if (Storage::disk('public')->exists($permission->photo)) {
                Storage::disk('public')->delete($permission->photo);
            }


            $permission->delete() ?
                info('Successfully delete Permission') :
                info('Failed to delete Permission ' . $permission->id);
        }

        $this->info('Check for Unapproved Data is finished!');

        return 0;
    }
}
