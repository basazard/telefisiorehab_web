<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An invitation to join a clinic as staff. Created and accepted through App\Support\Staff\Invitations. */
class StaffInvitation extends Model
{
    protected $fillable = ['clinic_id', 'email', 'role', 'token_hash', 'expires_at', 'invited_by', 'accepted_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['role' => Role::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
