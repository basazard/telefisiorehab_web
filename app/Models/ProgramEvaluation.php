<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Monitoring dan Evaluasi" questionnaire of a program. */
class ProgramEvaluation extends Model
{
    use BelongsToClinic;

    public const GLOBAL_CHANGE = ['better' => 'Lebih baik', 'same' => 'Sama saja', 'worse' => 'Lebih buruk'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'program_id', 'pain', 'function', 'mobility', 'global_change', 'new_complaint', 'adherence', 'improved',
    ];

    protected function casts(): array
    {
        return ['new_complaint' => 'boolean', 'improved' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
