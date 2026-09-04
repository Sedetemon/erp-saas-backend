<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['cdi', 'cdd', 'interim', 'stage', 'freelance'])->default('cdi');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = durée indéterminée (CDI)
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['active', 'ended', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
