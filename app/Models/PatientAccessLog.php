<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit trail of staff access to a patient's data. Written through App\Support\Staff\Audit. */
class PatientAccessLog extends Model
{
    use AppendOnly;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'patient_id', 'action'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
