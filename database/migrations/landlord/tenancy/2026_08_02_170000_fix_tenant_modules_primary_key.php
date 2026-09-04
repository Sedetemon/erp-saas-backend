<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les tables pivot pures (attach()/sync()) insèrent via une requête SQL
     * directe, sans passer par les événements du modèle : le hook qui
     * génère l'UUID (HasUuid::creating()) ne se déclenche donc jamais pour
     * ce genre d'insertion, d'où l'erreur "Field 'id' doesn't have a
     * default value". Une clé primaire auto-increment évite ce problème
     * une fois pour toutes — pas besoin d'UUID ici, cette table n'est
     * jamais exposée directement.
     */
    public function up(): void
    {
        Schema::dropIfExists('tenant_modules');

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
