<?php

use App\Models\Landlord\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Module::firstOrCreate(
            ['name' => 'hotel'],
            [
                'label' => 'Hôtellerie',
                'description' => 'Gestion des chambres, réservations, clients et facturation hôtelière.',
                'icon' => 'bed',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Module::where('name', 'hotel')->delete();
    }
};
