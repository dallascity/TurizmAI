<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = ['uuid'];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
