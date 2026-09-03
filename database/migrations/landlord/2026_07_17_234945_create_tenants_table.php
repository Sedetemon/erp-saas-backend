<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('email')->unique();
            $table->string('phone')->nullable();

            $table->foreignUuid('plan_id')->nullable();

            $table->enum('status', [
                'pending',
                'trial',
                'active',
                'suspended',
                'expired',
            ])->default('pending');

            $table->timestamp('trial_ends_at')->nullable();

            $table->json('data')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
