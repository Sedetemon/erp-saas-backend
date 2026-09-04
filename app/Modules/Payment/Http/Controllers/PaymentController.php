<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\Transaction;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initier un paiement
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|in:reservation,invoice,subscription,order',
            'entity_id' => 'required|string|max:36',
            'provider' => 'required|string|in:orange_money,mtn_money,wave,stripe,card,manual',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'meta' => 'nullable|array',
        ]);

        $validated['tenant_id'] = tenant()->id;

        $transaction = $this->paymentService->initiatePayment($validated);

        return response()->json([
            'data' => $transaction,
        ], 201);
    }

    /**
     * Récupérer les transactions
     */
    public function index(Request $request)
    {
        $transactions = Transaction::where('tenant_id', tenant()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($transactions);
    }

    /**
     * Voir une transaction
     */
    public function show(string $id)
    {
        $transaction = Transaction::where('tenant_id', tenant()->id)
            ->findOrFail($id);

        return response()->json(['data' => $transaction]);
    }
}
