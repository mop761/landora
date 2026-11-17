<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'layout',
    ];

    /**
     * Conversión de tipos: el diseño se guarda y se recupera como array (JSON).
     */
    protected function casts(): array
    {
        return [
            'layout' => 'array',
        ];
    }

    /**
     * La landing pertenece a un usuario (su propietario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Una landing tiene muchos leads (los contactos capturados por su formulario).
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
