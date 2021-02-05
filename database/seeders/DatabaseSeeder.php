<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            GenderSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            AttendeTypeSeeder::class,
            AttendeStatusSeeder::class,
            LeaveCategorySeeder::class,
            ApprovalStatusSeeder::class,
            GovernmentEmployeeGroupSeeder::class
        ]);
    }
}
