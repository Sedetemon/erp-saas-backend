<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reservation_number')->unique(); // ex: "RES-2026-00001"
            $table->foreignUuid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->enum('status', [
                'pending',     // en attente de confirmation
                'confirmed',   // confirmée, pas encore arrivée
                'checked_in',  // client arrivé
                'checked_out', // séjour terminé
                'cancelled',
                'no_show',
            ])->default('pending');
            $table->enum('source', [
                'direct', 'phone', 'walk_in', 'ota',
            ])->default('direct');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['check_in_date', 'check_out_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
