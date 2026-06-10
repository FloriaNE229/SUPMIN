<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Shared\Traits\HasUuid;

class AuditLog extends Model
{
    use HasUuid;

    protected $fillable = [

        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address'

    ];

    protected $casts = [

        'old_values' => 'array',
        'new_values' => 'array',

    ];

    public $incrementing = false;

    protected $keyType = 'string';
}