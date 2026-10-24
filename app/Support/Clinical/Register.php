<?php

namespace App\Support\Clinical;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

/**
 * The clinical parameter register (PRD 10 W1): prd/clinical/parameters.json is the source of truth. Each entry points to
 * its place in the code with a `location`:
 *
 *   RedFlags::PAIN_STOP_THRESHOLD           a constant
 *   RedFlags::ITEMS[chest_pain]             one element of a constant array (nested: PATHWAYS[low][weeks])
 *   ClinicalAssessment::pickDomain()        a rule: the source code of the method
 *   resources/views/....blade.php#P-TX-01   a text block between {{-- @clinical P-TX-01 --}} and {{-- @endclinical --}}
 *
 * The code carries an `@clinical P-XXX` tag at each place. A hash of the current value lets `clinical:status` notice when
 * the code changed after a value was approved.
 */
final class Register
{
    public const STATUSES = ['draft', 'approved', 'changed', 'removed'];

    public const UNKNOWN = 'tidak diketahui, perlu diisi';

    /** Where short class names in a location are looked up. */
    private const NAMESPACES = ['App\\Support\\Onboarding\\', 'App\\Support\\Staff\\'];

    /** Tests point the register at their own file. */
    private static ?string $path = null;

    public static function usePath(?string $path): void
    {
        self::$path = $path;
    }

    public static function path(): string
    {
        return self::$path ?? base_path('prd/clinical/parameters.json');
    }

    /** @return array<string, mixed> the whole file */
    public static function load(?string $path = null): array
    {
        $data = json_decode(file_get_contents($path ?? self::path()), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    public static function entries(?string $path = null): array
    {
        return self::load($path)['parameters'];
    }

    /**
     * The current value of a location: the PHP value of a constant or element, or the source text of a rule or text block.
     */
    public static function resolve(string $location): mixed
    {
        if (str_contains($location, '.blade.php#')) {
            return self::block($location);
        }

        if (preg_match('/^(\w+)::(\w+)\(\)$/', $location, $m)) {
            return self::method($m[1], $m[2]);
        }

        if (preg_match('/^(\w+)::([A-Z0-9_]+)((?:\[[^\]]+\])*)$/', $location, $m)) {
            $class = new ReflectionClass(self::class($m[1]));
            if (! $class->hasConstant($m[2])) {
                throw new InvalidArgumentException("Unknown constant [$m[2]] in [$location].");
            }
            $value = $class->getConstant($m[2]);
            preg_match_all('/\[([^\]]+)\]/', $m[3], $keys);

            foreach ($keys[1] as $key) {
                if (! is_array($value) || ! array_key_exists($key, $value)) {
                    throw new InvalidArgumentException("Unknown key [$key] in [$location].");
                }
                $value = $value[$key];
            }

            return $value;
        }

        throw new InvalidArgumentException("Unknown location [$location].");
    }

    /** Hash of the current value, compared with `approved_hash`. */
    public static function hash(string $location): string
    {
        return hash('sha256', json_encode(self::resolve($location), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** True for a location whose value is a rule or a text block (compared by hash only, not by value). */
    public static function isSource(string $location): bool
    {
        return str_contains($location, '.blade.php#') || str_ends_with($location, '()');
    }

    /** The file that must carry the `@clinical` tag of this location. */
    public static function file(string $location): string
    {
        if (str_contains($location, '.blade.php#')) {
            return base_path(strstr($location, '#', true));
        }

        preg_match('/^(\w+)::/', $location, $m);

        return (new ReflectionClass(self::class($m[1])))->getFileName();
    }

    /** @return class-string */
    public static function class(string $short): string
    {
        foreach (self::NAMESPACES as $namespace) {
            if (class_exists($namespace.$short)) {
                return $namespace.$short;
            }
        }

        throw new InvalidArgumentException("Unknown class [$short].");
    }

    /** Method source without layout noise, so re-indenting does not count as a change. */
    private static function method(string $class, string $method): string
    {
        $reflection = new ReflectionMethod(self::class($class), $method);
        $lines = file($reflection->getFileName());
        $source = implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        return self::normalize($source);
    }

    private static function block(string $location): string
    {
        [$file, $id] = explode('#', $location, 2);
        $content = file_get_contents(base_path($file));

        if (! preg_match('/\{\{--\s*@clinical\s+'.preg_quote($id, '/').'\s*--\}\}(.*?)\{\{--\s*@endclinical\s*--\}\}/s', $content, $m)) {
            throw new InvalidArgumentException("No text block [$id] in [$file].");
        }

        return self::normalize($m[1]);
    }

    private static function normalize(string $source): string
    {
        return trim(preg_replace('/\s+/', ' ', $source));
    }
}
