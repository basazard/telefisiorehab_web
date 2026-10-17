<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
