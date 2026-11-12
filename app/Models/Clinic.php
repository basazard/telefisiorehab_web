<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    /** @use HasFactory<\Database\Factories\ClinicFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'status'];

    /** No look-alike characters (0/O, 1/I/L), so a code can be read aloud and typed. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** DRAFT: 8 characters, 31^8 possible codes. */
    private const CODE_LENGTH = 8;

    protected static function booted(): void
    {
        static::creating(function (Clinic $clinic) {
            $clinic->join_code ??= self::newJoinCode();
        });
    }

    /** A random code that no clinic has yet. */
    public static function newJoinCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::query()->where('join_code', $code)->exists());

        return $code;
    }

    /** The code as people type it: case and spaces do not matter. */
    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', (string) $code));
    }

    /** An active clinic with this code, or null. */
    public static function findByJoinCode(?string $code): ?self
    {
        $code = self::normalizeCode($code);

        return $code === '' ? null : static::query()->where('join_code', $code)->where('status', 'active')->first();
    }

    /** A new code; the old one stops working at once. */
    public function regenerateJoinCode(): string
    {
        $this->forceFill(['join_code' => self::newJoinCode()])->save();

        return $this->join_code;
    }

    /** The link a clinic gives its patients. */
    public function registerUrl(): string
    {
        return route('register', ['clinic' => $this->join_code]);
    }
}
