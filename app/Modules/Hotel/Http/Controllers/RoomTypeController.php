<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\StoreRoomTypeRequest;
use App\Modules\Hotel\Http\Resources\RoomTypeResource;
use App\Modules\Hotel\Models\RoomType;
use Illuminate\Http\JsonResponse;

class RoomTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $roomTypes = RoomType::withCount('rooms')->orderBy('name')->paginate(20);

        return RoomTypeResource::collection($roomTypes)->response();
    }

    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $roomType = RoomType::create($request->validated());

        return response()->json(new RoomTypeResource($roomType), 201);
    }

    public function show(RoomType $roomType): JsonResponse
    {
        $roomType->loadCount('rooms');

        return response()->json(new RoomTypeResource($roomType));
    }

    public function update(StoreRoomTypeRequest $request, RoomType $roomType): JsonResponse
    {
        $roomType->update($request->validated());

        return response()->json(new RoomTypeResource($roomType));
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $roomType->delete();

        return response()->json(null, 204);
    }
}
