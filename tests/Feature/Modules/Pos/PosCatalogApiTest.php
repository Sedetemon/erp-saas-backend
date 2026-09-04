<?php

namespace Tests\Feature\Modules\Pos;

use App\Models\User;
use App\Modules\Pos\Models\PosCategory;
use App\Modules\Pos\Models\PosProduct;
use App\Modules\Pos\Models\PosTable;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class PosCatalogApiTest extends TenantTestCase
{
    protected static array $modulesToActivate = ['pos'];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    // ---------------------------------------------------------------
    // PosCategory
    // ---------------------------------------------------------------

    public function test_it_creates_a_category(): void
    {
        $response = $this->headers()->postJson('/api/pos/categories', [
            'name'       => 'Boissons',
            'sort_order' => 1,
        ]);

        $response->assertCreated()->assertJsonPath('name', 'Boissons');

        $this->assertDatabaseHas('pos_categories', ['name' => 'Boissons'], 'tenant');
    }

    public function test_it_lists_categories(): void
    {
        PosCategory::create(['name' => 'Plats', 'sort_order' => 0]);
        PosCategory::create(['name' => 'Desserts', 'sort_order' => 1]);

        $response = $this->headers()->getJson('/api/pos/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_it_updates_a_category(): void
    {
        $category = PosCategory::create(['name' => 'Boissons', 'sort_order' => 0]);

        $response = $this->headers()->putJson("/api/pos/categories/{$category->id}", [
            'name' => 'Boissons chaudes',
        ]);

        $response->assertOk()->assertJsonPath('name', 'Boissons chaudes');
    }

    public function test_it_deletes_a_category(): void
    {
        $category = PosCategory::create(['name' => 'Temporaire']);

        $response = $this->headers()->deleteJson("/api/pos/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('pos_categories', ['id' => $category->id], 'tenant');
    }

    // ---------------------------------------------------------------
    // PosProduct
    // ---------------------------------------------------------------

    public function test_it_creates_a_product(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);

        $response = $this->headers()->postJson('/api/pos/products', [
            'pos_category_id' => $category->id,
            'name'            => 'Coca-Cola 33cl',
            'sku'             => 'COCA-33',
            'price'           => 1500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Coca-Cola 33cl')
            ->assertJsonPath('category.id', $category->id);
    }

    public function test_it_rejects_duplicate_sku(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);

        PosProduct::create([
            'pos_category_id' => $category->id,
            'name'            => 'Coca-Cola',
            'sku'             => 'COCA-33',
            'price'           => 1500,
        ]);

        $response = $this->headers()->postJson('/api/pos/products', [
            'pos_category_id' => $category->id,
            'name'            => 'Coca-Cola bis',
            'sku'             => 'COCA-33',
            'price'           => 1500,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['sku']);
    }

    public function test_updating_a_product_without_changing_its_own_sku_does_not_trigger_a_duplicate_error(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);

        $product = PosProduct::create([
            'pos_category_id' => $category->id,
            'name'            => 'Coca-Cola',
            'sku'             => 'COCA-33',
            'price'           => 1500,
        ]);

        $response = $this->headers()->putJson("/api/pos/products/{$product->id}", [
            'pos_category_id' => $category->id,
            'name'            => 'Coca-Cola (mis à jour)',
            'sku'             => 'COCA-33', // même SKU, ne doit pas être rejeté
            'price'           => 1600,
        ]);

        $response->assertOk()->assertJsonPath('name', 'Coca-Cola (mis à jour)');
    }

    public function test_it_lists_products(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);
        PosProduct::create(['pos_category_id' => $category->id, 'name' => 'Eau', 'price' => 500]);
        PosProduct::create(['pos_category_id' => $category->id, 'name' => 'Jus', 'price' => 1000]);

        $response = $this->headers()->getJson('/api/pos/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_it_deletes_a_product(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);
        $product = PosProduct::create(['pos_category_id' => $category->id, 'name' => 'Eau', 'price' => 500]);

        $response = $this->headers()->deleteJson("/api/pos/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('pos_products', ['id' => $product->id], 'tenant');
    }

    // ---------------------------------------------------------------
    // PosTable
    // ---------------------------------------------------------------

    public function test_it_creates_a_table(): void
    {
        $response = $this->headers()->postJson('/api/pos/tables', [
            'name' => 'Table 5',
            'area' => 'Terrasse',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Table 5')
            ->assertJsonPath('status', 'free');
    }

    public function test_it_rejects_invalid_table_status(): void
    {
        $response = $this->headers()->postJson('/api/pos/tables', [
            'name'   => 'Table 6',
            'status' => 'closed', // hors enum autorisé
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_it_lists_tables_ordered_by_name(): void
    {
        PosTable::create(['name' => 'Table 2']);
        PosTable::create(['name' => 'Table 1']);

        $response = $this->headers()->getJson('/api/pos/tables');

        $response->assertOk();
        $this->assertSame('Table 1', $response->json('data.0.name'));
    }

    public function test_it_updates_table_status(): void
    {
        $table = PosTable::create(['name' => 'Table 3', 'status' => 'free']);

        $response = $this->headers()->putJson("/api/pos/tables/{$table->id}", [
            'name'   => 'Table 3',
            'status' => 'occupied',
        ]);

        $response->assertOk()->assertJsonPath('status', 'occupied');
    }
}
