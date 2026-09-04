<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('pos_product_id')->index(); // Lié au produit POS
                $table->decimal('quantity', 12, 2)->default(0); // Quantité actuelle en stock
                $table->decimal('alert_threshold', 12, 2)->default(5); // Seuil d'alerte stock bas
                $table->timestamps();

                // Clé étrangère vers la table des produits POS
                $table->foreign('pos_product_id')
                    ->references('id')
                    ->on('pos_products')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
