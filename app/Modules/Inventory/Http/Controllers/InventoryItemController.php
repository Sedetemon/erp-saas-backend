<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\StoreInventoryItemRequest;
use App\Modules\Inventory\Http\Requests\UpdateInventoryItemRequest;
use App\Modules\Inventory\Http\Resources\InventoryItemResource;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Pos\Models\PosProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = InventoryItem::query()
            ->with('stock')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            }))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereHas('stock', fn ($q) => $q->lowStock()))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return InventoryItemResource::collection($items)->response();
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = DB::connection('tenant')->transaction(function () use ($request) {
            $data = $request->safe()->only(['name', 'sku', 'unit', 'category', 'is_active']);

            if ($request->filled('pos_product_id')) {
                $data['itemable_type'] = PosProduct::class;
                $data['itemable_id'] = $request->pos_product_id;
            }

            $item = InventoryItem::create($data);

            $item->stock()->create([
                'quantity' => $request->input('initial_quantity', 0),
                'alert_threshold' => $request->input('alert_threshold', 5),
            ]);

            return $item;
        });

        return response()->json(new InventoryItemResource($item->load('stock')), 201);
    }

    public function show(InventoryItem $item): JsonResponse
    {
        return response()->json(new InventoryItemResource($item->load('stock')));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item): JsonResponse
    {
        $item->update($request->safe()->only(['name', 'sku', 'unit', 'category', 'is_active']));

        if ($request->filled('alert_threshold') && $item->stock) {
            $item->stock->update(['alert_threshold' => $request->alert_threshold]);
        }

        return response()->json(new InventoryItemResource($item->load('stock')));
    }

    public function destroy(InventoryItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
