<?php

namespace App\Modules\Entite\Models;

use Illuminate\Database\Eloquent\Model;

class Entite extends Model
{
    protected $fillable = [
        'id',
        'code',
        'denomination',
        'sigle',
        'type_entite',
        'localisation',
        'region',
        'responsable_id',
        'statut',
        'date_creation',
        'entite_parente_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Responsable
     */
    public function responsable()
    {
        return $this->belongsTo(\App\Models\User::class, 'responsable_id');
    }

    /**
     * Enfants
     */
    public function enfants()
    {
        return $this->hasMany(self::class, 'entite_parente_id');
    }

    /**
     * Parent
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'entite_parente_id');
    }

    public function missions()
    {
        return $this->hasMany(\App\Modules\Mission\Models\Mission::class);
    }
}