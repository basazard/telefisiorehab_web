<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory, BelongsToClinic;

    protected $fillable = ['clinic_id', 'patient_id', 'created_by', 'title', 'status', 'starts_on', 'ends_on'];
}
