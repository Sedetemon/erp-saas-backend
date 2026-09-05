<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Http\Requests\StoreInventoryMovementRequest;
use App\Modules\Inventory\Http\Resources\InventoryMovementResource;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movements = InventoryMovement::query()
            ->with('item')
            ->when($request->filled('inventory_item_id'), fn ($q) => $q->where('inventory_item_id', $request->inventory_item_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return InventoryMovementResource::collection($movements)->response();
    }

    public function store(StoreInventoryMovementRequest $request, InventoryService $service): JsonResponse
    {
        $item = InventoryItem::findOrFail($request->inventory_item_id);

        try {
            $movement = $service->recordMovement(
                item: $item,
                type: $request->type,
                quantity: (float) $request->quantity,
                referenceType: 'manual',
                reason: $request->reason,
                createdBy: $request->user()?->id,
            );
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new InventoryMovementResource($movement->load('item')), 201);
    }
}
