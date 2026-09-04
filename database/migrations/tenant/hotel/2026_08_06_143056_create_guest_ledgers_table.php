<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_ledgers')) {
            Schema::create('guest_ledgers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('reservation_id')->index();
                $table->uuid('guest_id')->index();
                $table->enum('type', ['charge', 'payment', 'refund', 'discount']);
                $table->string('source');
                $table->string('source_id')->nullable();
                $table->string('description');
                $table->decimal('amount', 12, 2);
                $table->timestamps();

                $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
                $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_ledgers');
    }
};
