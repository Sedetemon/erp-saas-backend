<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\StoreHousekeepingTaskRequest;
use App\Modules\Hotel\Http\Resources\HousekeepingTaskResource;
use App\Modules\Hotel\Models\HousekeepingTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousekeepingTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = HousekeepingTask::with(['room.roomType', 'assignedTo'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->string('room_id')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->string('assigned_to')))
            ->orderBy('created_at')
            ->paginate(30);

        return HousekeepingTaskResource::collection($tasks)->response();
    }

    public function store(StoreHousekeepingTaskRequest $request): JsonResponse
    {
        $task = HousekeepingTask::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(new HousekeepingTaskResource($task->fresh(['room', 'assignedTo'])), 201);
    }

    public function assign(Request $request, HousekeepingTask $housekeepingTask): JsonResponse
    {
        $request->validate(['assigned_to' => ['required', 'uuid', 'exists:users,id']]);

        $housekeepingTask->update(['assigned_to' => $request->input('assigned_to')]);

        return response()->json(new HousekeepingTaskResource($housekeepingTask->load('room', 'assignedTo')));
    }

    public function start(HousekeepingTask $housekeepingTask): JsonResponse
    {
        $housekeepingTask->start();

        return response()->json(new HousekeepingTaskResource($housekeepingTask->load('room')));
    }

    public function complete(HousekeepingTask $housekeepingTask): JsonResponse
    {
        $housekeepingTask->complete();

        return response()->json(new HousekeepingTaskResource($housekeepingTask->load('room')));
    }
}
