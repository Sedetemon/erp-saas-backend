<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Lien optionnel vers un compte de connexion (users) : un employé
            // n'a pas forcément accès à l'application.
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();   // poste
            $table->string('department')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->date('date_of_birth')->nullable();
            $table->string('national_id')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
