<?php

namespace App\Modules\Report\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class Report extends Model
{
    use HasUuid;

    protected $table = 'rapports';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'mission_id',
        'reference',
        'titre',
        'synthese',
        'statut',
        'url_pdf',
        'validee_par',
        'date_validation',
        'date_transmission',
        'accuse_reception_at',
    ];

    protected $casts = [
        'date_validation'    => 'datetime',
        'date_transmission'  => 'datetime',
        'accuse_reception_at'=> 'datetime',
    ];

    public function mission()
    {
        return $this->belongsTo(\App\Modules\Mission\Models\Mission::class, 'mission_id');
    }

    public function validatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'validee_par');
    }
}
