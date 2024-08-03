<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UsesUuid
{
    /**
     * Boot the trait to add a creating model event to generate UUIDs.
     */
    protected static function bootUsesUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string)Str::orderedUuid();
            }
        });
    }

    public function getIncrementing(): false
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
