<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            $table->timestamp('starts_at');

            $table->timestamp('ends_at')->nullable();

            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->enum('status', [
                'trial',
                'active',
                'expired',
                'cancelled',
                'suspended',
            ])->default('trial');

            $table->boolean('auto_renew')->default(true);

            $table->decimal('amount', 12, 2)->nullable();

            $table->string('currency', 3)->default('XOF');

            $table->string('payment_provider')->nullable();

            $table->string('payment_reference')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
