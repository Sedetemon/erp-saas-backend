<?php

use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\GeoController;
use App\Models\Landlord\Continent;
use App\Models\Landlord\Country;
use App\Models\Landlord\Region;
use App\Models\Landlord\Department;
use App\Models\Landlord\City;
use App\Models\Landlord\Neighborhood;
use App\Models\Landlord\Street;
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
use App\Modules\Pos\Http\Controllers\PosCategoryController;
use App\Modules\Pos\Http\Controllers\PosOrderController;
use App\Modules\Pos\Http\Controllers\PosProductController;
use App\Modules\Pos\Http\Controllers\PosTableController;
use Illuminate\Support\Facades\Route;
use App\Modules\Messaging\Http\Controllers\ConversationController;
use App\Modules\Messaging\Http\Controllers\MessageController;
/*
|--------------------------------------------------------------------------
| Routes tenant
|--------------------------------------------------------------------------
|
| Toutes les routes ici passent par IdentifyTenant (header X-Tenant) et
| tournent donc dans le contexte d'un tenant précis. Les routes centrales
| (routes/api.php) n'y passent pas.
|
| IMPORTANT : 'identify.tenant' doit toujours être le PREMIER middleware
| de chaque groupe qui utilise aussi 'auth:sanctum' (voir
| TenancyServiceProvider::makeTenancyMiddlewareHighestPriority()).
|
*/

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
    // Fichiers joints
    Route::post('messages/{message}/attachments', [MessageController::class, 'uploadAttachment']);
    Route::delete('attachments/{attachment}', [MessageController::class, 'deleteAttachment']);

    // Recherche de messages
    Route::get('messages/search', [MessageController::class, 'search']);

// Notifications
Route::get('notifications', function (Illuminate\Http\Request $request) {
    return $request->user()->notifications;
});

Route::post('notifications/{id}/mark-as-read', function (string $id, Illuminate\Http\Request $request) {
    $notification = $request->user()->notifications()->where('id', $id)->first();
    if ($notification) {
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marquée comme lue']);
    }
    return response()->json(['error' => 'Notification non trouvée'], 404);
});

Route::post('notifications/mark-all-read', function (Illuminate\Http\Request $request) {
    $request->user()->unreadNotifications->markAsRead();
    return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
});

});

Route::middleware(['identify.tenant'])->group(function () {

    // --- Authentification (login public, le reste protégé) ---
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // --- Personnel (transversal, pas propre au module Hôtel) ---
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('staff', [StaffController::class, 'index']);
        Route::get('staff/{user}', [StaffController::class, 'show']);
    });

    // --- Module Hôtel (protégé : connecté + module actif) ---
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

    // --- Module POS (transversal, indépendant du module hôtel) ---
    Route::middleware(['auth:sanctum', 'module.active:pos'])->prefix('pos')->group(function () {

        Route::get('categories', [PosCategoryController::class, 'index']);
        Route::post('categories', [PosCategoryController::class, 'store']);
        Route::put('categories/{posCategory}', [PosCategoryController::class, 'update']);
        Route::delete('categories/{posCategory}', [PosCategoryController::class, 'destroy']);

        Route::get('products', [PosProductController::class, 'index']);
        Route::post('products', [PosProductController::class, 'store']);
        Route::put('products/{posProduct}', [PosProductController::class, 'update']);
        Route::delete('products/{posProduct}', [PosProductController::class, 'destroy']);

        Route::get('tables', [PosTableController::class, 'index']);
        Route::post('tables', [PosTableController::class, 'store']);
        Route::put('tables/{posTable}', [PosTableController::class, 'update']);
        Route::delete('tables/{posTable}', [PosTableController::class, 'destroy']);

        Route::get('orders', [PosOrderController::class, 'index']);
        Route::post('orders', [PosOrderController::class, 'store']);
        Route::get('orders/{posOrder}', [PosOrderController::class, 'show']);
        Route::post('orders/{posOrder}/items', [PosOrderController::class, 'addItem']);
        Route::delete('orders/{posOrder}/items/{item}', [PosOrderController::class, 'removeItem']);
        Route::post('orders/{posOrder}/send-to-kitchen', [PosOrderController::class, 'sendToKitchen']);
        Route::post('orders/{posOrder}/serve', [PosOrderController::class, 'markServed']);
        Route::post('orders/{posOrder}/close', [PosOrderController::class, 'close']);

    });

    // --- Module RH (protégé : connecté + module actif) ---
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

    // ============================================================
    // 2.3 LOCALISATION & GÉOLOCALISATION
    // ============================================================

    // --- 2.3.1 Référentiels géographiques (publics, sans auth) ---
    Route::prefix('geo')->group(function () {

        // Continents
        Route::get('continents', function () {
            return response()->json(['data' => Continent::active()->get()]);
        });

        // Pays (filtré par continent)
        Route::get('countries', function (\Illuminate\Http\Request $request) {
            $query = Country::active();
            if ($request->filled('continent_id')) {
                $query->where('continent_id', $request->continent_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // Régions (filtré par pays)
        Route::get('regions', function (\Illuminate\Http\Request $request) {
            $query = Region::active();
            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // Départements (filtré par région)
        Route::get('departments', function (\Illuminate\Http\Request $request) {
            $query = Department::active();
            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // Villes (filtré par département)
        Route::get('cities', function (\Illuminate\Http\Request $request) {
            $query = City::active();
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // Quartiers (filtré par ville)
        Route::get('neighborhoods', function (\Illuminate\Http\Request $request) {
            $query = Neighborhood::active();
            if ($request->filled('city_id')) {
                $query->where('city_id', $request->city_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // Rues (filtré par quartier)
        Route::get('streets', function (\Illuminate\Http\Request $request) {
            $query = Street::active();
            if ($request->filled('neighborhood_id')) {
                $query->where('neighborhood_id', $request->neighborhood_id);
            }
            return response()->json(['data' => $query->get()]);
        });

        // --- 2.3.2 Géolocalisation (protégée par auth) ---
        Route::middleware('auth:sanctum')->group(function () {
            // Hôtels à proximité
            Route::get('nearby/hotels', [GeoController::class, 'nearbyHotels']);

            // Commerces à proximité
            Route::get('nearby/commerces', [GeoController::class, 'nearbyCommerces']);

            // Tous les lieux à proximité
            Route::get('nearby/all', [GeoController::class, 'nearbyAll']);

            // Distance entre deux adresses
            Route::get('distance', [GeoController::class, 'distance']);
        });

    }); // fin geo

});
