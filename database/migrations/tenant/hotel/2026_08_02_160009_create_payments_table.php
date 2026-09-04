<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignUuid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignUuid('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'card', 'mobile_money', 'bank_transfer'])->default('cash');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
