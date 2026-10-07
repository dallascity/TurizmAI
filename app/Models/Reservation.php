<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'unit_id', 'chat_session_id', 'guest_name', 'guest_email', 'guest_phone',
        'check_in', 'check_out', 'quantity', 'guests', 'total_price', 'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->guest_name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }
}
