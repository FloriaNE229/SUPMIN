<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Question extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'section_id',
        'libelle',
        'description_aide',
        'type_question',
        'options',
        'est_obligatoire',
        'condition_affichage',
        'ordre',
        'validation_regles'
    ];

    protected $casts = [
        'options' => 'array',
        'condition_affichage' => 'array',
        'validation_regles' => 'array'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Section
     */
    public function section()
    {
        return $this->belongsTo(
            \App\Modules\Form\Models\Section::class
        );
    }

    /**
     * Réponses
     */

    public function responses()
    {
        return $this->hasMany(\App\Modules\Response\Models\Response::class);
    }

    /**
     * Answers
     */
    
    public function answers()
    {
    return $this->hasMany(\App\Modules\Form\Models\Answer::class);
    }
}