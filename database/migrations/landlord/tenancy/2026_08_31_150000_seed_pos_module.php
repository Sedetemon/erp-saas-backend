<?php

use App\Models\Landlord\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Module::firstOrCreate(
            ['name' => 'pos'],
            [
                'label' => 'Point de Vente',
                'description' => 'Gestion des produits, commandes et ventes.',
                'icon' => 'shopping-cart',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Module::where('name', 'pos')->delete();
    }
};