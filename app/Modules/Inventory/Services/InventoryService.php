<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Trouve l'InventoryItem lié à une entité source (ex: PosProduct), s'il existe.
     */
    public function itemFor(object $itemable): ?InventoryItem
    {
        return InventoryItem::query()
            ->where('itemable_type', get_class($itemable))
            ->where('itemable_id', $itemable->getKey())
            ->first();
    }

    /**
     * Vérifie que le stock est suffisant pour la quantité demandée.
     * Un item sans fiche de stock (non suivi) est toujours considéré comme suffisant :
     * on ne bloque la vente que pour les items explicitement suivis en stock.
     */
    public function hasSufficientStock(InventoryItem $item, float $quantity): bool
    {
        $stock = $item->stock;

        return ! $stock || $stock->hasSufficientQuantity($quantity);
    }

    /**
     * Enregistre un mouvement de stock ('in', 'out' ou 'adjustment') et met à jour
     * la quantité en base de façon atomique (verrou pessimiste pour éviter les
     * conditions de course entre deux ventes simultanées sur le même item).
     *
     * @throws InsufficientStockException si le mouvement est une sortie et que le
     *         stock déjà suivi est insuffisant.
     */
    public function recordMovement(
        InventoryItem $item,
        string $type,
        float $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
        ?string $createdBy = null,
    ): InventoryMovement {
        return DB::connection('tenant')->transaction(function () use ($item, $type, $quantity, $referenceType, $referenceId, $reason, $createdBy) {
            $stock = InventoryStock::where('inventory_item_id', $item->id)->lockForUpdate()->first();

            if ($stock) {
                if ($type === 'out' && ! $stock->hasSufficientQuantity($quantity)) {
                    throw new InsufficientStockException($item->name, (float) $stock->quantity, $quantity);
                }

                $stock->quantity = match ($type) {
                    'in' => (float) $stock->quantity + $quantity,
                    'out' => (float) $stock->quantity - $quantity,
                    'adjustment' => $quantity, // l'ajustement fixe la quantité absolue
                };
                $stock->save();
            }
            // Item non suivi (pas de fiche de stock) : le mouvement est quand même
            // journalisé pour l'historique, mais aucune quantité n'est décrémentée.

            return InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Raccourci pour une vente (sortie de stock).
     */
    public function recordSale(
        InventoryItem $item,
        float $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
        ?string $createdBy = null,
    ): InventoryMovement {
        return $this->recordMovement($item, 'out', $quantity, $referenceType, $referenceId, $reason, $createdBy);
    }
}
