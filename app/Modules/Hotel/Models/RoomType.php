<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\Module\Hotel\RoomTypeFactory;

class RoomType extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'room_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'capacity_adults',
        'capacity_children',
        'amenities',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'amenities' => 'array',
        'is_active' => 'boolean',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    protected static function newFactory(): RoomTypeFactory
    {
    return RoomTypeFactory::new();
    }

    /**
     * Calcule le prix par nuit selon les Rate Plans chargés ou en base.
     */
    public function priceFor(DateTimeInterface $date): float
    {
        // 1. Si la relation est déjà chargée en mémoire, on filtre la collection (Évite N+1 queries)
        if ($this->relationLoaded('ratePlans')) {
            $applicable = $this->ratePlans
                ->filter(fn (RatePlan $plan) => $plan->starts_on <= $date && $plan->ends_on >= $date)
                ->sortByDesc('price_per_night')
                ->first();

            if ($applicable) {
                return (float) $applicable->price_per_night;
            }

            $default = $this->ratePlans->firstWhere('is_default', true);

            return (float) ($default?->price_per_night ?? $this->base_price);
        }

        // 2. Fallback SQL optimisé si la relation n'est pas eager-loaded
        $applicable = $this->ratePlans()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->orderByDesc('price_per_night')
            ->first();

        if ($applicable) {
            return (float) $applicable->price_per_night;
        }

        $default = $this->ratePlans()->where('is_default', true)->first();

        return (float) ($default?->price_per_night ?? $this->base_price);
    }
}
