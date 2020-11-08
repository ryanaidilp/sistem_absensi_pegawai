<?php

namespace Database\Factories;

use App\Models\AttendeCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendeCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttendeCode::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->unique()->strings,
        ];
    }
}
