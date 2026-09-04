<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                // ex: "Chambre Deluxe"
            $table->string('code')->unique();       // ex: "DLX"
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);   // prix de base / nuit
            $table->unsignedInteger('capacity_adults')->default(2);
            $table->unsignedInteger('capacity_children')->default(0);
            $table->json('amenities')->nullable();  // ["wifi", "climatisation", "minibar"...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
