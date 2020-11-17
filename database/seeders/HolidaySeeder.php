<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Calendarific\Calendarific;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dates = Calendarific::make(
            env('CALENDARIFIC_KEY'),
            'ID',
            '2020',
        );

        foreach ($dates['response']['holidays'] as $holiday) {
            Holiday::create([
                'name' => $holiday['name'],
                'description' => $holiday['description'],
                'date' => Carbon::parse($holiday['date']['iso'])
            ]);
        }
    }
}
