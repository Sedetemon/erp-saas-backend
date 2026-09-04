<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimpleGeographySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌍 Création des données géographiques...');

        // 1. Continents
        $africaId = DB::table('continents')->insertGetId([
            'code' => 'AF',
            'name' => 'Afrique',
            'slug' => 'afrique',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Pays
        $ciId = DB::table('countries')->insertGetId([
            'continent_id' => $africaId,
            'code' => 'CI',
            'name' => 'Côte d\'Ivoire',
            'slug' => 'cote-d-ivoire',
            'phone_code' => '+225',
            'currency_code' => 'XOF',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Régions
        $abidjanRegionId = DB::table('regions')->insertGetId([
            'country_id' => $ciId,
            'code' => 'ABJ',
            'name' => 'District Autonome d\'Abidjan',
            'slug' => 'district-autonome-abidjan',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guemonRegionId = DB::table('regions')->insertGetId([
            'country_id' => $ciId,
            'code' => 'GUE',
            'name' => 'Région du Guémon',
            'slug' => 'region-guemon',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $worodougouRegionId = DB::table('regions')->insertGetId([
            'country_id' => $ciId,
            'code' => 'WOR',
            'name' => 'Région du Worodougou',
            'slug' => 'region-worodougou',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Départements
        $abidjanDeptId = DB::table('departments')->insertGetId([
            'region_id' => $abidjanRegionId,
            'code' => 'ABJ',
            'name' => 'Abidjan',
            'slug' => 'abidjan',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guigloDeptId = DB::table('departments')->insertGetId([
            'region_id' => $guemonRegionId,
            'code' => 'GGL',
            'name' => 'Guiglo',
            'slug' => 'guiglo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $daloaDeptId = DB::table('departments')->insertGetId([
            'region_id' => $guemonRegionId,
            'code' => 'DAL',
            'name' => 'Daloa',
            'slug' => 'daloa',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seguelaDeptId = DB::table('departments')->insertGetId([
            'region_id' => $worodougouRegionId,
            'code' => 'SEG',
            'name' => 'Séguéla',
            'slug' => 'seguela',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Villes
        $abidjanCityId = DB::table('cities')->insertGetId([
            'department_id' => $abidjanDeptId,
            'name' => 'Abidjan',
            'slug' => 'abidjan',
            'postal_code' => '01 BP 1234',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guigloCityId = DB::table('cities')->insertGetId([
            'department_id' => $guigloDeptId,
            'name' => 'Guiglo',
            'slug' => 'guiglo',
            'postal_code' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $daloaCityId = DB::table('cities')->insertGetId([
            'department_id' => $daloaDeptId,
            'name' => 'Daloa',
            'slug' => 'daloa',
            'postal_code' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seguelaCityId = DB::table('cities')->insertGetId([
            'department_id' => $seguelaDeptId,
            'name' => 'Séguéla',
            'slug' => 'seguela',
            'postal_code' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Quartiers
        $cocodyId = DB::table('neighborhoods')->insertGetId([
            'city_id' => $abidjanCityId,
            'name' => 'Cocody',
            'slug' => 'cocody',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $plateauId = DB::table('neighborhoods')->insertGetId([
            'city_id' => $abidjanCityId,
            'name' => 'Plateau',
            'slug' => 'plateau',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $guigloCentreId = DB::table('neighborhoods')->insertGetId([
            'city_id' => $guigloCityId,
            'name' => 'Guiglo Centre',
            'slug' => 'guiglo-centre',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $daloaCentreId = DB::table('neighborhoods')->insertGetId([
            'city_id' => $daloaCityId,
            'name' => 'Daloa Centre',
            'slug' => 'daloa-centre',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Rues
        DB::table('streets')->insert([
            [
                'neighborhood_id' => $cocodyId,
                'name' => 'Rue des Jardins',
                'slug' => 'rue-des-jardins',
                'type' => 'rue',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'neighborhood_id' => $cocodyId,
                'name' => 'Avenue des Lagunes',
                'slug' => 'avenue-des-lagunes',
                'type' => 'avenue',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'neighborhood_id' => $plateauId,
                'name' => 'Avenue Treichville',
                'slug' => 'avenue-treichville',
                'type' => 'avenue',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'neighborhood_id' => $guigloCentreId,
                'name' => 'Avenue de l\'Indépendance',
                'slug' => 'avenue-independance',
                'type' => 'avenue',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'neighborhood_id' => $daloaCentreId,
                'name' => 'Avenue du Commerce',
                'slug' => 'avenue-du-commerce',
                'type' => 'avenue',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ Données géographiques créées avec succès !');
        $this->command->info('');
        $this->command->info('📊 Récapitulatif :');
        $this->command->info('  - Continents : 1');
        $this->command->info('  - Pays : 1');
        $this->command->info('  - Régions : 3');
        $this->command->info('  - Départements : 4');
        $this->command->info('  - Villes : 4');
        $this->command->info('  - Quartiers : 4');
        $this->command->info('  - Rues : 5');
    }
}
