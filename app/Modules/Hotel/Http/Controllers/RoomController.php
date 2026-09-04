<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\StoreRoomRequest;
use App\Modules\Hotel\Http\Resources\RoomResource;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use App\Modules\Hotel\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rooms = Room::with('roomType')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('room_type_id'), fn ($q) => $q->where('room_type_id', $request->string('room_type_id')))
            ->orderBy('number')
            ->paginate(30);

        return RoomResource::collection($rooms)->response();
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated());

        return response()->json(new RoomResource($room->load('roomType')), 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json(new RoomResource($room->load('roomType')));
    }

    public function update(StoreRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return response()->json(new RoomResource($room->load('roomType')));
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json(null, 204);
    }

    public function availability(Request $request, RoomType $roomType, ReservationService $service): JsonResponse
    {
        $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $rooms = $service->availableRooms(
            $roomType,
            new \DateTime($request->string('check_in')),
            new \DateTime($request->string('check_out')),
        );

        return RoomResource::collection($rooms)->response();
    }
}
