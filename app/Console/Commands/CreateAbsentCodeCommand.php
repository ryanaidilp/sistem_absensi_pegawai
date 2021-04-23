<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use App\Models\AttendeType;
use App\Models\AttendeCode;
use Illuminate\Console\Command;
use App\Repositories\Interfaces\HolidayRepositoryInterface;

class CreateAbsentCodeCommand extends Command
{

    private $holidayRepository;
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
    public function __construct(HolidayRepositoryInterface $holidayRepository)
    {
        parent::__construct();
        $this->holidayRepository = $holidayRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $types = AttendeType::all();

        $holiday = $this->holidayRepository->getToday();
        $isWeekend = today()->isWeekend();

        $attendeCode = AttendeCode::whereDate('created_at', today())->get();
        if ($attendeCode->count() > 0) {
            $this->error('Attende code already generated!');
            return;
        }

        if (!$holiday && !$isWeekend) {
            foreach ($types as $type) {
                do {
                    $code = Str::random(rand(8, 16));
                    $attendeCode = AttendeCode::where('code', $code)->first();
                } while (!is_null($attendeCode));
                $start_time = [
                    'Absen Pagi' => '07:00',
                    'Absen Istrahat' => now()->isFriday() ? '11:30' : '12:00',
                    'Absen Siang' => '13:00',
                    'Absen Pulang' => now()->isFriday() ? '16:30' : '16:00'
                ][$type->name];
                $end_time = [
                    'Absen Pagi' => '08:00',
                    'Absen Istrahat' => '12:59',
                    'Absen Siang' => '14:00',
                    'Absen Pulang' => '18:00'
                ][$type->name];
                $type->kode_absen()->create(
                    [
                        'code' => $code,
                        'start_time' => $start_time,
                        'end_time' => $end_time,

                    ]
                );
            }
            $this->info('Succesfully created absent code!');
        } else if ($holiday) {
            $this->info('Today is holiday!');
        } else if ($isWeekend) {
            $this->info('Today is weekend!');
        } else {
            $this->error('Errors : Failed to generate absent code!');
        }
        return 0;
    }
}
