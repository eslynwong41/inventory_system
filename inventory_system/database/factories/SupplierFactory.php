<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;
 
    public function definition(): array
    {
        return [
            'name'           => $this->faker->company(),
            'email'          => $this->faker->unique()->companyEmail(),
            'phone'          => $this->faker->optional()->phoneNumber(),
            'address'        => $this->faker->optional()->streetAddress(),
            'city'           => $this->faker->optional()->city(),
            'country'        => $this->faker->optional()->country(),
            'contact_person' => $this->faker->optional()->name(),
            'is_active'      => $this->faker->boolean(85),
        ];
    }
}
