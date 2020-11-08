<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle,
            'slug' => Str::slug($this->faker->jobTitle)
        ];
    }
}
