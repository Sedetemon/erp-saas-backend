<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary(); // si vous préférez les UUID

            // 🔑 Pour le multi-tenant : on enregistre quel tenant est concerné
            // (si le log vient d'un tenant, sinon NULL si c'est une action Landlord)
            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            // 🧑‍💻 Qui a fait l'action (polymorphique : User admin, ou User tenant, ou API token)
            $table->nullableMorphs('causer');

            // 📦 Sur quoi l'action a été faite (polymorphique)
            $table->nullableMorphs('auditable');

            // 📝 Description de l'événement
            $table->string('event'); // ex: 'created', 'updated', 'deleted', 'login', 'export'

            // 📋 Le nom du log (pour catégoriser, ex: 'authentification', 'facturation', 'RH')
            $table->string('log_name')->default('default');

            // 📄 Anciennes et nouvelles valeurs (au format JSON)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // 🌐 Contexte technique de la requête
            $table->string('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // 🔗 Identifiant de lot (pour regrouper plusieurs logs d'une même transaction)
            $table->uuid('batch_uuid')->nullable();

            $table->timestamps();

            // Index pour accélérer les recherches
            $table->index(['log_name', 'event']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
