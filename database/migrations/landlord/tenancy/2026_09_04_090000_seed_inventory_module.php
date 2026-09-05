<?php

use App\Models\Landlord\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Module::firstOrCreate(
            ['name' => 'inventory'],
            [
                'label' => 'Inventaire',
                'description' => 'Suivi des stocks et mouvements (entrées, sorties, ajustements).',
                'icon' => 'archive',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Module::where('name', 'inventory')->delete();
    }
};
