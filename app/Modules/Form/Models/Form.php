<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Form extends Model
{
    use HasUuid;

    protected $table = 'forms';

    /**
     * Champs autorisés en mass assignment.
     */
    protected $fillable = [
        'id',
        'code',
        'mission_id',
        'titre',
        'description',
        'schema',
        'est_modele',
        'version',
        'statut',
        'user_id',
    ];

    /**
     * Casts automatiques.
     */
    protected $casts = [
        'schema' => 'array',
        'est_modele' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * UUID non auto-incrémenté.
     */
    public $incrementing = false;

    /**
     * Type de clé primaire.
     */
    protected $keyType = 'string';

    /**
     * Mission associée (nullable).
     */
    public function mission()
    {
        return $this->belongsTo(
            \App\Modules\Mission\Models\Mission::class,
            'mission_id'
        );
    }

    /**
     * Créateur du formulaire.
     */
    public function user()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'user_id'
        );
    }

    /**
     * Un formulaire possède plusieurs sections.
     */
    public function sections()
    {
        return $this->hasMany(
            \App\Modules\Form\Models\Section::class,
            'form_id'
        );
    }
}