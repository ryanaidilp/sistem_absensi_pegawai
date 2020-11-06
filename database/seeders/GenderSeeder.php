<?php

namespace Database\Seeders;

use App\Models\Gender;
use Illuminate\Database\Seeder;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Pria',
                'slug' => 'pria'
            ],
            [
                'name' => 'Wanita',
                'slug' => 'wanita'
            ],
        ];

        foreach ($data as $gender) {
            Gender::create($gender);
        }
    }
}
