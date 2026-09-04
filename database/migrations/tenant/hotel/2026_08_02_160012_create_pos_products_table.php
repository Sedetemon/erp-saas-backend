<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pos_category_id')->constrained('pos_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_products');
    }
};
