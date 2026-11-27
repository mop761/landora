<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ¿El usuario es administrador?
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * ¿El usuario tiene el rol indicado?
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Landings de las que es propietario.
     */
    public function landings(): HasMany
    {
        return $this->hasMany(Landing::class);
    }

    /**
     * Leads de todas sus landings: usuario -> landings -> leads (relación "a través de").
     */
    public function leads(): HasManyThrough
    {
        return $this->hasManyThrough(Lead::class, Landing::class);
    }
}
