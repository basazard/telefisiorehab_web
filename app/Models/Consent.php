<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A user's acceptance of one version of the consent statements (PRD 11 E4). Never changed or removed. */
class Consent extends Model
{
    use AppendOnly;

    public $timestamps = false;

    protected $fillable = ['user_id', 'version', 'items', 'accepted_at'];

    protected function casts(): array
    {
        return ['items' => 'array', 'accepted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
