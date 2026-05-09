<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
 
        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'is_active'   => $this->faker->boolean(90),
        ];
    }
}
