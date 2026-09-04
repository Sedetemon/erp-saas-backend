<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\StoreGuestRequest;
use App\Modules\Hotel\Http\Resources\GuestResource;
use App\Modules\Hotel\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $guests = Guest::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                );
            })
            ->orderBy('last_name')
            ->paginate(20);

        return GuestResource::collection($guests)->response();
    }

    public function store(StoreGuestRequest $request): JsonResponse
    {
        $guest = Guest::create($request->validated());

        return response()->json(new GuestResource($guest), 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        return response()->json(new GuestResource($guest));
    }

    public function update(StoreGuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return response()->json(new GuestResource($guest));
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $guest->delete();

        return response()->json(null, 204);
    }
}
