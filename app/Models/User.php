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
        'statut'
    ];

    protected $hidden = [
        'mot_de_passe_hash',
        'remember_token',
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
}