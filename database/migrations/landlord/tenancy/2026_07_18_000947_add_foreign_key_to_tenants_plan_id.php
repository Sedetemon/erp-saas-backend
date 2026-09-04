<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tenants.plan_id existe depuis la création de la table `tenants`, mais
     * sans contrainte de clé étrangère car `plans` n'existait pas encore à
     * ce moment de l'historique des migrations. On l'ajoute ici, une fois
     * que `plans` existe.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });
    }
};
