<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Address;
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
            ->with('primaryAddress')
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
