<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\PaymentMethod;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Liste des modes de paiement de l'utilisateur
     */
    public function index(Request $request)
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json(['data' => $methods]);
    }

    /**
     * Ajouter un mode de paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:orange_money,mtn_money,wave,stripe,card',
            'token' => 'nullable|string',
            'last_four' => 'nullable|string|size:4',
            'brand' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        $method = app(PaymentService::class)->storePaymentMethod(
            $request->user()->id,
            $validated['provider'],
            $validated
        );

        return response()->json(['data' => $method], 201);
    }

    /**
     * Supprimer un mode de paiement
     */
    public function destroy(string $id, Request $request)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $method->delete();

        // Si c'était le mode par défaut, définir un autre comme défaut
        if ($method->is_default) {
            $newDefault = PaymentMethod::where('user_id', $request->user()->id)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json(null, 204);
    }

    /**
     * Définir un mode de paiement comme défaut
     */
    public function setDefault(string $id, Request $request)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        PaymentMethod::where('user_id', $request->user()->id)
            ->update(['is_default' => false]);

        $method->update(['is_default' => true]);

        return response()->json(['data' => $method]);
    }
}
