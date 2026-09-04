<?php

namespace App\Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Http\Requests\StorePosProductRequest;
use App\Modules\Pos\Http\Requests\UpdatePosProductRequest;
use App\Modules\Pos\Http\Resources\PosProductResource;
use App\Modules\Pos\Models\PosProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PosProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = PosProduct::with('category')
            ->when($request->filled('pos_category_id'), fn ($q) => $q->where('pos_category_id', $request->string('pos_category_id')))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(30);

        return PosProductResource::collection($products)->response();
    }

    public function store(StorePosProductRequest $request): JsonResponse
    {
        $product = PosProduct::create($request->validated());

        return response()->json(new PosProductResource($product->load('category')), 201);
    }

    public function show(PosProduct $product): JsonResponse
    {
        return response()->json(new PosProductResource($product->load('category')));
    }

    // Paramètre nommé $product (et non $posProduct) : doit correspondre
    // exactement au segment {product} déclaré dans routes/tenant.php,
    // sinon Laravel ne fait pas la liaison implicite et instancie un
    // modèle vide au lieu de charger le vrai enregistrement.
    public function update(UpdatePosProductRequest $request, PosProduct $product): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['sku'])) {
            $duplicate = PosProduct::where('sku', $data['sku'])
                ->where('id', '!=', $product->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'sku' => 'Le SKU est déjà utilisé par un autre produit.',
                ]);
            }
        }

        $product->update($data);

        return response()->json(new PosProductResource($product->load('category')));
    }

    public function destroy(PosProduct $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
