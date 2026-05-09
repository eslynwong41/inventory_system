<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $suppliers  = Supplier::all();
 
        if ($categories->isEmpty() || $suppliers->isEmpty()) {
            $this->command->warn('No categories or suppliers found. Run CategorySeeder and SupplierSeeder first.');
            return;
        }
 
        // Create 50 normal products
        Product::factory()
            ->count(50)
            ->recycle($categories)
            ->create()
            ->each(function (Product $product) use ($suppliers) {
                // Attach 1-3 random suppliers with pivot data
                $attached = $suppliers->random(rand(1, 3));
                $syncData = [];
                foreach ($attached as $supplier) {
                    $syncData[$supplier->id] = [
                        'supply_price'   => round($product->price * rand(50, 75) / 100, 2),
                        'lead_time_days' => rand(1, 30),
                        'is_preferred'   => false,
                    ];
                }
                // Mark first as preferred
                if (! empty($syncData)) {
                    $firstKey = array_key_first($syncData);
                    $syncData[$firstKey]['is_preferred'] = true;
                }
                $product->suppliers()->sync($syncData);
            });
 
        // Create some low-stock products
        Product::factory()
            ->lowStock()
            ->count(10)
            ->recycle($categories)
            ->create()
            ->each(fn($p) => $p->suppliers()->attach($suppliers->random()->id));
 
        // Create some out-of-stock products
        Product::factory()
            ->outOfStock()
            ->count(5)
            ->recycle($categories)
            ->create();
 
        // Create some deleted (soft-deleted) products
        Product::factory()
            ->count(3)
            ->recycle($categories)
            ->create()
            ->each(fn($p) => $p->delete());
 
        $this->command->info('Products seeded successfully.');
    }
}
