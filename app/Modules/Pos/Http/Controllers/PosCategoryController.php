<?php
// app/Modules/Pos/Http/Controllers/PosCategoryController.php

namespace App\Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Models\PosCategory;
use App\Modules\Pos\Http\Requests\StorePosCategoryRequest;
use App\Modules\Pos\Http\Requests\UpdatePosCategoryRequest;
use Illuminate\Support\Str;

class PosCategoryController extends Controller
{
    public function index()
    {
        $categories = PosCategory::orderBy('sort_order')->get();

        // ✅ RETOURNER LES DONNÉES
        return response()->json([
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'created_at' => $category->created_at,
                ];
            }),
        ]);
    }

    public function store(StorePosCategoryRequest $request)
    {
        $category = PosCategory::create([
            'id' => (string) Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        // ✅ RETOURNER LA CATÉGORIE CRÉÉE
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'created_at' => $category->created_at,
        ], 201);
    }

    public function show(string $id)
    {
        $category = PosCategory::findOrFail($id);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'created_at' => $category->created_at,
        ]);
    }

    public function update(UpdatePosCategoryRequest $request, string $id)
    {
        $category = PosCategory::findOrFail($id);
        $category->update($request->validated());

        // ✅ RETOURNER LA CATÉGORIE MIS À JOUR
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'created_at' => $category->created_at,
        ]);
    }

    public function destroy(string $id)
    {
        $category = PosCategory::findOrFail($id);
        $category->delete();

        return response()->noContent();
    }
}
