<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->string('entity_type', 50); // 'reservation', 'invoice', 'subscription', 'order'
            $table->string('entity_id', 36);
            $table->string('provider', 50); // 'orange_money', 'mtn_money', 'wave', 'stripe', 'manual'
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 20)->default('pending'); // pending, succeeded, failed, cancelled, refunded
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['provider', 'provider_reference']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
