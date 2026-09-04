<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->string('entity_type', 50); // 'plan', 'service'
            $table->string('entity_id', 36);
            $table->string('plan_name');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XOF');
            $table->string('interval', 20); // 'monthly', 'yearly'
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('active'); // active, paused, cancelled, expired
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_subscriptions');
    }
};
