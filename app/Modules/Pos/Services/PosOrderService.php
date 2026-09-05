<?php

namespace App\Modules\Pos\Services;

use App\Modules\Hotel\Models\GuestLedger;
use App\Modules\Hotel\Models\Invoice;
use App\Modules\Hotel\Models\Reservation;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Pos\Models\PosOrder;
use App\Modules\Pos\Models\PosOrderItem;
use App\Modules\Pos\Models\PosProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosOrderService
{
    public function __construct(protected InventoryService $inventory = new InventoryService())
    {
    }

    public function createOrder(array $attributes): PosOrder
    {
        return PosOrder::create([
            'order_number' => $this->generateOrderNumber(),
            'pos_table_id' => $attributes['pos_table_id'] ?? null,
            'guest_id' => $attributes['guest_id'] ?? null,
            'reservation_id' => $attributes['reservation_id'] ?? null,
            'type' => $attributes['type'] ?? 'dine_in',
            'status' => 'open',
            'created_by' => $attributes['created_by'] ?? null,
        ]);
    }

    public function addItem(PosOrder $order, PosProduct $product, float $quantity = 1, ?string $notes = null): PosOrderItem
    {
        $item = $order->items()->create([
            'pos_product_id' => $product->id,
            'quantity' => (float) $quantity,
            'unit_price' => $product->price,
            'notes' => $notes,
        ]);

        $order->recalculateTotals();

        return $item;
    }

    public function removeItem(PosOrder $order, PosOrderItem $item): void
    {
        $item->delete();
        $order->recalculateTotals();
    }

    public function sendToKitchen(PosOrder $order): PosOrder
    {
        $order->update(['status' => 'sent_to_kitchen']);

        return $order->fresh();
    }

    public function markServed(PosOrder $order): PosOrder
    {
        $order->update(['status' => 'served']);

        return $order->fresh();
    }

    /**
     * Clôture la commande, déduit les stocks et gère l'imputation chambre si nécessaire.
     */
    public function closeOrder(PosOrder $order, string $paymentMethod, float $taxRate = 0.0): PosOrder
    {
        return DB::transaction(function () use ($order, $paymentMethod, $taxRate) {
            // 1. Recalcul des totaux de la commande
            $order->recalculateTotals($taxRate);

            // 2. Déduction automatique du stock pour chaque article commandé
            //    (uniquement pour les produits explicitement suivis en inventaire ;
            //    lève une InsufficientStockException — qui annule toute la clôture
            //    via le rollback de transaction — si le stock suivi est insuffisant)
            foreach ($order->items as $item) {
                $inventoryItem = $this->inventory->itemFor($item->product);

                if ($inventoryItem) {
                    $this->inventory->recordSale(
                        item: $inventoryItem,
                        quantity: (float) $item->quantity,
                        referenceType: 'pos_order',
                        referenceId: $order->id,
                        reason: "Vente POS / Commande #{$order->order_number}",
                        createdBy: $order->created_by,
                    );
                }
            }

            // 3. Gestion de l'imputation sur la chambre (Guest Ledger / Folio) si applicable
            if ($paymentMethod === 'room_charge' && $order->reservation_id) {
                $invoice = $this->chargeToRoomInvoice($order);
                $order->update(['invoice_id' => $invoice->id]);

                GuestLedger::create([
                    'reservation_id' => $order->reservation_id,
                    'guest_id' => $order->guest_id,
                    'type' => 'charge',
                    'source' => 'pos_order',
                    'source_id' => $order->id,
                    'description' => "Consommation Bar/POS - Commande #{$order->order_number}",
                    'amount' => $order->total,
                ]);
            }

            // 4. Passage du statut de la commande à 'closed'
            $order->update([
                'status' => 'closed',
                'payment_method' => $paymentMethod,
                'closed_at' => now(),
            ]);

            // 5. Libération de la table POS si associée
            if ($order->pos_table_id) {
                $order->table?->update(['status' => 'free']);
            }

            return $order->fresh(['items.product', 'invoice']);
        });
    }

    protected function chargeToRoomInvoice(PosOrder $order): Invoice
    {
        $reservation = Reservation::findOrFail($order->reservation_id);

        $invoice = Invoice::where('reservation_id', $reservation->id)
            ->where('status', 'draft')
            ->first();

        if (! $invoice) {
            $invoice = Invoice::create([
                'invoice_number' => 'FACT-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'status' => 'draft',
            ]);
        }

        $invoice->items()->create([
            'description' => "Restaurant/Bar — commande {$order->order_number}",
            'quantity' => 1,
            'unit_price' => $order->total,
            'total' => $order->total,
        ]);

        $invoice->recalculateTotals();

        return $invoice;
    }

    protected function generateOrderNumber(): string
    {
        return 'CMD-'.now()->format('Y').'-'.strtoupper(Str::random(6));
    }
}
