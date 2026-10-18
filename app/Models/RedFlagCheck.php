<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Support\Onboarding\RedFlags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedFlagCheck extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'screening_id', 'patient_id', 'answers', 'other_text', 'flagged', 'cleared',
        'stopped_at', 'unlocked_at', 'unlocked_by', 'unlock_note',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'flagged' => 'array',
            'cleared' => 'boolean',
            'stopped_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    /** Hard stop: set when any answer was "yes", cleared only by a clinic therapist. */
    public function isLocked(): bool
    {
        return $this->stopped_at !== null && $this->unlocked_at === null;
    }

    /** @return string[] */
    public function flaggedLabels(): array
    {
        return RedFlags::labels($this->flagged ?? []);
    }
}
