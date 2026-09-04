<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('pos_product_id')->index();
                $table->enum('type', ['in', 'out', 'adjustment']); // 'in' = Entrée/Achat, 'out' = Sortie/Vente, 'adjustment' = Correction
                $table->decimal('quantity', 12, 2);
                $table->string('reference_type')->nullable(); // Ex: 'pos_order'
                $table->uuid('reference_id')->nullable();     // Ex: L'ID de la commande POS
                $table->string('reason')->nullable();         // Ex: "Vente POS / Commande #CMD-..."
                $table->uuid('created_by')->nullable();       // Utilisateur qui a déclenché le mouvement
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
        Schema::dropIfExists('inventory_movements');
    }
};
