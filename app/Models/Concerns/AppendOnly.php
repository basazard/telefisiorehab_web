<?php

namespace App\Models\Concerns;

use LogicException;

/** Rows are written once and never changed or removed (audit trail, staff notes). Pair with UPDATED_AT = null. */
trait AppendOnly
{
    protected static function bootAppendOnly(): void
    {
        static::updating(fn () => throw new LogicException(class_basename(static::class).' tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException(class_basename(static::class).' tidak boleh dihapus.'));
    }
}
