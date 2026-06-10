<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Mission\Models\Mission;

class Answer extends Model
{
    protected $fillable = [
        'mission_id',
        'question_id',
        'value',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}