<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Section extends Model
{
    use HasUuid;

    protected $table = 'sections';

    protected $fillable = [
        'id',
        'form_id',
        'title',
        'description',
        'order',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * La section appartient à un formulaire.
     */
    public function form()
    {
        return $this->belongsTo(
            \App\Modules\Form\Models\Form::class,
            'form_id'
        );
    }

    /**
     * Une section contient plusieurs questions.
     */
    public function questions()
    {
        return $this->hasMany(
            \App\Modules\Form\Models\Question::class,
            'section_id'
        );
    }
}