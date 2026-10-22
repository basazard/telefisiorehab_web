<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Telekonsultasi / rujukan request from the Exercise Recommendation module, handled by clinic staff. */
class ConsultationRequest extends Model
{
    use HasFactory, BelongsToClinic;

    public const TYPES = [
        'teleconsultation' => 'Telekonsultasi dengan fisioterapis',
        'referral' => 'Rujukan ke fasilitas kesehatan',
    ];

    protected $fillable = [
        'clinic_id', 'patient_id', 'assessment_id', 'type', 'reason', 'status', 'handled_by', 'handled_at', 'staff_note',
    ];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
