<?php
// app/Modules/Pos/Http/Controllers/PosTableController.php

namespace App\Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Models\PosTable;
use App\Modules\Pos\Http\Requests\StorePosTableRequest;
use App\Modules\Pos\Http\Requests\UpdatePosTableRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosTableController extends Controller
{
    public function index(Request $request)
    {
        $tables = PosTable::query()
            ->when($request->has('status'), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $tables->map(function ($table) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status ?? 'free',  // ✅ TOUJOURS UN STATUT
                    'capacity' => $table->capacity,
                    'is_active' => $table->is_active,
                    'created_at' => $table->created_at,
                ];
            }),
        ]);
    }

    public function store(StorePosTableRequest $request)
    {
        $table = PosTable::create([
            'id' => (string) Str::uuid(),
            'name' => $request->name,
            'status' => $request->status ?? 'free',  // ✅ VALEUR PAR DÉFAUT
            'capacity' => $request->capacity ?? 4,
            'is_active' => $request->is_active ?? true,
        ]);

        // ✅ RETOURNER LA TABLE CRÉÉE
        return response()->json([
            'id' => $table->id,
            'name' => $table->name,
            'status' => $table->status,
            'capacity' => $table->capacity,
            'is_active' => $table->is_active,
            'created_at' => $table->created_at,
        ], 201);
    }

    public function show(string $id)
    {
        $table = PosTable::findOrFail($id);

        return response()->json([
            'id' => $table->id,
            'name' => $table->name,
            'status' => $table->status,
            'capacity' => $table->capacity,
            'is_active' => $table->is_active,
            'created_at' => $table->created_at,
        ]);
    }

    public function update(UpdatePosTableRequest $request, string $id)
    {
        $table = PosTable::findOrFail($id);
        $table->update($request->validated());

        // ✅ RETOURNER LA TABLE MIS À JOUR
        return response()->json([
            'id' => $table->id,
            'name' => $table->name,
            'status' => $table->status,
            'capacity' => $table->capacity,
            'is_active' => $table->is_active,
            'created_at' => $table->created_at,
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:free,occupied,reserved',
        ]);

        $table = PosTable::findOrFail($id);
        $table->status = $request->status;
        $table->save();

        // ✅ RETOURNER LE STATUT MIS À JOUR
        return response()->json([
            'id' => $table->id,
            'name' => $table->name,
            'status' => $table->status,
            'capacity' => $table->capacity,
            'is_active' => $table->is_active,
            'created_at' => $table->created_at,
        ]);
    }

    public function destroy(string $id)
    {
        $table = PosTable::findOrFail($id);
        $table->delete();

        return response()->noContent();
    }
}
