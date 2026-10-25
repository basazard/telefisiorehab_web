<?php

namespace App\Models\Concerns;

/** Stores the clinical rule set version (config('clinical.version')) on every new row (PRD 10 W5). */
trait RecordsClinicalVersion
{
    protected static function bootRecordsClinicalVersion(): void
    {
        static::creating(function ($model) {
            $model->clinical_version ??= config('clinical.version');
        });
    }
}
