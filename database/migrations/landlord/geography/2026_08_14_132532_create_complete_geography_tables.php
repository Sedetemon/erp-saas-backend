<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Continents
        Schema::create('continents', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Pays
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('continent_id')->constrained('continents')->cascadeOnDelete();
            $table->char('code', 2)->unique();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('phone_code', 10)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('continent_id');
        });

        // 3. Régions
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['country_id', 'slug']);
            $table->index('country_id');
        });

        // 4. Départements
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['region_id', 'slug']);
            $table->index('region_id');
        });

        // 5. Villes
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'slug']);
            $table->index('department_id');
        });

        // 6. Quartiers
        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['city_id', 'slug']);
            $table->index('city_id');
        });

        // 7. Rues
        Schema::create('streets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neighborhood_id')->constrained('neighborhoods')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->enum('type', ['avenue', 'boulevard', 'rue', 'impasse', 'chemin', 'lieu-dit'])->default('rue');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['neighborhood_id', 'slug']);
            $table->index('neighborhood_id');
        });

        // 8. Adresses
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->nullable()->index();
            $table->string('entity_type', 50);
            $table->string('entity_id', 36);
            $table->foreignId('street_id')->nullable()->constrained('streets')->nullOnDelete();
            $table->string('street_number', 20)->nullable();
            $table->string('building', 50)->nullable();
            $table->string('floor', 20)->nullable();
            $table->string('apartment', 20)->nullable();
            $table->text('additional_info')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_delivery')->default(false);
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('streets');
        Schema::dropIfExists('neighborhoods');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('continents');
    }
};
