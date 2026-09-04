<?php
// app/Modules/Pos/Database/Migrations/2026_08_31_203038_add_soft_deletes_to_pos_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Utiliser la connexion par défaut (tenant)
        Schema::table('pos_products', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pos_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
