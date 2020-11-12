<?php

namespace Database\Seeders;

use App\Models\AttendeCode;
use App\Models\AttendeType;
use Illuminate\Support\Str;
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
            'Absen Pagi',
            'Absen Istrahat',
            'Absen Siang',
            'Absen Pulang'
        ];

        foreach ($data as $type) {
            AttendeType::create(
                [
                    'name' => $type,
                    'slug' => Str::slug($type)
                ]
            );
        }
    }
}
