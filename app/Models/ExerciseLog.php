<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseLog extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseLogFactory> */
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'program_exercise_id',
        'performed_at',
        'sets_done',
        'reps_done',
        'duration_seconds',
        'pain_score',
        'effort',
        'notes',
    ];

    protected function casts(): array
    {
        return ['performed_at' => 'datetime'];
    }

    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(ProgramExercise::class);
    }
}
