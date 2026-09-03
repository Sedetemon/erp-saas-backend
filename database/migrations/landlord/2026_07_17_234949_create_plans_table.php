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
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // si vous préférez les UUID
            $table->string('name');          // ex: "Starter"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->nullable(); // ou price en cents
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->integer('max_users')->nullable();       // nombre max d'utilisateurs
            $table->integer('max_storage')->nullable();     // en Mo/Go
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
