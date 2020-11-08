<?php

namespace Database\Seeders;


use App\Models\AttendeType;
use App\Models\AttendeCode;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class AttendeCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = AttendeType::inRandomOrder()->first();

        AttendeCode::create([
            'code' => Str::random(8),
            'code_type_id' => $types->id,
            'start_time' => "06:00",
            'end_date' => '08:00'
        ]);
    }
}
