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
        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary(); // si vous préférez les UUID
            $table->string('name')->unique();         // ex: "invoicing"
            $table->string('label');         // ex: "Facturation"
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // icône pour l'interface
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
