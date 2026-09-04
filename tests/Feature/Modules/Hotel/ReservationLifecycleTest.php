<?php

namespace Tests\Feature\Modules\Hotel;

use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Payment;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use App\Modules\Hotel\Services\ReservationService;
use Tests\TenantTestCase;

class ReservationLifecycleTest extends TenantTestCase
{
    protected Guest $guest;
    protected RoomType $roomType;
    protected Room $room;
    protected ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guest = Guest::factory()->create();
        $this->roomType = RoomType::factory()->create();
        $this->room = Room::factory()->create([
            'room_type_id' => $this->roomType->id,
            'status'       => 'available',
        ]);

        $this->service = app(ReservationService::class);
    }

    protected function makeReservation(float $ratePerNight = 25000): \App\Modules\Hotel\Models\Reservation
    {
        return $this->service->createReservation(
            guest: $this->guest,
            checkIn: new \DateTime('2026-09-10'),
            checkOut: new \DateTime('2026-09-15'), // 5 nuits
            roomBookings: [
                [
                    'room_type_id'   => $this->roomType->id,
                    'room_id'        => $this->room->id,
                    'rate_per_night' => $ratePerNight,
                ],
            ],
        );
    }

    public function test_check_in_marks_reservation_and_room_correctly(): void
    {
        $reservation = $this->makeReservation();

        $result = $this->service->checkIn($reservation);

        $this->assertSame('checked_in', $result->status);
        $this->assertNotNull($result->actual_check_in);

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'occupied',
        ], 'tenant');
    }

    public function test_check_out_without_payment_leaves_balance_unpaid_and_room_cleaning(): void
    {
        $reservation = $this->makeReservation(25000); // total = 125000

        $this->service->checkIn($reservation);
        $result = $this->service->checkOut($reservation);

        $this->assertSame('checked_out', $result->status);
        $this->assertNotNull($result->actual_check_out);

        // Aucun paiement demandé : le solde doit rester dû.
        $this->assertEquals(125000, $result->fresh()->balance);

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'cleaning',
        ], 'tenant');
    }

    public function test_check_out_with_payment_method_settles_balance_to_zero(): void
    {
        $reservation = $this->makeReservation(25000); // total = 125000

        $this->service->checkIn($reservation);
        $result = $this->service->checkOut($reservation, 'cash');

        $this->assertSame('checked_out', $result->status);

        $this->assertEquals(0, $result->fresh()->balance);

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'amount'         => 125000,
            'method'         => 'cash',
        ], 'tenant');

        $this->assertDatabaseHas('guest_ledgers', [
            'reservation_id' => $reservation->id,
            'type'           => 'payment',
            'amount'         => 125000,
        ], 'tenant');
    }

    public function test_cancel_releases_room_and_marks_reservation_cancelled(): void
    {
        $reservation = $this->makeReservation();

        $this->service->cancel($reservation);

        $this->assertDatabaseHas('reservations', [
            'id'     => $reservation->id,
            'status' => 'cancelled',
        ], 'tenant');

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'available',
        ], 'tenant');
    }

    public function test_add_payment_partially_reduces_balance(): void
    {
        $reservation = $this->makeReservation(25000); // total = 125000

        $payment = $this->service->addPayment($reservation, 50000, 'mobile_money', 'REF-001');

        $this->assertInstanceOf(Payment::class, $payment);

        $this->assertEquals(75000, $reservation->fresh()->balance);

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'amount'         => 50000,
            'method'         => 'mobile_money',
            'reference'      => 'REF-001',
        ], 'tenant');
    }

    public function test_add_payment_does_not_duplicate_ledger_entries(): void
    {
        $reservation = $this->makeReservation(25000); // total = 125000

        $this->service->addPayment($reservation, 125000, 'card');

        $paymentLedgerCount = \DB::connection('tenant')
            ->table('guest_ledgers')
            ->where('reservation_id', $reservation->id)
            ->where('type', 'payment')
            ->count();

        // Une seule ligne 'payment' au ledger, créée par Payment::booted(),
        // pas de doublon manuel côté ReservationService::addPayment().
        $this->assertSame(1, $paymentLedgerCount);

        $this->assertEquals(0, $reservation->fresh()->balance);
    }
}
