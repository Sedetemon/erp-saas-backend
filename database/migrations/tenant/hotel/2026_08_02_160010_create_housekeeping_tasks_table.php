<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', [
                'checkout_cleaning', // nettoyage après départ client
                'daily_cleaning',    // recouche pendant un séjour
                'inspection',        // contrôle qualité
                'maintenance_report', // signalement d'un problème technique
            ])->default('checkout_cleaning');
            $table->enum('status', [
                'pending', 'in_progress', 'completed', 'verified',
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_tasks');
    }
};
