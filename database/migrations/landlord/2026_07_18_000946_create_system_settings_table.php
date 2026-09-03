<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            // La clé unique qui identifie le paramètre (ex: 'app_name', 'mail_driver', 'vat_rate')
            $table->string('key')->unique();

            // La valeur en elle-même (on utilise 'text' car elle peut être longue,
            // JSON, ou contenir du HTML/texte)
            $table->text('value')->nullable();

            // Permet de regrouper les paramètres pour les afficher facilement dans l'UI
            // ex: 'general', 'mail', 'payment', 'security', 'appearance'
            $table->string('group')->default('general')->index();

            // Description explicative pour l'administrateur (visible dans l'interface)
            $table->string('label')->nullable();
            $table->text('description')->nullable();

            // Le type de données pour un casting facile dans le modèle
            // ex: 'string', 'boolean', 'integer', 'json', 'array'
            $table->string('type')->default('string');

            // Si le paramètre peut être modifié par l'utilisateur ou est figé en dur
            $table->boolean('is_editable')->default(true);

            // Si le paramètre est actif (pour désactiver un paramètre sans le supprimer)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
