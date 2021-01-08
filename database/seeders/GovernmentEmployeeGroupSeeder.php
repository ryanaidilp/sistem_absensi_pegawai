<?php

namespace Database\Seeders;

use App\Models\GovernmentEmployeeGroup;
use Illuminate\Database\Seeder;

class GovernmentEmployeeGroupSeeder extends Seeder
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
                'rank' => 'Juru Muda',
                'group' => 'I/A'
            ],
            [
                'rank' => 'Juru Muda Tingkat 1',
                'group' => 'I/B'
            ],
            [
                'rank' => 'Juru',
                'group' => 'I/C'
            ],
            [
                'rank' => 'Juru Tingkat 1',
                'group' => 'I/D'
            ],
            [
                'rank' => 'Pengatur Muda',
                'group' => 'II/A'
            ],
            [
                'rank' => 'Pengatur Muda Tingkat 1',
                'group' => 'II/B'
            ],
            [
                'rank' => 'Pengatur',
                'group' => 'II/C'
            ],
            [
                'rank' => 'Pengatur Tingkat 1',
                'group' => 'II/D'
            ],
            [
                'rank' => 'Penata Muda',
                'group' => 'III/A'
            ],
            [
                'rank' => 'Penata Muda Tingkat 1',
                'group' => 'III/B'
            ],
            [
                'rank' => 'Penata',
                'group' => 'III/C'
            ],
            [
                'rank' => 'Penata Tingkat 1',
                'group' => 'III/D'
            ],
            [
                'rank' => 'Pembina',
                'group' => 'IV/A'
            ],
            [
                'rank' => 'Pembina Tingkat 1',
                'group' => 'IV/B'
            ],
            [
                'rank' => 'Pembina Utama Muda',
                'group' => 'IV/C'
            ],
            [
                'rank' => 'Pembina Utama Madya',
                'group' => 'IV/D'
            ],
            [
                'rank' => 'Pembina Utama',
                'group' => 'IV/E'
            ],

        ];

        foreach ($data as $group) {
            GovernmentEmployeeGroup::create($group);
        }
    }
}
