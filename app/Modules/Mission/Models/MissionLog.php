<?php

namespace App\Modules\Mission\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class MissionLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'mission_id',
        'user_id',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }
}