<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Unit extends Model
{
    public const TYPES = [
        'room' => 'oda',
        'hall' => 'salon',
        'villa' => 'villa',
    ];

    protected $fillable = ['hotel_id', 'type', 'name', 'capacity', 'quantity', 'price_per_night'];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isHall(): bool
    {
        return $this->type === 'hall';
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // Boşta kalan oda sayısını döndürür.
    public function availableQuantity(Carbon $checkIn, Carbon $checkOut): int
    {
        $booked = $this->reservations()
            ->where('status', '!=', 'cancelled')
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->sum('quantity');

        return max(0, $this->quantity - (int) $booked);
    }
}
