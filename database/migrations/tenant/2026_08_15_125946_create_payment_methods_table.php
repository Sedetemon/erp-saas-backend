<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36)->index();
            $table->string('user_id', 36)->index();
            $table->string('provider', 50);
            $table->string('provider_token')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->string('brand')->nullable(); // 'visa', 'mastercard', 'orange', 'mtn'
            $table->boolean('is_default')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
