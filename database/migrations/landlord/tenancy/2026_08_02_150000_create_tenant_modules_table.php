<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('module_id')
    ->constrained('modules')
    ->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
