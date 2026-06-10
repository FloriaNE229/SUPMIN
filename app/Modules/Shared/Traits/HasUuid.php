<?php

namespace App\Modules\Shared\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot trait
     */
    public static function bootHasUuid(): void
    {
        static::creating(function ($model): void {

            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

        });
    }

    /**
     * Disable auto increment
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * UUID key type
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}