<?php

namespace App\Modules\Recommendation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Recommendation extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'mission_id',
        'question_id',
        'reference',
        'intitule',
        'description',
        'priorite',
        'responsable_id',
        'delai_realisation',
        'statut',
        'nb_reports',
        'recommandation_parente_id',
        'creee_par',
        'validee_par'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'due_date' => 'date',
    ];

    public function mission()
{
    return $this->belongsTo(\App\Modules\Mission\Models\Mission::class);
}

public function question()
{
    return $this->belongsTo(\App\Modules\Form\Models\Question::class);
}

public function responsable()
{
    return $this->belongsTo(\App\Models\User::class, 'responsable_id');
}
}