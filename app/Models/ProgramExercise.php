<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramExercise extends Model
{
    protected $fillable = ['program_id', 'exercise_id', 'sets', 'reps', 'hold_seconds', 'sort_order'];
}
