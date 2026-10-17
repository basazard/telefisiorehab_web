<?php

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\Scope|\Closure|string $scope, \Closure|null $implementation = null)
 * @method static void creating(\Closure|string $callback)
 */
trait BelongsToClinic
{
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $query) {
            $user = Auth::user();

            if (! $user || in_array($user->role, [Role::PlatformAdmin, Role::Patient], true)) {
                return;
            }

            $query->where($query->getModel()->getTable() . '.clinic_id', $user->clinic_id);
        });

        static::creating(function (Model $model) {
            $model->clinic_id ??= Auth::user()?->clinic_id;
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
