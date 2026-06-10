<?php

namespace App\Modules\Recommendation\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class RecommendationTracking extends Model
{
    use HasUuid;

    protected $table = 'suivi_recommandations';

    protected $fillable = [
        'id',
        'recommandation_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'preuves_jointes',
        'updated_by',
        'created_at'
    ];

    protected $casts = [
        'preuves_jointes' => 'array',
        'created_at' => 'datetime'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function recommendation()
    {
        return $this->belongsTo(
            \App\Modules\Recommendation\Models\Recommendation::class,
            'recommandation_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'updated_by'
        );
    }
}