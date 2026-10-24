<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramExercise extends Model
{
    use HasFactory;

    protected $fillable = ['program_id', 'exercise_id', 'sets', 'reps', 'hold_seconds', 'minutes', 'sort_order', 'removed_at'];

    protected function casts(): array
    {
        return ['removed_at' => 'datetime'];
    }

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class)->withoutGlobalScopes();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    /** "3 × 10 repetisi", "2 × tahan 20 detik", "15 menit". */
    public function doseLabel(): string
    {
        return match (true) {
            $this->minutes !== null => "{$this->minutes} menit",
            $this->hold_seconds !== null => "{$this->sets} × tahan {$this->hold_seconds} detik",
            default => "{$this->sets} × {$this->reps} repetisi",
        };
    }
}
