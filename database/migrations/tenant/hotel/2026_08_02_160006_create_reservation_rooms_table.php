<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignUuid('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->decimal('rate_per_night', 10, 2); // tarif figé au moment de la réservation
            $table->unsignedInteger('nights');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
