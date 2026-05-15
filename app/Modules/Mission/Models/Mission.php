<?php

namespace App\Modules\Mission\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Mission extends Model
{
    use HasUuid;

    protected $table = 'missions';

    protected $fillable = [
        'id',
        'reference',
        'entity_id',
        'coordinateur_id',
        'objectif',
        'axes_prioritaires',
        'date_debut',
        'date_fin_prevue',
        'date_fin_effective',
        'statut',
        'annee_supervision',
        'pdf_path',
    ];

    protected $casts = [
        'axes_prioritaires' => 'array',
        'date_debut' => 'date',
        'date_fin_prevue' => 'date',
        'date_fin_effective' => 'date',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Entité liée à la mission
     */
    public function entity()
    {
        return $this->belongsTo(
            \App\Modules\Entities\Models\Entity::class,
            'entity_id'
        );
    }

    /**
     * Alias pour compatibilité : mission->entite
     */
    public function entite()
    {
        return $this->entity();
    }

    /**
     * Coordinateur de la mission
     */
    public function coordinateur()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'coordinateur_id'
        );
    }

    /**
     * Alias pour compatibilité : mission->user
     */
    public function user()
    {
        return $this->coordinateur();
    }

    /**
     * Formulaires liés à la mission
     */
    public function forms()
    {
        return $this->belongsToMany(
            \App\Modules\Form\Models\Form::class,
            'mission_forms',
            'mission_id',
            'form_id'
        );
    }

    /**
     * Réponses
     */
    public function responses()
    {
        return $this->hasMany(
            \App\Modules\Response\Models\Response::class,
            'mission_id'
        );
    }

    /**
     * Recommandations
     */
    public function recommendations()
    {
        return $this->hasMany(
            \App\Modules\Recommendation\Models\Recommendation::class,
            'mission_id'
        );
    }

    /**
     * Agents affectés à la mission
     */
    public function agents()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'mission_user',
            'mission_id',
            'user_id'
        )
        ->withPivot('role')
        ->withTimestamps();
    }

    /**
     * Leader de la mission
     */
    public function leader()
    {
        return $this->agents()
            ->wherePivot('role', 'leader')
            ->first();
    }

    /**
     * Réponses aux formulaires
     */
    public function answers()
    {
        return $this->hasMany(
            \App\Modules\Form\Models\Answer::class,
            'mission_id'
        );
    }

    /**
     * Logs d'audit de la mission
     */
    public function logs()
    {
        return $this->hasMany(
            \App\Modules\Mission\Models\MissionLog::class,
            'mission_id'
        );
    }
}