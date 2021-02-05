<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Calendarific\Calendarific;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holiday:generate {--year=2021 : Year of holidays.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate holiday for a specific years';

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
        $year = (int) $this->option('year');
        $dates = Calendarific::make(
            env('CALENDARIFIC_KEY'),
            'ID',
            $year,
            null,
            null,
            null,
            ['national']
        );

        foreach ($dates['response']['holidays'] as $holiday) {
            Holiday::create([
                'name' => $holiday['name'],
                'description' => $holiday['description'],
                'date' => Carbon::parse($holiday['date']['iso'])
            ]);
        }
        $this->info('Holidays generated successfully!');
        return 0;
    }
}
