<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('name');                  // ex: "Tarif haute saison"
            $table->decimal('price_per_night', 10, 2);
            $table->date('starts_on')->nullable();    // null = tarif permanent
            $table->date('ends_on')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
