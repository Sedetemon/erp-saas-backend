<?php

namespace Database\Seeders;

use App\Models\Landlord\Continent;
use App\Models\Landlord\Country;
use App\Models\Landlord\Region;
use App\Models\Landlord\Department;
use App\Models\Landlord\City;
use App\Models\Tenant\Address;
use App\Models\Landlord\Neighborhood;
use App\Models\Landlord\Street;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GeographyDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nettoyer les données existantes (optionnel)
        // Address::truncate();
        // Street::truncate();
        // Neighborhood::truncate();
        // City::truncate();
        // Department::truncate();
        // Region::truncate();
        // Country::truncate();
        // Continent::truncate();

        $this->command->info('🌍 Création des données géographiques de démonstration...');

        // ============================================
        // 1. CONTINENTS
        // ============================================
        $africa = Continent::create([
            'code' => 'AF',
            'name' => 'Afrique',
            'slug' => 'afrique',
            'is_active' => true,
        ]);

        $europe = Continent::create([
            'code' => 'EU',
            'name' => 'Europe',
            'slug' => 'europe',
            'is_active' => true,
        ]);

        $this->command->info('✅ Continents créés.');

        // ============================================
        // 2. PAYS (Côte d'Ivoire + quelques autres)
        // ============================================
        $ci = Country::create([
            'continent_id' => $africa->id,
            'code' => 'CI',
            'name' => 'Côte d\'Ivoire',
            'slug' => 'cote-d-ivoire',
            'phone_code' => '+225',
            'currency_code' => 'XOF',
            'is_active' => true,
        ]);

        Country::create([
            'continent_id' => $africa->id,
            'code' => 'SN',
            'name' => 'Sénégal',
            'slug' => 'senegal',
            'phone_code' => '+221',
            'currency_code' => 'XOF',
            'is_active' => true,
        ]);

        Country::create([
            'continent_id' => $africa->id,
            'code' => 'CM',
            'name' => 'Cameroun',
            'slug' => 'cameroun',
            'phone_code' => '+237',
            'currency_code' => 'XAF',
            'is_active' => true,
        ]);

        Country::create([
            'continent_id' => $europe->id,
            'code' => 'FR',
            'name' => 'France',
            'slug' => 'france',
            'phone_code' => '+33',
            'currency_code' => 'EUR',
            'is_active' => true,
        ]);

        $this->command->info('✅ Pays créés.');

        // ============================================
        // 3. RÉGIONS DE CÔTE D'IVOIRE
        // ============================================
        $regionsData = [
            ['name' => 'District Autonome d\'Abidjan', 'slug' => 'district-autonome-abidjan'],
            ['name' => 'District Autonome de Yamoussoukro', 'slug' => 'district-autonome-yamoussoukro'],
            ['name' => 'Région de la Bagoué', 'slug' => 'region-bagoue'],
            ['name' => 'Région de la Béré', 'slug' => 'region-bere'],
            ['name' => 'Région de la Comoé', 'slug' => 'region-comoe'],
            ['name' => 'Région du Gbôklé', 'slug' => 'region-gbokle'],
            ['name' => 'Région du Guémon', 'slug' => 'region-guemon'],
            ['name' => 'Région du Haut-Sassandra', 'slug' => 'region-haut-sassandra'],
            ['name' => 'Région du Lôh-Djiboua', 'slug' => 'region-loh-djiboua'],
            ['name' => 'Région de la Marahoué', 'slug' => 'region-marahoue'],
            ['name' => 'Région du Poro', 'slug' => 'region-poro'],
            ['name' => 'Région du Tchologo', 'slug' => 'region-tchologo'],
            ['name' => 'Région du Worodougou', 'slug' => 'region-worodougou'],
        ];

        $regions = [];
        foreach ($regionsData as $data) {
            $regions[] = Region::create([
                'country_id' => $ci->id,
                'code' => Str::slug($data['name']),
                'name' => $data['name'],
                'slug' => $data['slug'],
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Régions créées.');

        // ============================================
        // 4. DÉPARTEMENTS (principaux)
        // ============================================
        $abidjanRegion = Region::where('slug', 'district-autonome-abidjan')->first();
        $guemonRegion = Region::where('slug', 'region-guemon')->first();
        $hautSassandraRegion = Region::where('slug', 'region-haut-sassandra')->first();
        $poroRegion = Region::where('slug', 'region-poro')->first();
        $worodougouRegion = Region::where('slug', 'region-worodougou')->first();

        $departmentsData = [
            // District Autonome d'Abidjan
            ['region_id' => $abidjanRegion->id, 'name' => 'Abidjan', 'slug' => 'abidjan'],

            // Guémon
            ['region_id' => $guemonRegion->id, 'name' => 'Guiglo', 'slug' => 'guiglo'],
            ['region_id' => $guemonRegion->id, 'name' => 'Duékoué', 'slug' => 'duekoue'],
            ['region_id' => $guemonRegion->id, 'name' => 'Bangolo', 'slug' => 'bangolo'],

            // Haut-Sassandra
            ['region_id' => $hautSassandraRegion->id, 'name' => 'Daloa', 'slug' => 'daloa'],
            ['region_id' => $hautSassandraRegion->id, 'name' => 'Issia', 'slug' => 'issia'],
            ['region_id' => $hautSassandraRegion->id, 'name' => 'Vavoua', 'slug' => 'vavoua'],

            // Poro
            ['region_id' => $poroRegion->id, 'name' => 'Korhogo', 'slug' => 'korhogo'],
            ['region_id' => $poroRegion->id, 'name' => 'Ferkessédougou', 'slug' => 'ferkessedougou'],

            // Worodougou
            ['region_id' => $worodougouRegion->id, 'name' => 'Séguéla', 'slug' => 'seguela'],
            ['region_id' => $worodougouRegion->id, 'name' => 'Mankono', 'slug' => 'mankono'],
        ];

        $departments = [];
        foreach ($departmentsData as $data) {
            $departments[] = Department::create($data);
        }

        $this->command->info('✅ Départements créés.');

        // ============================================
        // 5. VILLES / COMMUNES
        // ============================================
        $abidjanDept = Department::where('slug', 'abidjan')->first();
        $guigloDept = Department::where('slug', 'guiglo')->first();
        $daloaDept = Department::where('slug', 'daloa')->first();
        $korhogoDept = Department::where('slug', 'korhogo')->first();
        $seguelaDept = Department::where('slug', 'seguela')->first();

        $citiesData = [
            // Abidjan
            ['department_id' => $abidjanDept->id, 'name' => 'Abidjan', 'slug' => 'abidjan', 'postal_code' => '01 BP 1234'],
            ['department_id' => $abidjanDept->id, 'name' => 'Yopougon', 'slug' => 'yopougon', 'postal_code' => '15 BP 5678'],
            ['department_id' => $abidjanDept->id, 'name' => 'Treichville', 'slug' => 'treichville', 'postal_code' => '04 BP 9012'],

            // Guiglo
            ['department_id' => $guigloDept->id, 'name' => 'Guiglo', 'slug' => 'guiglo', 'postal_code' => null],
            ['department_id' => $guigloDept->id, 'name' => 'Duékoué', 'slug' => 'duekoue', 'postal_code' => null],

            // Daloa
            ['department_id' => $daloaDept->id, 'name' => 'Daloa', 'slug' => 'daloa', 'postal_code' => null],

            // Korhogo
            ['department_id' => $korhogoDept->id, 'name' => 'Korhogo', 'slug' => 'korhogo', 'postal_code' => null],

            // Séguéla
            ['department_id' => $seguelaDept->id, 'name' => 'Séguéla', 'slug' => 'seguela', 'postal_code' => null],
        ];

        $cities = [];
        foreach ($citiesData as $data) {
            $cities[] = City::create($data);
        }

        $this->command->info('✅ Villes créées.');

        // ============================================
        // 6. QUARTIERS
        // ============================================
        $abidjanCity = City::where('slug', 'abidjan')->first();
        $yopougonCity = City::where('slug', 'yopougon')->first();
        $guigloCity = City::where('slug', 'guiglo')->first();
        $daloaCity = City::where('slug', 'daloa')->first();
        $korhogoCity = City::where('slug', 'korhogo')->first();

        $neighborhoodsData = [
            // Abidjan (Cocody, Plateau, Marcory, etc.)
            ['city_id' => $abidjanCity->id, 'name' => 'Cocody', 'slug' => 'cocody'],
            ['city_id' => $abidjanCity->id, 'name' => 'Plateau', 'slug' => 'plateau'],
            ['city_id' => $abidjanCity->id, 'name' => 'Marcory', 'slug' => 'marcory'],
            ['city_id' => $abidjanCity->id, 'name' => 'Treichville', 'slug' => 'treichville'],
            ['city_id' => $abidjanCity->id, 'name' => 'Adjamé', 'slug' => 'adjame'],
            ['city_id' => $abidjanCity->id, 'name' => 'Abobo', 'slug' => 'abobo'],
            ['city_id' => $abidjanCity->id, 'name' => 'Attécoubé', 'slug' => 'attecoube'],
            ['city_id' => $abidjanCity->id, 'name' => 'Koumassi', 'slug' => 'koumassi'],
            ['city_id' => $abidjanCity->id, 'name' => 'Port-Bouët', 'slug' => 'port-bouet'],

            // Yopougon
            ['city_id' => $yopougonCity->id, 'name' => 'Yopougon Siporex', 'slug' => 'yopougon-siporex'],
            ['city_id' => $yopougonCity->id, 'name' => 'Yopougon Quartier', 'slug' => 'yopougon-quartier'],

            // Guiglo
            ['city_id' => $guigloCity->id, 'name' => 'Guiglo Centre', 'slug' => 'guiglo-centre'],
            ['city_id' => $guigloCity->id, 'name' => 'Guiglo Carrefour', 'slug' => 'guiglo-carrefour'],

            // Daloa
            ['city_id' => $daloaCity->id, 'name' => 'Daloa Centre', 'slug' => 'daloa-centre'],
            ['city_id' => $daloaCity->id, 'name' => 'Daloa Maroc', 'slug' => 'daloa-maroc'],

            // Korhogo
            ['city_id' => $korhogoCity->id, 'name' => 'Korhogo Centre', 'slug' => 'korhogo-centre'],
            ['city_id' => $korhogoCity->id, 'name' => 'Korhogo Bantou', 'slug' => 'korhogo-bantou'],
        ];

        $neighborhoods = [];
        foreach ($neighborhoodsData as $data) {
            $neighborhoods[] = Neighborhood::create($data);
        }

        $this->command->info('✅ Quartiers créés.');

        // ============================================
        // 7. RUES
        // ============================================
        $cocody = Neighborhood::where('slug', 'cocody')->first();
        $plateau = Neighborhood::where('slug', 'plateau')->first();
        $marcory = Neighborhood::where('slug', 'marcory')->first();
        $guigloCentre = Neighborhood::where('slug', 'guiglo-centre')->first();
        $daloaCentre = Neighborhood::where('slug', 'daloa-centre')->first();

        $streetsData = [
            // Cocody
            ['neighborhood_id' => $cocody->id, 'name' => 'Rue des Jardins', 'slug' => 'rue-des-jardins', 'type' => 'rue'],
            ['neighborhood_id' => $cocody->id, 'name' => 'Avenue des Lagunes', 'slug' => 'avenue-des-lagunes', 'type' => 'avenue'],
            ['neighborhood_id' => $cocody->id, 'name' => 'Boulevard de la Paix', 'slug' => 'boulevard-de-la-paix', 'type' => 'boulevard'],

            // Plateau
            ['neighborhood_id' => $plateau->id, 'name' => 'Avenue Treichville', 'slug' => 'avenue-treichville', 'type' => 'avenue'],
            ['neighborhood_id' => $plateau->id, 'name' => 'Rue du Commerce', 'slug' => 'rue-du-commerce', 'type' => 'rue'],

            // Marcory
            ['neighborhood_id' => $marcory->id, 'name' => 'Boulevard de Marseille', 'slug' => 'boulevard-de-marseille', 'type' => 'boulevard'],
            ['neighborhood_id' => $marcory->id, 'name' => 'Rue des Écoles', 'slug' => 'rue-des-ecoles', 'type' => 'rue'],

            // Guiglo
            ['neighborhood_id' => $guigloCentre->id, 'name' => 'Avenue de l\'Indépendance', 'slug' => 'avenue-independance', 'type' => 'avenue'],
            ['neighborhood_id' => $guigloCentre->id, 'name' => 'Rue des Marchés', 'slug' => 'rue-des-marches', 'type' => 'rue'],

            // Daloa
            ['neighborhood_id' => $daloaCentre->id, 'name' => 'Avenue du Commerce', 'slug' => 'avenue-du-commerce', 'type' => 'avenue'],
            ['neighborhood_id' => $daloaCentre->id, 'name' => 'Rue de l\'Hôpital', 'slug' => 'rue-de-hopital', 'type' => 'rue'],
        ];

        $streets = [];
        foreach ($streetsData as $data) {
            $streets[] = Street::create($data);
        }

        $this->command->info('✅ Rues créées.');

        // ============================================
        // 8. ADRESSES (exemples pour différents tenants)
        // ============================================
        // Pour les adresses, nous créons des exemples mais sans tenant_id car nous n'avons pas encore de tenants.
        // Nous les créerons après l'installation des tenants.

        $this->command->info('✅ Données géographiques de démonstration créées avec succès !');
        $this->command->info('');
        $this->command->info('📌 Pour créer des adresses spécifiques à un tenant, utilisez la commande :');
        $this->command->info('php artisan tinker');
        $this->command->info('et créez les adresses avec le tenant_id approprié.');
    }
}
