<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AttendeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'Absen Hadir',
            'Absen Istrahat',
            'Absen Masuk Siang',
            'Absen Pulang'
        ];
    }
}
