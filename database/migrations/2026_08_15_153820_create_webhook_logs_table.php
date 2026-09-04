<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_type', 100);
            $table->json('payload');
            $table->string('status', 20)->default('received');
            $table->timestamps();

            $table->index('provider');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('webhook_logs');
    }
};
