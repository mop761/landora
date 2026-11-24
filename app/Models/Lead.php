<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'landing_id',
        'data',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip',
        'user_agent',
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

    /**
     * Todos los envíos de este lead al webhook (incluidos los reintentos).
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * El último envío al webhook, para mostrar su estado en el listado de leads.
     */
    public function latestDelivery(): HasOne
    {
        return $this->hasOne(WebhookDelivery::class)->latestOfMany();
    }
}
