<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Section extends Model
{
    use HasUuid;

    protected $table = 'sections_formulaire';

    protected $fillable = [
        'id',
        'formulaire_id',
        'titre',
        'description',
        'ordre'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * 🔗 Formulaire
     */
    public function form()
    {
        return $this->belongsTo(
            \App\Modules\Form\Models\Form::class,
            'formulaire_id'
        );
    }

    /**
     * 🔗 Questions
     */
    public function questions()
    {
        return $this->hasMany(
            \App\Modules\Form\Models\Question::class
        )->orderBy('ordre');
    }

}