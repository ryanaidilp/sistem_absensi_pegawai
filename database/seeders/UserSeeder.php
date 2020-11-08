<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gender;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JeroenZwart\CsvSeeder\CsvSeeder;

class UserSeeder extends CsvSeeder
{

    public function __construct()
    {
        $this->file = '/database/csv/users.csv';
        $this->defaults = [
            'created_at' => now(),
            'updated_at' => now(),
            'remember_token' => Str::random(10)
        ];
        $this->hashable = ['password'];
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::disableQueryLog();
        parent::run();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
