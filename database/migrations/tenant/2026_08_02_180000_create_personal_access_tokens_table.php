<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sanctum publie normalement cette table côté central, mais nos
     * `users` vivent dans la base de chaque tenant — la relation
     * polymorphe tokenable ne peut pas traverser deux bases différentes.
     * Cette table doit donc exister dans CHAQUE base tenant.
     *
     * uuidMorphs() (et non morphs()) car users.id est un UUID, pas un
     * entier auto-incrémenté.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
