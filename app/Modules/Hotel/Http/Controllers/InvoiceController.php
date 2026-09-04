<?php

namespace App\Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotel\Http\Requests\AddReservationPaymentRequest;
use App\Modules\Hotel\Http\Resources\InvoiceResource;
use App\Modules\Hotel\Http\Resources\PaymentResource;
use App\Modules\Hotel\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with('guest')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('guest_id'), fn ($q) => $q->where('guest_id', $request->string('guest_id')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return InvoiceResource::collection($invoices)->response();
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['guest', 'items', 'payments', 'reservation']);

        return response()->json(new InvoiceResource($invoice));
    }

    public function issue(Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'issued', 'issued_at' => now()]);
        }

        return response()->json(new InvoiceResource($invoice->load('items')));
    }

    /**
     * Paiement direct sur la facture (hors flux réservation, ex: facture
     * indépendante). Pour un acompte/règlement lié à un séjour, préférez
     * POST /reservations/{reservation}/payments qui passe par le folio.
     */
    public function storePayment(AddReservationPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $payment = $invoice->payments()->create([
            'amount' => $request->input('amount'),
            'method' => $request->input('payment_method'),
            'reference' => $request->input('reference'),
            'paid_at' => now(),
        ]);

        return response()->json(new PaymentResource($payment), 201);
    }
}
