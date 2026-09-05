<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Address;
use Illuminate\Http\Request;
use App\Modules\Hotel\Models\Hotel;

class GeoController extends Controller
{
    /**
     * Rechercher des hôtels à proximité
     */
    public function nearbyHotels(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $radius = $request->input('radius', 10);

        $hotels = Hotel::nearby($request->lat, $request->lng, $radius)
            ->with('addresses')
            ->get();

        return response()->json([
            'data' => $hotels,
            'meta' => [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'radius' => $radius,
                'count' => $hotels->count(),
            ],
        ]);
    }

    /**
     * Lister les continents
     */
    public function continents(Request $request)
    {
        $continents = Continent::active()
            ->withCount('countries')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($continents);
    }

    /**
     * Lister les pays, filtrables par continent
     */
    public function countries(Request $request)
    {
        $request->validate([
            'continent_id' => 'nullable|exists:continents,id',
            'search' => 'nullable|string|max:100',
        ]);

        $countries = Country::active()
            ->when($request->filled('continent_id'), fn ($q) => $q->where('continent_id', $request->continent_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('regions')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($countries);
    }

    /**
     * Lister les régions, filtrables par pays
     */
    public function regions(Request $request)
    {
        $request->validate([
            'country_id' => 'nullable|exists:countries,id',
            'search' => 'nullable|string|max:100',
        ]);

        $regions = Region::active()
            ->when($request->filled('country_id'), fn ($q) => $q->where('country_id', $request->country_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('departments')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($regions);
    }

    /**
     * Lister les départements, filtrables par région
     */
    public function departments(Request $request)
    {
        $request->validate([
            'region_id' => 'nullable|exists:regions,id',
            'search' => 'nullable|string|max:100',
        ]);

        $departments = Department::active()
            ->when($request->filled('region_id'), fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('cities')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($departments);
    }

    /**
     * Lister les villes, filtrables par département
     */
    public function cities(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'search' => 'nullable|string|max:100',
        ]);

        $cities = City::active()
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('neighborhoods')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($cities);
    }

    /**
     * Lister les quartiers, filtrables par ville
     */
    public function neighborhoods(Request $request)
    {
        $request->validate([
            'city_id' => 'nullable|exists:cities,id',
            'search' => 'nullable|string|max:100',
        ]);

        $neighborhoods = Neighborhood::active()
            ->when($request->filled('city_id'), fn ($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->withCount('streets')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($neighborhoods);
    }

    /**
     * Lister les rues, filtrables par quartier
     */
    public function streets(Request $request)
    {
        $request->validate([
            'neighborhood_id' => 'nullable|exists:neighborhoods,id',
            'search' => 'nullable|string|max:100',
        ]);

        $streets = Street::active()
            ->when($request->filled('neighborhood_id'), fn ($q) => $q->where('neighborhood_id', $request->neighborhood_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 100));

        return response()->json($streets);
    }

    /**
     * Rechercher des commerces (entités non-hôtel disposant d'une adresse géolocalisée) à proximité.
     *
     * NB: il n'existe pas encore de modèle "Commerce" dédié dans le projet — cette méthode
     * s'appuie sur la table addresses polymorphe (entity_type/entity_id) et exclut simplement
     * les hôtels (déjà couverts par nearbyHotels). À affiner si un module Commerce apparaît.
     */
    public function nearbyCommerces(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $radius = $request->input('radius', 10);

        $commerces = Address::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('entity_type', '!=', Hotel::class)
            ->with('addressable')
            ->get()
            ->map(function (Address $address) use ($request) {
                $address->distance_km = round($address->distanceTo($request->lat, $request->lng), 2);
                return $address;
            })
            ->filter(fn (Address $address) => $address->distance_km <= $radius)
            ->sortBy('distance_km')
            ->values();

        return response()->json([
            'data' => $commerces,
            'meta' => [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'radius' => $radius,
                'count' => $commerces->count(),
            ],
        ]);
    }

    /**
     * Rechercher tout ce qui est géolocalisable à proximité (hôtels + autres entités adressées).
     */
    public function nearbyAll(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $radius = $request->input('radius', 10);

        $hotels = Hotel::nearby($request->lat, $request->lng, $radius)
            ->with('addresses')
            ->get()
            ->map(fn (Hotel $hotel) => [
                'type' => 'hotel',
                'entity' => $hotel,
            ]);

        $others = Address::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('entity_type', '!=', Hotel::class)
            ->with('addressable')
            ->get()
            ->map(function (Address $address) use ($request) {
                $address->distance_km = round($address->distanceTo($request->lat, $request->lng), 2);
                return $address;
            })
            ->filter(fn (Address $address) => $address->distance_km <= $radius)
            ->map(fn (Address $address) => [
                'type' => 'other',
                'entity' => $address,
            ]);

        $results = $hotels->concat($others)->values();

        return response()->json([
            'data' => $results,
            'meta' => [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'radius' => $radius,
                'count' => $results->count(),
            ],
        ]);
    }

    /**
     * Calculer la distance entre deux adresses
     */
    public function distance(Request $request)
    {
        $request->validate([
            'address1_id' => 'required|exists:addresses,id',
            'address2_id' => 'required|exists:addresses,id',
        ]);

        $address1 = Address::find($request->address1_id);
        $address2 = Address::find($request->address2_id);

        if (!$address1->hasCoordinates() || !$address2->hasCoordinates()) {
            return response()->json([
                'error' => 'Les deux adresses doivent avoir des coordonnées GPS',
                'code' => 'MISSING_COORDINATES',
            ], 422);
        }

        $distance = Address::calculateDistance(
            $address1->latitude,
            $address1->longitude,
            $address2->latitude,
            $address2->longitude
        );

        return response()->json([
            'data' => [
                'address1' => $address1->full_address,
                'address2' => $address2->full_address,
                'distance_km' => round($distance, 2),
                'distance_m' => round($distance * 1000, 0),
            ],
        ]);
    }
}
