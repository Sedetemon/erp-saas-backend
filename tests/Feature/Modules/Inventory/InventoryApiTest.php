<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Pos\Models\PosCategory;
use App\Modules\Pos\Models\PosProduct;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryApiTest extends TenantTestCase
{
    protected static array $modulesToActivate = ['inventory', 'pos'];

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

    public function test_it_creates_a_standalone_inventory_item_with_initial_stock(): void
    {
        $response = $this->headers()->postJson('/api/inventory/items', [
            'name' => 'Draps de bain',
            'sku' => 'DRAP-001',
            'unit' => 'pcs',
            'category' => 'linge',
            'initial_quantity' => 30,
            'alert_threshold' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Draps de bain')
            ->assertJsonPath('stock.quantity', 30.0)
            ->assertJsonPath('stock.alert_threshold', 10.0)
            ->assertJsonPath('linked_to', null);

        $this->assertDatabaseHas('inventory_items', ['sku' => 'DRAP-001'], 'tenant');
        $this->assertDatabaseHas('inventory_stocks', ['quantity' => 30], 'tenant');
    }

    public function test_it_creates_an_inventory_item_linked_to_a_pos_product(): void
    {
        $category = PosCategory::create(['name' => 'Boissons']);
        $product = PosProduct::create([
            'pos_category_id' => $category->id,
            'name' => 'Bière 65cl',
            'sku' => 'BIERE-65',
            'price' => 2000,
        ]);

        $response = $this->headers()->postJson('/api/inventory/items', [
            'name' => 'Bière 65cl',
            'pos_product_id' => $product->id,
            'initial_quantity' => 100,
        ]);

        $response->assertCreated()->assertJsonPath('linked_to', 'PosProduct');

        $this->assertDatabaseHas('inventory_items', [
            'itemable_type' => PosProduct::class,
            'itemable_id' => $product->id,
        ], 'tenant');
    }

    public function test_it_rejects_a_duplicate_sku(): void
    {
        InventoryItem::create(['name' => 'Item A', 'sku' => 'DUP-1']);

        $response = $this->headers()->postJson('/api/inventory/items', [
            'name' => 'Item B',
            'sku' => 'DUP-1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('sku');
    }

    public function test_it_lists_and_filters_low_stock_items(): void
    {
        $low = InventoryItem::create(['name' => 'Savon']);
        $low->stock()->create(['quantity' => 2, 'alert_threshold' => 10]);

        $ok = InventoryItem::create(['name' => 'Shampoing']);
        $ok->stock()->create(['quantity' => 50, 'alert_threshold' => 10]);

        $response = $this->headers()->getJson('/api/inventory/items?low_stock=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Savon');
    }

    public function test_it_updates_the_alert_threshold(): void
    {
        $item = InventoryItem::create(['name' => 'Café']);
        $item->stock()->create(['quantity' => 20, 'alert_threshold' => 5]);

        $response = $this->headers()->putJson("/api/inventory/items/{$item->id}", [
            'alert_threshold' => 15,
        ]);

        $response->assertOk()->assertJsonPath('stock.alert_threshold', 15.0);
    }

    public function test_it_records_a_manual_in_movement_and_increments_stock(): void
    {
        $item = InventoryItem::create(['name' => 'Farine']);
        $item->stock()->create(['quantity' => 10, 'alert_threshold' => 5]);

        $response = $this->headers()->postJson('/api/inventory/movements', [
            'inventory_item_id' => $item->id,
            'type' => 'in',
            'quantity' => 25,
            'reason' => 'Réception fournisseur',
        ]);

        $response->assertCreated()->assertJsonPath('type', 'in');

        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_item_id' => $item->id,
            'quantity' => 35,
        ], 'tenant');
    }

    public function test_it_rejects_a_manual_out_movement_when_stock_is_insufficient(): void
    {
        $item = InventoryItem::create(['name' => 'Sucre']);
        $item->stock()->create(['quantity' => 3, 'alert_threshold' => 5]);

        $response = $this->headers()->postJson('/api/inventory/movements', [
            'inventory_item_id' => $item->id,
            'type' => 'out',
            'quantity' => 10,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_item_id' => $item->id,
            'quantity' => 3, // inchangé
        ], 'tenant');

        $this->assertDatabaseMissing('inventory_movements', [
            'inventory_item_id' => $item->id,
        ], 'tenant');
    }

    public function test_it_lists_movement_history_filtered_by_item(): void
    {
        $item = InventoryItem::create(['name' => 'Riz']);
        $item->stock()->create(['quantity' => 100, 'alert_threshold' => 10]);

        $this->headers()->postJson('/api/inventory/movements', [
            'inventory_item_id' => $item->id,
            'type' => 'out',
            'quantity' => 5,
        ]);

        $response = $this->headers()->getJson("/api/inventory/movements?inventory_item_id={$item->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_soft_deletes_an_item(): void
    {
        $item = InventoryItem::create(['name' => 'À supprimer']);

        $response = $this->headers()->deleteJson("/api/inventory/items/{$item->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('inventory_items', ['id' => $item->id], 'tenant');
    }
}
