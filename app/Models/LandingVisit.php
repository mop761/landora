<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingVisit extends Model
{
    /** Una visita no se modifica nunca: solo tiene fecha de creación. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'landing_id',
        'visitor_hash',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'device',
    ];

    public function landing(): BelongsTo
    {
        return $this->belongsTo(Landing::class);
    }
}
