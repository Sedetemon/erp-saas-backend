<?php

namespace Tests\Feature\Modules\Pos;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use App\Modules\Hotel\Services\ReservationService;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Pos\Models\PosCategory;
use App\Modules\Pos\Models\PosOrder;
use App\Modules\Pos\Models\PosProduct;
use App\Modules\Pos\Models\PosTable;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class PosOrderLifecycleApiTest extends TenantTestCase
{
    // Scénario room_charge : nécessite le module hôtel en plus du POS.
    protected static array $modulesToActivate = ['pos', 'hotel'];

    protected User $user;
    protected PosCategory $category;
    protected PosProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->category = PosCategory::create(['name' => 'Boissons']);
        $this->product = PosProduct::create([
            'pos_category_id' => $this->category->id,
            'name'            => 'Bière 65cl',
            'sku'             => 'BIERE-65',
            'price'           => 2000,
        ]);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    public function test_it_creates_a_dine_in_order_and_occupies_the_table(): void
    {
        $table = PosTable::create(['name' => 'Table 1', 'status' => 'free']);

        $response = $this->headers()->postJson('/api/pos/orders', [
            'pos_table_id' => $table->id,
            'type'         => 'dine_in',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('type', 'dine_in');

        $this->assertDatabaseHas('pos_tables', [
            'id'     => $table->id,
            'status' => 'occupied',
        ], 'tenant');
    }

    public function test_adding_an_item_recalculates_order_totals(): void
    {
        $order = PosOrder::create(['order_number' => 'CMD-TEST-1', 'type' => 'dine_in', 'status' => 'open']);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/items", [
            'pos_product_id' => $this->product->id,
            'quantity'       => 3,
        ]);

        $response->assertOk()->assertJsonPath('total', 6000);

        $this->assertDatabaseHas('pos_order_items', [
            'pos_order_id'   => $order->id,
            'pos_product_id' => $this->product->id,
            'quantity'       => 3,
            'total'          => 6000,
        ], 'tenant');
    }

    public function test_removing_an_item_recalculates_order_totals_down(): void
    {
        $order = PosOrder::create(['order_number' => 'CMD-TEST-2', 'type' => 'dine_in', 'status' => 'open']);

        $item = $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 2,
            'unit_price'     => 2000,
        ]);
        $order->recalculateTotals();

        $response = $this->headers()->deleteJson("/api/pos/orders/{$order->id}/items/{$item->id}");

        $response->assertOk()->assertJsonPath('total', 0);
    }

    public function test_it_sends_order_to_kitchen(): void
    {
        $order = PosOrder::create(['order_number' => 'CMD-TEST-3', 'type' => 'dine_in', 'status' => 'open']);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/send-to-kitchen");

        $response->assertOk()->assertJsonPath('status', 'sent_to_kitchen');
    }

    public function test_it_marks_order_as_served(): void
    {
        $order = PosOrder::create(['order_number' => 'CMD-TEST-4', 'type' => 'dine_in', 'status' => 'sent_to_kitchen']);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/serve");

        $response->assertOk()->assertJsonPath('status', 'served');
    }

    public function test_closing_an_order_with_cash_frees_the_table(): void
    {
        $table = PosTable::create(['name' => 'Table 2', 'status' => 'occupied']);

        $order = PosOrder::create([
            'order_number' => 'CMD-TEST-5',
            'pos_table_id' => $table->id,
            'type'         => 'dine_in',
            'status'       => 'served',
        ]);

        $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 2,
            'unit_price'     => 2000,
        ]);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'closed')
            ->assertJsonPath('payment_method', 'cash');

        $this->assertDatabaseHas('pos_tables', [
            'id'     => $table->id,
            'status' => 'free',
        ], 'tenant');
    }

    public function test_closing_an_order_decrements_stock_and_records_a_movement(): void
    {
        $item = InventoryItem::create([
            'name' => $this->product->name,
            'itemable_type' => PosProduct::class,
            'itemable_id' => $this->product->id,
        ]);
        $stock = $item->stock()->create(['quantity' => 50, 'alert_threshold' => 5]);

        $order = PosOrder::create(['order_number' => 'CMD-TEST-6', 'type' => 'dine_in', 'status' => 'served']);
        $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 4,
            'unit_price'     => 2000,
        ]);

        $this->headers()->postJson("/api/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'id'       => $stock->id,
            'quantity' => 46,
        ], 'tenant');

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type'           => 'out',
            'quantity'       => 4,
            'reference_type' => 'pos_order',
            'reference_id'   => $order->id,
        ], 'tenant');
    }

    public function test_closing_an_order_fails_when_stock_is_insufficient(): void
    {
        $item = InventoryItem::create([
            'name' => $this->product->name,
            'itemable_type' => PosProduct::class,
            'itemable_id' => $this->product->id,
        ]);
        $item->stock()->create(['quantity' => 2, 'alert_threshold' => 5]);

        $order = PosOrder::create(['order_number' => 'CMD-TEST-6B', 'type' => 'dine_in', 'status' => 'served']);
        $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 4,
            'unit_price'     => 2000,
        ]);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_item_id' => $item->id,
            'quantity'          => 2, // inchangé, la clôture a été annulée
        ], 'tenant');

        $this->assertDatabaseHas('pos_orders', [
            'id'     => $order->id,
            'status' => 'served', // pas passée à 'closed'
        ], 'tenant');
    }

    public function test_closing_an_order_without_a_stock_row_does_not_fail(): void
    {
        // Aucun InventoryStock pour ce produit : la déduction doit être
        // silencieusement ignorée, sans erreur.
        $order = PosOrder::create(['order_number' => 'CMD-TEST-7', 'type' => 'dine_in', 'status' => 'served']);
        $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 1,
            'unit_price'     => 2000,
        ]);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/close", [
            'payment_method' => 'cash',
        ]);

        $response->assertOk()->assertJsonPath('status', 'closed');
    }

    public function test_closing_an_order_with_room_charge_creates_an_invoice_and_ledger_entry(): void
    {
        $guest = Guest::factory()->create();
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'occupied']);

        $reservation = app(ReservationService::class)->createReservation(
            guest: $guest,
            checkIn: new \DateTime('2026-10-01'),
            checkOut: new \DateTime('2026-10-03'),
            roomBookings: [[
                'room_type_id'   => $roomType->id,
                'room_id'        => $room->id,
                'rate_per_night' => 30000,
            ]],
        );

        $order = PosOrder::create([
            'order_number'   => 'CMD-TEST-8',
            'type'           => 'room_service',
            'status'         => 'served',
            'reservation_id' => $reservation->id,
            'guest_id'       => $guest->id,
        ]);
        $order->items()->create([
            'pos_product_id' => $this->product->id,
            'quantity'       => 2,
            'unit_price'     => 2000,
        ]);

        $response = $this->headers()->postJson("/api/pos/orders/{$order->id}/close", [
            'payment_method' => 'room_charge',
        ]);

        $response->assertOk()->assertJsonPath('payment_method', 'room_charge');

        $invoiceId = $response->json('invoice_id');
        $this->assertNotNull($invoiceId);

        $this->assertDatabaseHas('invoices', [
            'id'             => $invoiceId,
            'reservation_id' => $reservation->id,
            'status'         => 'draft',
        ], 'tenant');

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoiceId,
            'total'      => 4000,
        ], 'tenant');

        $this->assertDatabaseHas('guest_ledgers', [
            'reservation_id' => $reservation->id,
            'type'           => 'charge',
            'source'         => 'pos_order',
            'source_id'      => $order->id,
            'amount'         => 4000,
        ], 'tenant');
    }

    public function test_it_lists_orders_filtered_by_status(): void
    {
        PosOrder::create(['order_number' => 'CMD-A', 'type' => 'dine_in', 'status' => 'open']);
        PosOrder::create(['order_number' => 'CMD-B', 'type' => 'dine_in', 'status' => 'closed']);

        $response = $this->headers()->getJson('/api/pos/orders?status=closed');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
