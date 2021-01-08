<?php

namespace Database\Seeders;

use App\Models\LeaveCategory;
use Illuminate\Database\Seeder;

class LeaveCategorySeeder extends Seeder
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
                'name' => 'Cuti Tahunan',
                'points' => 100,
                'limit' => 12
            ],
            [
                'name' => 'Cuti Alasan Penting',
                'points' => 97.5,
                'limit' => 60
            ],
            [
                'name' => 'Cuti Bersalin',
                'points' => 97.5,
                'limit' => 90
            ],
            [
                'name' => 'Cuti Sakit',
                'points' => 97.5,
                'limit' => 545
            ],
            [
                'name' => 'Cuti Diluar Tanggungan',
                'points' => 0,
                'limit' => 90
            ],

        ];

        foreach ($data as $category) {
            LeaveCategory::create($category);
        }
    }
}
