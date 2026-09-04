<?php

namespace App\Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Http\Requests\AddPosOrderItemRequest;
use App\Modules\Pos\Http\Requests\ClosePosOrderRequest;
use App\Modules\Pos\Http\Requests\StorePosOrderRequest;
use App\Modules\Pos\Http\Resources\PosOrderResource;
use App\Modules\Pos\Models\PosOrder;
use App\Modules\Pos\Models\PosOrderItem;
use App\Modules\Pos\Models\PosProduct;
use App\Modules\Pos\Models\PosTable;
use App\Modules\Pos\Services\PosOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosOrderController extends Controller
{
    public function __construct(protected PosOrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $orders = PosOrder::with(['table', 'guest', 'items.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(30);

        return PosOrderResource::collection($orders)->response();
    }

    public function store(StorePosOrderRequest $request): JsonResponse
    {
        $order = $this->orders->createOrder([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        if ($order->pos_table_id) {
            PosTable::whereKey($order->pos_table_id)->update(['status' => 'occupied']);
        }

        return response()->json(new PosOrderResource($order->load('table', 'guest')), 201);
    }

    public function show(PosOrder $posOrder): JsonResponse
    {
        $posOrder->load(['table', 'guest', 'reservation', 'items.product']);

        return response()->json(new PosOrderResource($posOrder));
    }

    public function addItem(AddPosOrderItemRequest $request, PosOrder $posOrder): JsonResponse
    {
        $product = PosProduct::findOrFail($request->input('pos_product_id'));

        $this->orders->addItem(
            $posOrder,
            $product,
            (float) $request->input('quantity', 1),
            $request->input('notes'),
        );

        return response()->json(new PosOrderResource($posOrder->fresh(['items.product'])));
    }

    public function removeItem(PosOrder $posOrder, PosOrderItem $item): JsonResponse
    {
        $this->orders->removeItem($posOrder, $item);

        return response()->json(new PosOrderResource($posOrder->fresh(['items.product'])));
    }

    public function sendToKitchen(PosOrder $posOrder): JsonResponse
    {
        return response()->json(new PosOrderResource($this->orders->sendToKitchen($posOrder)));
    }

    public function markServed(PosOrder $posOrder): JsonResponse
    {
        return response()->json(new PosOrderResource($this->orders->markServed($posOrder)));
    }

    public function close(ClosePosOrderRequest $request, PosOrder $posOrder): JsonResponse
    {
        $order = $this->orders->closeOrder($posOrder, $request->input('payment_method'));

        return response()->json(new PosOrderResource($order));
    }
}
