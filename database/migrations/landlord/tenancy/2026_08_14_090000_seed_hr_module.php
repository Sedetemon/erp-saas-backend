<?php

use App\Models\Landlord\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Module::firstOrCreate(
            ['name' => 'hr'],
            [
                'label' => 'Ressources Humaines',
                'description' => 'Employés, contrats, pointage et congés.',
                'icon' => 'badge',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Module::where('name', 'hr')->delete();
    }
};
