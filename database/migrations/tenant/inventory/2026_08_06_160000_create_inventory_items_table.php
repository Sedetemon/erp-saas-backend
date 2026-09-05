<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('sku')->nullable()->unique();
                $table->string('unit')->default('pcs'); // pcs, kg, l, box, ...
                $table->string('category')->nullable();
                $table->boolean('is_active')->default(true);

                // Lien polymorphe optionnel vers l'entité "source" du produit
                // (ex: PosProduct). Nullable : un item peut exister sans être
                // rattaché à aucun autre module (linge, fournitures, etc.).
                $table->string('itemable_type')->nullable();
                $table->uuid('itemable_id')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['itemable_type', 'itemable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
