<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type', 100)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('subject', 255)->nullable();
            $table->uuid('created_by')->nullable();
            $table->boolean('is_group')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
