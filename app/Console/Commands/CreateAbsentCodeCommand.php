<?php

namespace App\Console\Commands;

use App\Models\AttendeCode;
use App\Models\AttendeType;
use App\Models\Holiday;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class CreateAbsentCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absent:code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create absent code';

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

        $types = AttendeType::all();

        $types = AttendeType::all();

        $holiday = Holiday::whereDate('date', today())->first();

        if (!$holiday) {
            foreach ($types as $type) {
                do {
                    $code = Str::random(8);
                    $attendeCode = AttendeCode::where('code', $code)->first();
                } while (!is_null($attendeCode));
                $start_time = [
                    'Absen Pagi' => '07:00',
                    'Absen Istrahat' => now()->isFriday() ? '11:30' : '12:00',
                    'Absen Siang' => '13:00',
                    'Absen Pulang' => '16:30'
                ][$type->name];
                $end_time = [
                    'Absen Pagi' => '08:30',
                    'Absen Istrahat' => '12:59',
                    'Absen Siang' => '14:00',
                    'Absen Pulang' => '18:00'
                ][$type->name];
                $type->kode_absen()->create(
                    [
                        'code' => Str::random(8),
                        'start_time' => $start_time,
                        'end_time' => $end_time,

                    ]
                );
            }
            $this->info('Succesfully created absent code!');
        } else {
            $this->info('Today is holiday!');
        }
        return 0;
    }
}
