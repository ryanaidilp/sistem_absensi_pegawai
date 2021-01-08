<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\AttendeStatus;
use Illuminate\Database\Seeder;

class AttendeStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'Tepat Waktu',
            'Terlambat',
            'Tidak Hadir',
            'Izin',
            'Cuti Tahunan',
            'Cuti Alasan Penting',
            'Cuti Bersalin',
            'Cuti Sakit',
            'Cuti Diluar Tanggungan'
        ];

        foreach ($data as $status) {
            AttendeStatus::create(
                [
                    'name' => $status,
                    'slug' => Str::slug($status)
                ]
            );
        }
    }
}
