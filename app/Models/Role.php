<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Str;

class Role extends SpatieRole
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'guard_name',
        'code',
        'libelle',
        'description',
        'permissions'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}