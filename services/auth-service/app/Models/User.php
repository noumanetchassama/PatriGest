<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Structures administratives auxquelles l'utilisateur appartient.
     */
    public function structures(): BelongsToMany
    {
        return $this->belongsToMany(Structure::class, 'user_structure')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Structure principale de l'utilisateur.
     */
    public function primaryStructure()
    {
        return $this->structures()->wherePivot('is_primary', true)->first();
    }

    /**
     * Connexions enregistrées de l'utilisateur.
     */
    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }
}
