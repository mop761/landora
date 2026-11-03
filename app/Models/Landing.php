<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Landing extends Model
{
    /**
     * Campos que se pueden asignar de forma masiva.
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'status',
    ];

    /**
     * La landing pertenece a un usuario (su propietario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
