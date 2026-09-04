<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique(); // ex: "CMD-2026-000001"
            $table->foreignUuid('pos_table_id')->nullable()->constrained('pos_tables')->nullOnDelete();
            $table->foreignUuid('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignUuid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->enum('type', ['dine_in', 'room_service', 'takeaway'])->default('dine_in');
            $table->enum('status', ['open', 'sent_to_kitchen', 'served', 'closed', 'cancelled'])->default('open');
            $table->enum('payment_method', ['cash', 'card', 'mobile_money', 'room_charge'])->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
