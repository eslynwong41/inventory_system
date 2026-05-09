<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySupplierTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    /*
    |--------------------------------------------------------------------------
    | Category Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_categories(): void
    {
        Category::factory()->count(5)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'is_active']]]);
    }

    public function test_can_create_category(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name'        => 'Test Category',
                'description' => 'A test category description',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Category');

        $this->assertDatabaseHas('categories', ['name' => 'Test Category', 'slug' => 'test-category']);
    }

    public function test_cannot_create_duplicate_category_name(): void
    {
        Category::factory()->create(['name' => 'Electronics']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/categories', ['name' => 'Electronics'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", ['name' => 'Updated Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_suppliers(): void
    {
        Supplier::factory()->count(5)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/suppliers')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'is_active']],
                'meta',
            ]);
    }

    public function test_can_create_supplier(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/suppliers', [
                'name'    => 'New Supplier Ltd',
                'email'   => 'supplier@newsupplier.com',
                'country' => 'Malaysia',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'New Supplier Ltd');
    }

    public function test_cannot_create_supplier_with_duplicate_email(): void
    {
        Supplier::factory()->create(['email' => 'existing@supplier.com']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/suppliers', [
                'name'  => 'Another Supplier',
                'email' => 'existing@supplier.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Updated Supplier Name',
                'city' => 'Kuala Lumpur',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Supplier Name');
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(200);
    }

    public function test_supplier_full_address_accessor(): void
    {
        $supplier = Supplier::factory()->make([
            'address' => '123 Main St',
            'city'    => 'Kuala Lumpur',
            'country' => 'Malaysia',
        ]);

        $this->assertEquals('123 Main St, Kuala Lumpur, Malaysia', $supplier->full_address);
    }
}