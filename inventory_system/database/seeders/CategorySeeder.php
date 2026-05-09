<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics',      'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing',          'description' => 'Apparel and fashion items'],
            ['name' => 'Food & Beverage',   'description' => 'Food products and drinks'],
            ['name' => 'Home & Garden',     'description' => 'Home improvement and garden supplies'],
            ['name' => 'Sports & Outdoors', 'description' => 'Sports equipment and outdoor gear'],
            ['name' => 'Books',             'description' => 'Books, magazines, and publications'],
            ['name' => 'Toys & Games',      'description' => 'Children toys and board games'],
            ['name' => 'Health & Beauty',   'description' => 'Health, wellness, and beauty products'],
            ['name' => 'Automotive',        'description' => 'Car parts and accessories'],
            ['name' => 'Office Supplies',   'description' => 'Stationery and office equipment'],
        ];
 
        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['name' => $data['name']],
                [
                    'slug'        => Str::slug($data['name']),
                    'description' => $data['description'],
                    'is_active'   => true,
                ]
            );
        }
 
        // Add a few random ones
        Category::factory()->count(5)->create();
    }
}
