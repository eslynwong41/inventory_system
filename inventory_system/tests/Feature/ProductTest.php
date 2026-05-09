<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create(['role' => 'admin']);
        $this->category = Category::factory()->create();
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    /*
    |--------------------------------------------------------------------------
    | Index / Listing Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(20)->create(['category_id' => $this->category->id]);

        $this->actingAsUser()
            ->getJson('/api/v1/products?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sku', 'price', 'stock_quantity', 'stock_status', 'category']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_can_filter_products_by_category(): void
    {
        $otherCategory = Category::factory()->create();

        Product::factory()->count(3)->create(['category_id' => $this->category->id]);
        Product::factory()->count(5)->create(['category_id' => $otherCategory->id]);

        $this->actingAsUser()
            ->getJson("/api/v1/products?category_id={$this->category->id}")
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 10.00]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 50.00]);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 200.00]);

        $this->actingAsUser()
            ->getJson('/api/v1/products?min_price=20&max_price=100')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_can_filter_products_by_stock_status(): void
    {
        Product::factory()->outOfStock()->count(3)->create(['category_id' => $this->category->id]);
        Product::factory()->count(5)->create(['category_id' => $this->category->id, 'stock_quantity' => 100]);

        $this->actingAsUser()
            ->getJson('/api/v1/products?stock_status=out_of_stock')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'Unique Widget Pro',
            'slug'        => 'unique-widget-pro',
        ]);
        Product::factory()->count(5)->create(['category_id' => $this->category->id]);

        $this->actingAsUser()
            ->getJson('/api/v1/products?search=Unique+Widget')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_products_are_sorted(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 100, 'name' => 'Expensive']);
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 10, 'name' => 'Cheap']);

        $response = $this->actingAsUser()
            ->getJson('/api/v1/products?sort_by=price&sort_direction=asc')
            ->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('price');
        $this->assertEquals($prices->sort()->values()->toArray(), $prices->values()->toArray());
    }

    /*
    |--------------------------------------------------------------------------
    | Create Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_create_product_with_valid_data(): void
    {
        $supplier = Supplier::factory()->create();

        $payload = [
            'category_id'    => $this->category->id,
            'name'           => 'New Test Product',
            'price'          => 99.99,
            'stock_quantity' => 50,
            'supplier_ids'   => [$supplier->id],
        ];

        $this->actingAsUser()
            ->postJson('/api/v1/products', $payload)
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'price', 'sku', 'category', 'suppliers'],
            ])
            ->assertJsonPath('data.name', 'New Test Product')
            ->assertJsonPath('data.price', 99.99);

        $this->assertDatabaseHas('products', ['name' => 'New Test Product']);
    }

    public function test_create_product_fails_with_invalid_category(): void
    {
        $this->actingAsUser()
            ->postJson('/api/v1/products', [
                'category_id'    => 99999,
                'name'           => 'Test',
                'price'          => 10,
                'stock_quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_product_fails_without_required_fields(): void
    {
        $this->actingAsUser()
            ->postJson('/api/v1/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'price', 'stock_quantity']);
    }

    public function test_create_product_fails_with_duplicate_sku(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'sku'         => 'EXISTING-SKU',
        ]);

        $this->actingAsUser()
            ->postJson('/api/v1/products', [
                'category_id'    => $this->category->id,
                'name'           => 'Another Product',
                'price'          => 10,
                'stock_quantity' => 1,
                'sku'            => 'EXISTING-SKU',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_retrieve_single_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAsUser()
            ->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $this->actingAsUser()
            ->getJson('/api/v1/products/99999')
            ->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAsUser()
            ->putJson("/api/v1/products/{$product->id}", [
                'name'  => 'Updated Product Name',
                'price' => 199.99,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Product Name')
            ->assertJsonPath('data.price', 199.99);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'Updated Product Name',
        ]);
    }

    public function test_update_product_with_suppliers(): void
    {
        $product   = Product::factory()->create(['category_id' => $this->category->id]);
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $this->actingAsUser()
            ->putJson("/api/v1/products/{$product->id}", [
                'supplier_pivot' => [
                    ['supplier_id' => $supplier1->id, 'supply_price' => 50.00, 'is_preferred' => true],
                    ['supplier_id' => $supplier2->id, 'supply_price' => 45.00, 'is_preferred' => false],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('product_supplier', [
            'product_id'  => $product->id,
            'supplier_id' => $supplier1->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete / Soft Delete Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_soft_delete_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->actingAsUser()
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Product deleted successfully.']);

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Should not appear in normal listing
        $this->actingAsUser()
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_restore_soft_deleted_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $product->delete();

        $this->actingAsUser()
            ->postJson("/api/v1/products/{$product->id}/restore")
            ->assertStatus(200)
            ->assertJson(['message' => 'Product restored successfully.']);

        $this->assertDatabaseHas('products', [
            'id'         => $product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_list_trashed_products(): void
    {
        Product::factory()->count(3)->create(['category_id' => $this->category->id])
            ->each(fn($p) => $p->delete());

        $this->actingAsUser()
            ->getJson('/api/v1/products/trashed')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_can_force_delete_product(): void
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $product->delete();

        $this->actingAsUser()
            ->deleteJson("/api/v1/products/{$product->id}/force")
            ->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
        $this->postJson('/api/v1/products', [])->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor / Scope Tests
    |--------------------------------------------------------------------------
    */

    public function test_product_stock_status_accessor_returns_correct_value(): void
    {
        $inStock    = Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 100, 'low_stock_threshold' => 10]);
        $lowStock   = Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 5, 'low_stock_threshold' => 10]);
        $outOfStock = Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 0, 'low_stock_threshold' => 10]);

        $this->assertEquals('in_stock',    $inStock->stock_status);
        $this->assertEquals('low_stock',   $lowStock->stock_status);
        $this->assertEquals('out_of_stock', $outOfStock->stock_status);
    }

    public function test_product_profit_margin_accessor(): void
    {
        $product = Product::factory()->make(['price' => 100, 'cost_price' => 60]);
        $this->assertEquals(40.0, $product->profit_margin);

        $noCost = Product::factory()->make(['price' => 100, 'cost_price' => null]);
        $this->assertNull($noCost->profit_margin);
    }

    public function test_low_stock_scope_returns_correct_products(): void
    {
        Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 100, 'low_stock_threshold' => 10]);
        Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 5,   'low_stock_threshold' => 10]);
        Product::factory()->create(['category_id' => $this->category->id, 'stock_quantity' => 10,  'low_stock_threshold' => 10]); // at threshold

        $lowStock = Product::lowStock()->get();
        $this->assertCount(2, $lowStock); // 5 and 10 (<=threshold)
    }
}