<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Support\Str;

/** Privacy and terms pages and the consent statements (PRD 11 E4). Texts live in resources/legal and config/legal.php. */
class Legal
{
    /** slug => [title, file] */
    public const PAGES = [
        'privasi' => ['Kebijakan Privasi', 'privasi-id.md'],
        'syarat' => ['Syarat Penggunaan', 'syarat-id.md'],
    ];

    public static function path(string $page): string
    {
        return resource_path('legal/'.self::PAGES[$page][1]);
    }

    /** The page as HTML, without the placeholder marker line (the page shows a banner instead). */
    public static function html(string $page): string
    {
        $text = str_replace(config('legal.placeholder_marker'), '', file_get_contents(self::path($page)));

        return Str::markdown(trim($text), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /** Whether this page's text is still the temporary one. */
    public static function isPlaceholder(string $page): bool
    {
        return str_contains(file_get_contents(self::path($page)), config('legal.placeholder_marker'));
    }

    /** Whether any legal text is still temporary: the go-live check (E10) fails then. */
    public static function hasPlaceholder(): bool
    {
        foreach (array_keys(self::PAGES) as $page) {
            if (self::isPlaceholder($page)) {
                return true;
            }
        }

        return false;
    }

    /** The statements this user must tick: key => text. */
    public static function items(User $user): array
    {
        return config('legal.items.'.($user->role === Role::Patient ? 'patient' : 'staff'));
    }

    /** Whether the user accepted the current version. */
    public static function hasConsented(User $user): bool
    {
        return Consent::query()->where('user_id', $user->id)->where('version', config('legal.consent_version'))->exists();
    }

    /** Records the acceptance of the current version (every statement must have been ticked by the caller). */
    public static function accept(User $user): Consent
    {
        return Consent::query()->create([
            'user_id' => $user->id,
            'version' => config('legal.consent_version'),
            'items' => array_keys(self::items($user)),
            'accepted_at' => now(),
        ]);
    }
}
