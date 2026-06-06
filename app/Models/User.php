<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Modules\Shared\Traits\HasUuid;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles, HasUuid;

    protected string $guard_name = 'sanctum';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe_hash',
        'telephone',
        'statut',
        'tentatives_echec',
        'date_derniere_connexion',
        'compte_active',
        'mdp_activation',
        'tentatives_activation',
        'compte_bloque',
    ];

    protected $hidden = [
        'mot_de_passe_hash',
        'remember_token',
    ];

    protected $casts = [
        'compte_active'           => 'boolean',
        'compte_bloque'           => 'boolean',
        'tentatives_echec'        => 'integer',
        'tentatives_activation'   => 'integer',
        'date_derniere_connexion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | UUID
    |--------------------------------------------------------------------------
    */

    public $incrementing = false;
    protected $keyType = 'string';

    /*
    |--------------------------------------------------------------------------
    | AUTH PASSWORD
    |--------------------------------------------------------------------------
    */

    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    /*
    |--------------------------------------------------------------------------
    | MISSIONS
    |--------------------------------------------------------------------------
    */

    public function missions()
    {
        return $this->belongsToMany(
            \App\Modules\Mission\Models\Mission::class
        );
    }

    /**
     * Missions assignées (pivot mission_user)
     */
    public function assignedMissions()
    {
        return $this->belongsToMany(
            \App\Modules\Mission\Models\Mission::class,
            'mission_user',
            'user_id',
            'mission_id'
        )->withPivot('role')
         ->withTimestamps();
    }
}