<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseFactory> */
    use HasFactory, BelongsToClinic;

    protected $fillable = ['clinic_id', 'name', 'body_area', 'video_url', 'instructions', 'slug', 'type', 'dose_unit', 'precautions'];

    protected function casts(): array
    {
        return ['type' => ExerciseType::class];
    }
}
