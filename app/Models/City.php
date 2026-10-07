<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name', 'country'];

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }
}
