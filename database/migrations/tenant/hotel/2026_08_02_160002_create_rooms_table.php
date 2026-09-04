<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('number')->unique(); // ex: "201"
            $table->string('floor')->nullable();
            $table->enum('status', [
                'available',   // libre et propre
                'occupied',    // occupée
                'cleaning',    // en nettoyage
                'maintenance', // hors service
            ])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
