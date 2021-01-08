<?php

namespace App\Console\Commands;

use App\Models\Attende;
use App\Models\AttendeCode;
use App\Models\AttendeStatus;
use App\Models\LeaveCategory;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateAttendeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absent:attende';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate attende for absent code.';

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
        $users = User::pns()->orWhere->honorer()->get();
        $codes = AttendeCode::whereDate('created_at', today())->get();
        if ($codes->count() > 0) {
            foreach ($codes as $code) {
                foreach ($users as $user) {
                    $status = Attende::ABSENT;
                    $permit = $user->izin()->whereDate('start_date', '<=', today())->whereDate('due_date', '>=', today())->first();
                    if (!is_null($permit) && $permit->is_approved) {
                        $status = Attende::PERMISSION;
                    }
                    $outstation = $user->dinas_luar()->whereDate('start_date', '<=', today())->whereDate('due_date', '>=', today())->first();
                    if (!is_null($outstation) && $outstation->is_approved) {
                        $status = Attende::OUTSTATION;
                    }
                    $paid_leave = $user->cuti()->whereDate('start_date', '<=', today())->whereDate('due_date', '>=', today())->first();
                    if (!is_null($paid_leave) && $paid_leave->is_approved) {
                        $status = AttendeStatus::where('name', $paid_leave->kategori->name)->first();
                        $status = $status->id;
                    }
                    Attende::create([
                        'user_id' => $user->id,
                        'attende_code_id' => $code->id,
                        'attende_status_id' => $status,
                    ]);
                }
            }
            $this->info('Attende list generated successfully!');
        } else {
            $this->info('No attende code for today!');
        }
        return 0;
    }
}
