<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Support\Onboarding\Assessment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedFlagCheck extends Model
{
    use BelongsToClinic;

    protected $fillable = ['clinic_id', 'screening_id', 'patient_id', 'answers', 'flagged', 'cleared', 'acknowledged_at'];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'flagged' => 'array',
            'cleared' => 'boolean',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    /** Never trust a "flagged" list from the browser: always derive it from the answers. */
    public static function attributesFromAnswers(array $answers): array
    {
        $flagged = Assessment::flaggedRedFlags($answers);

        return ['answers' => $answers, 'flagged' => $flagged, 'cleared' => $flagged === []];
    }

    public function requiresAcknowledgement(): bool
    {
        return ! empty($this->flagged) && $this->acknowledged_at === null;
    }

    public function canProceed(): bool
    {
        return ! $this->requiresAcknowledgement();
    }
}
