<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\AddReservationPaymentRequest;
use App\Modules\Hotel\Http\Requests\StoreReservationRequest;
use App\Modules\Hotel\Http\Resources\GuestLedgerResource;
use App\Modules\Hotel\Http\Resources\PaymentResource;
use App\Modules\Hotel\Http\Resources\ReservationResource;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Reservation;
use App\Modules\Hotel\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    /**
 * GET /reservations
 */
public function index(Request $request): JsonResponse
{
    $reservations = Reservation::query()
        ->with(['guest', 'reservationRooms', 'invoices', 'ledgers'])
        ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
        ->when($request->filled('guest_id'), fn ($query) => $query->where('guest_id', $request->input('guest_id')))
        ->orderByDesc('created_at')
        ->paginate($request->integer('per_page', 15));

    return ReservationResource::collection($reservations)->response();
}

    /**
     * GET /reservations/{reservation}
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load(['guest', 'reservationRooms', 'invoices', 'ledgers']);

        return response()->json(new ReservationResource($reservation));
    }

    /**
     * POST /reservations
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $guest = Guest::findOrFail($data['guest_id']);

        $reservation = $this->reservationService->createReservation(
            guest: $guest,
            checkIn: Carbon::parse($data['check_in_date']),
            checkOut: Carbon::parse($data['check_out_date']),
            roomBookings: $data['rooms'],
            source: $data['source'] ?? 'direct',
            createdBy: $request->user()?->id,
        );

        return response()->json(new ReservationResource($reservation), 201);
    }

    /**
     * POST /reservations/{reservation}/check-in
     */
    public function checkIn(Reservation $reservation): JsonResponse
    {
        $reservation = $this->reservationService->checkIn($reservation);

        return response()->json(new ReservationResource($reservation->fresh(['guest', 'reservationRooms'])));
    }

    /**
     * POST /reservations/{reservation}/check-out
     */
    public function checkOut(Request $request, Reservation $reservation): JsonResponse
    {
        $request->validate([
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'mobile_money', 'bank_transfer'])],
        ]);

        $reservation = $this->reservationService->checkOut(
            $reservation,
            $request->input('payment_method')
        );

        return response()->json(new ReservationResource($reservation->fresh(['guest', 'reservationRooms', 'ledgers'])));
    }

    /**
     * POST /reservations/{reservation}/cancel
     */
    public function cancel(Reservation $reservation): JsonResponse
    {
        $reservation = $this->reservationService->cancel($reservation);

        return response()->json(new ReservationResource($reservation->fresh(['guest', 'reservationRooms'])));
    }

    /**
     * POST /reservations/{reservation}/payments
     */
    public function addPayment(AddReservationPaymentRequest $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validated();

        $payment = $this->reservationService->addPayment(
            $reservation,
            (float) $data['amount'],
            $data['payment_method'],
            $data['reference'] ?? null,
        );

        return response()->json([
            'payment'     => new PaymentResource($payment),
            'reservation' => new ReservationResource($reservation->fresh(['ledgers'])),
        ], 201);
    }

    /**
 * GET /reservations/{reservation}/ledger
 */
public function ledger(Reservation $reservation): JsonResponse
{
    $ledgers = $reservation->ledgers()->orderBy('created_at')->get();

    return GuestLedgerResource::collection($ledgers)->response();
}
}
