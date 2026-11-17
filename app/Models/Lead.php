<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'landing_id',
        'data',
    ];

    /**
     * Los datos del formulario se guardan y recuperan como array (JSON).
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * El lead pertenece a la landing donde se capturó.
     */
    public function landing(): BelongsTo
    {
        return $this->belongsTo(Landing::class);
    }
}
