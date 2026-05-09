<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Global Tech Distributors', 'email' => 'orders@globaltechdist.com', 'country' => 'USA', 'contact_person' => 'John Smith'],
            ['name' => 'EcoSupply Co.',             'email' => 'supply@ecosupply.co',       'country' => 'Canada', 'contact_person' => 'Sarah Lee'],
            ['name' => 'FastShip International',    'email' => 'info@fastship-intl.com',    'country' => 'UK', 'contact_person' => 'Mark Johnson'],
            ['name' => 'Asian Pacific Traders',     'email' => 'trade@asianpacific.com',    'country' => 'Singapore', 'contact_person' => 'Mei Lin'],
            ['name' => 'Continental Goods GmbH',    'email' => 'sales@continental-gmbh.de', 'country' => 'Germany', 'contact_person' => 'Klaus Müller'],
        ];
 
        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );
        }
 
        Supplier::factory()->count(10)->create();
    }
}
