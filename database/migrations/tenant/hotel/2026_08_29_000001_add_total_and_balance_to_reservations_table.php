<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('actual_check_in')->nullable()->after('status');
            $table->timestamp('actual_check_out')->nullable()->after('actual_check_in');
            $table->decimal('total', 12, 2)->default(0)->after('actual_check_out');
            $table->decimal('balance', 12, 2)->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['actual_check_in', 'actual_check_out', 'total', 'balance']);
        });
    }
};
