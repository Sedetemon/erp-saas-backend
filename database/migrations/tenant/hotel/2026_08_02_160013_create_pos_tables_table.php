<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');   // ex: "Table 5", "Bar 2"
            $table->string('area')->nullable(); // ex: "Restaurant", "Terrasse", "Bar"
            $table->enum('status', ['free', 'occupied', 'reserved'])->default('free');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_tables');
    }
};
