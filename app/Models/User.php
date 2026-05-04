<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe_hash',
        'telephone',
        'statut'
    ];

    protected $hidden = [
        'mot_de_passe_hash',
        'remember_token',
    ];

    /**
     *  Auth password 
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    /**
     *  Missions
     */
    public function missions()
    {
        return $this->belongsToMany(
            \App\Modules\Mission\Models\Mission::class
        );
    }

    /**
     *  Roles (pivot user_roles)
     */
    public function roles()
    {
        return $this->belongsToMany(
            \App\Modules\Role\Models\Role::class,
            'user_roles'
        )->withPivot(['date_attribution', 'attribue_par']);
    }
}