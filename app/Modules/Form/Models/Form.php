<?php

namespace App\Modules\Form\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Form extends Model
{
    use HasUuid;

    protected $table = 'formulaires';

    protected $fillable = [
        'id',
        'mission_id',
        'titre',
        'description',
        'est_modele',
        'version',
        'statut',
        'cree_par'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Mission
     */
    public function mission()
    {
        return $this->belongsTo(
            \App\Modules\Mission\Models\Mission::class
        );
    }

    /**
     * Créateur
     */
    public function creator()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'cree_par'
        );
    }

    /**
     * Sections
     */
    public function sections()
    {
        return $this->hasMany(
            \App\Modules\Form\Models\Section::class
        );
    }
}