<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Modules\Payment\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Endpoint webhook pour les fournisseurs de paiement.
     *
     * Le tenant concerné est encodé dans l'URL (slug) : les fournisseurs
     * externes (Orange Money, Stripe, etc.) n'envoient jamais le header
     * X-Tenant utilisé par le reste de l'API. Sans cette identification,
     * les requêtes vers Transaction (connexion 'tenant') retomberaient
     * sur une connexion non initialisée — risque de fuite entre tenants
     * sur un worker persistant.
     */
    public function handle(Request $request, string $provider, string $tenant)
    {
        $payload = $request->all();

        // webhook_logs vit côté landlord, indépendant du tenant : on
        // journalise même si le tenant s'avère introuvable ensuite.
        $this->webhookService->log($provider, array_merge($payload, [
            'tenant_slug' => $tenant,
        ]));

        $tenantModel = Tenant::on('landlord')->where('slug', $tenant)->first();

        if (! $tenantModel) {
            // 200 volontaire : évite les tentatives de ré-envoi agressives
            // du fournisseur externe pour une référence définitivement
            // non résolvable ; l'événement reste tracé dans webhook_logs.
            return response()->json(['status' => 'ignored', 'message' => 'Tenant inconnu'], 200);
        }

        $alreadyOnThisTenant = tenancy()->initialized
            && tenancy()->tenant?->getTenantKey() === $tenantModel->getTenantKey();

        if (! $alreadyOnThisTenant) {
            tenancy()->initialize($tenantModel);
        }

        try {
            $this->webhookService->process($provider, $payload);
        } finally {
            // On ne ferme que le contexte qu'on a nous-mêmes ouvert : si
            // l'appelant était déjà dans le contexte de ce tenant (cas des
            // tests, ou d'un futur appel interne déjà tenant-scopé), on ne
            // casse pas un contexte qui ne nous appartient pas.
            if (! $alreadyOnThisTenant) {
                tenancy()->end();
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
