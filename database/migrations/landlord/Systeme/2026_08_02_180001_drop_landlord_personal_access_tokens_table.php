<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette table a été créée par erreur côté landlord à l'origine du
     * projet. Les `users` vivent côté tenant, donc les tokens Sanctum
     * doivent y être aussi (voir database/migrations/tenant/..._create_personal_access_tokens_table.php).
     * Cette table centrale n'est jamais utilisée : on la supprime.
     */
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        // Intentionnellement vide : on ne veut pas la recréer côté landlord.
    }
};
