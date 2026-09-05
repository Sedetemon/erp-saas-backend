<?php

use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\GeoController;
use App\Http\Controllers\Tenant\StaffController;
use App\Modules\Hotel\Http\Controllers\GuestController;
use App\Modules\Hotel\Http\Controllers\HousekeepingTaskController;
use App\Modules\Hotel\Http\Controllers\InvoiceController;
use App\Modules\Hotel\Http\Controllers\ReservationController;
use App\Modules\Hotel\Http\Controllers\RoomController;
use App\Modules\Hotel\Http\Controllers\RoomTypeController;
use App\Modules\Hr\Http\Controllers\AttendanceController;
use App\Modules\Hr\Http\Controllers\EmployeeContractController;
use App\Modules\Hr\Http\Controllers\EmployeeController;
use App\Modules\Hr\Http\Controllers\LeaveRequestController;
use App\Modules\Messaging\Http\Controllers\ConversationController;
use App\Modules\Messaging\Http\Controllers\MessageController;
use App\Modules\Pos\Http\Controllers\PosCategoryController;
use App\Modules\Pos\Http\Controllers\PosOrderController;
use App\Modules\Pos\Http\Controllers\PosProductController;
use App\Modules\Pos\Http\Controllers\PosTableController;
use App\Modules\Inventory\Http\Controllers\InventoryItemController;
use App\Modules\Inventory\Http\Controllers\InventoryMovementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Tenant
|--------------------------------------------------------------------------
*/

// --- Module Messagerie & Notifications ---
Route::middleware(['identify.tenant', 'auth:sanctum'])->prefix('messaging')->group(function () {
    // Conversations
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{id}', [ConversationController::class, 'show']);
    Route::post('conversations/{id}/mark-read', [ConversationController::class, 'markAsRead']);
    Route::post('conversations/{id}/close', [ConversationController::class, 'close']);
    Route::post('conversations/{id}/participants', [ConversationController::class, 'addParticipant']);
    Route::delete('conversations/{id}/participants/{userId}', [ConversationController::class, 'removeParticipant']);

    // Messages
    Route::get('conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::put('messages/{id}/read', [MessageController::class, 'markRead']);
    Route::delete('messages/{id}', [MessageController::class, 'destroy']);

    // Fichiers joints & Recherche
    Route::post('messages/{message}/attachments', [MessageController::class, 'uploadAttachment']);
    Route::delete('attachments/{attachment}', [MessageController::class, 'deleteAttachment']);
    Route::get('messages/search', [MessageController::class, 'search']);

    // Notifications
    Route::get('notifications', fn (Request $request) => $request->user()->notifications);
    Route::post('notifications/{id}/mark-as-read', function (string $id, Request $request) {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marquée comme lue']);
        }
        return response()->json(['error' => 'Notification non trouvée'], 404);
    });
    Route::post('notifications/mark-all-read', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    });
});

// --- Core Tenant & Modules métier ---
Route::middleware(['identify.tenant'])->group(function () {

    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Personnel (Transversal)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('staff', [StaffController::class, 'index']);
        Route::get('staff/{user}', [StaffController::class, 'show']);
    });

    // Module Hôtel
    Route::middleware(['auth:sanctum', 'module.active:hotel'])->prefix('hotel')->group(function () {
        Route::apiResource('room-types', RoomTypeController::class);
        Route::get('room-types/{roomType}/availability', [RoomController::class, 'availability']);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('guests', GuestController::class);

        Route::apiResource('reservations', ReservationController::class)->only(['index', 'store', 'show']);
        Route::post('reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
        Route::post('reservations/{reservation}/payments', [ReservationController::class, 'addPayment']);
        Route::get('reservations/{reservation}/ledger', [ReservationController::class, 'ledger']);

        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);

        Route::get('housekeeping-tasks', [HousekeepingTaskController::class, 'index']);
        Route::post('housekeeping-tasks', [HousekeepingTaskController::class, 'store']);
        Route::post('housekeeping-tasks/{housekeepingTask}/assign', [HousekeepingTaskController::class, 'assign']);
        Route::post('housekeeping-tasks/{housekeepingTask}/start', [HousekeepingTaskController::class, 'start']);
        Route::post('housekeeping-tasks/{housekeepingTask}/complete', [HousekeepingTaskController::class, 'complete']);
    });

    // Module POS (Autonome)
    Route::middleware(['auth:sanctum', 'module.active:pos'])->prefix('pos')->group(function () {
        Route::apiResource('categories', PosCategoryController::class)->except(['create', 'edit']);
        Route::apiResource('products', PosProductController::class)->except(['create', 'edit']);
        Route::apiResource('tables', PosTableController::class)->except(['create', 'edit']);

        Route::get('orders', [PosOrderController::class, 'index']);
        Route::post('orders', [PosOrderController::class, 'store']);
        Route::get('orders/{posOrder}', [PosOrderController::class, 'show']);
        Route::post('orders/{posOrder}/items', [PosOrderController::class, 'addItem']);
        Route::delete('orders/{posOrder}/items/{item}', [PosOrderController::class, 'removeItem']);
        Route::post('orders/{posOrder}/send-to-kitchen', [PosOrderController::class, 'sendToKitchen']);
        Route::post('orders/{posOrder}/serve', [PosOrderController::class, 'markServed']);
        Route::post('orders/{posOrder}/close', [PosOrderController::class, 'close']);
    });

    // Module Inventaire (Autonome, payant séparément — peut être utilisé par POS mais n'est pas inclus avec lui)
    Route::middleware(['auth:sanctum', 'module.active:inventory'])->prefix('inventory')->group(function () {
        Route::apiResource('items', InventoryItemController::class)->except(['create', 'edit']);
        Route::get('movements', [InventoryMovementController::class, 'index']);
        Route::post('movements', [InventoryMovementController::class, 'store']);
    });

    // Module RH
    Route::middleware(['auth:sanctum', 'module.active:hr'])->prefix('hr')->group(function () {
        Route::apiResource('employees', EmployeeController::class);

        Route::get('employees/{employee}/contracts', [EmployeeContractController::class, 'index']);
        Route::post('employees/{employee}/contracts', [EmployeeContractController::class, 'store']);
        Route::post('employees/{employee}/contracts/{contract}/terminate', [EmployeeContractController::class, 'terminate']);

        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::post('employees/{employee}/attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('employees/{employee}/attendance/clock-out', [AttendanceController::class, 'clockOut']);

        Route::get('leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('employees/{employee}/leave-requests', [LeaveRequestController::class, 'store']);
        Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
        Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);
    });

    // Géolocalisation & Référentiels
    Route::prefix('geo')->group(function () {
        Route::get('continents', [GeoController::class, 'continents']);
        Route::get('countries', [GeoController::class, 'countries']);
        Route::get('regions', [GeoController::class, 'regions']);
        Route::get('departments', [GeoController::class, 'departments']);
        Route::get('cities', [GeoController::class, 'cities']);
        Route::get('neighborhoods', [GeoController::class, 'neighborhoods']);
        Route::get('streets', [GeoController::class, 'streets']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('nearby/hotels', [GeoController::class, 'nearbyHotels']);
            Route::get('nearby/commerces', [GeoController::class, 'nearbyCommerces']);
            Route::get('nearby/all', [GeoController::class, 'nearbyAll']);
            Route::get('distance', [GeoController::class, 'distance']);
        });
    });

});
