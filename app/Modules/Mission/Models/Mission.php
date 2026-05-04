<?php

namespace App\Modules\Mission\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Mission extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'reference',
        'entite_id',
        'coordinateur_id',
        'objectif',
        'axes_prioritaires',
        'date_debut',
        'date_fin_prevue',
        'date_fin_effective',
        'statut',
        'annee_supervision'
    ];

    protected $casts = [
        'axes_prioritaires' => 'array'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     *  Entité
     */
    public function entite()
    {
        return $this->belongsTo(
            \App\Modules\Entite\Models\Entite::class
        );
    }

    /**
     *  Coordinateur
     */
    public function coordinateur()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'coordinateur_id'
        );
    }

    /**
     *  Forms liés à la mission
     */
    public function forms()
    {
        return $this->belongsToMany(
            \App\Modules\Form\Models\Form::class,
            'mission_forms'
        );
    }

    /**
     *  Réponses
     */
    public function responses()
    {
        return $this->hasMany(
            \App\Modules\Response\Models\Response::class
        );
    }

    /**
     *  Recommandations
     */
    public function recommendations()
    {
        return $this->hasMany(
            \App\Modules\Recommendation\Models\Recommendation::class
        );
    }

    /**
     *  Agents (pivot mission_users)
     */
    public function agents()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'mission_users'
        )->withPivot('role');
    }

    /**
     *  Leader (helper, pas relation)
     */
    public function leader()
    {
        return $this->agents()
            ->wherePivot('role', 'leader')
            ->first();
    }

public function answers()
{
    return $this->hasMany(\App\Modules\Form\Models\Answer::class);
}
}