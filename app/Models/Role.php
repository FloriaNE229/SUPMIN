<?php

namespace App\Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'id',
        'code',
        'libelle',
        'description',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function users()
{
    return $this->belongsToMany(
        \App\Models\User::class,
        'user_roles'
    );
}
}