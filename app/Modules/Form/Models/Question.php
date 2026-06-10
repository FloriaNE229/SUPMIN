<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Question extends Model
{
    use HasUuid;

    protected $table = 'questions';

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
        'validation_regles',
    ];

    protected $casts = [
        'options' => 'array',
        'est_obligatoire' => 'boolean',
        'condition_affichage' => 'array',
        'validation_regles' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * La question appartient à une section.
     */
    public function section()
    {
        return $this->belongsTo(
            \App\Modules\Form\Models\Section::class,
            'section_id'
        );
    }
}