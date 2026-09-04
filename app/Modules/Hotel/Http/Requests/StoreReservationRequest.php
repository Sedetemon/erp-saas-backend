<?php

namespace App\Modules\Hotel\Http\Requests;

use App\Rules\RoomIsAvailable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation appliquées à la requête.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Informations client
            'guest_id' => [
                'required',
                'string',
                Rule::exists('guests', 'id'),
            ],

            // Dates du séjour
            'check_in_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'check_out_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after:check_in_date',
            ],

            // Occupants
            'adults' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20',
            ],
            'children' => [
                'sometimes',
                'integer',
                'min:0',
                'max:20',
            ],

            // Métadonnées
            'source' => [
                'sometimes',
                'string',
                Rule::in(['walk_in', 'booking_com', 'expedia', 'phone', 'website', 'other']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Tableau des chambres rattachées
            'rooms' => [
                'required',
                'array',
                'min:1',
            ],
            'rooms.*.room_type_id' => [
                'required',
                'string',
                Rule::exists('room_types', 'id'),
            ],
            'rooms.*.room_id' => [
                'nullable',
                'string',
                Rule::exists('rooms', 'id'),
                // Injecte la règle personnalisée avec les dates de la requête
                new RoomIsAvailable(
                    $this->input('check_in_date'),
                    $this->input('check_out_date')
                ),
            ],
            'rooms.*.rate_per_night' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in_date.after_or_equal' => "La date d'arrivée ne peut pas être antérieure à aujourd'hui.",
            'check_out_date.after'         => "La date de départ doit être strictly postérieure à la date d'arrivée.",
            'rooms.required'               => "Vous devez sélectionner au moins un type de chambre ou une chambre.",
            'rooms.*.room_type_id.exists'  => "Le type de chambre sélectionné est invalide.",
            'rooms.*.room_id.exists'       => "La chambre sélectionnée n'existe pas.",
            'rooms.*.rate_per_night.min'   => "Le tarif par nuitée ne peut pas être négatif.",
        ];
    }

    /**
     * Préparation des données avant validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'adults'   => $this->adults ?? 1,
            'children' => $this->children ?? 0,
            'source'   => $this->source ?? 'walk_in',
        ]);
    }
}
