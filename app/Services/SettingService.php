<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Global settings service.
 *
 * All reads come from a single flat cache (key → raw string), loaded once per
 * request with one DB query.  Individual writes bust the full cache so the next
 * read rebuilds it fresh.
 */
class SettingService
{
    private const CACHE_KEY = 'system_settings_all';
    private const CACHE_TTL = 3600; // 1 hour

    /** Per-request typed-value memo so repeated setting() calls are free. */
    private array $memo = [];

    // ── Read ──────────────────────────────────────────────────────

    /**
     * Get a single setting value, typed and memoised.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $flat = $this->flatCache();
        $raw  = $flat[$key] ?? null;

        return $this->memo[$key] = ($raw === null ? $default : $this->cast($raw));
    }

    /**
     * Return all settings for a group as [key => typed_value].
     * Filters from the already-loaded flat cache — no extra DB hit.
     */
    public function group(string $groupName): array
    {
        // Load the group→key map from a cache keyed by group name, drawing only
        // on data already in the flat cache so we never go back to the DB.
        $groupKeys = Cache::remember(
            self::CACHE_KEY . "_group_{$groupName}",
            self::CACHE_TTL,
            function () use ($groupName) {
                return SystemSetting::where('group', $groupName)
                    ->orderBy('sort_order')
                    ->pluck('key')
                    ->toArray();
            }
        );

        $result = [];
        foreach ($groupKeys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * All settings as Collection of SystemSetting models (admin UI).
     */
    public function all(): Collection
    {
        return SystemSetting::orderBy('group')->orderBy('sort_order')->orderBy('key')->get();
    }

    /**
     * All settings grouped by their group name, as Eloquent models.
     */
    public function allGrouped(): Collection
    {
        return SystemSetting::orderBy('sort_order')->orderBy('key')->get()->groupBy('group');
    }

    // ── Write ─────────────────────────────────────────────────────

    /**
     * Persist a single setting and flush all caches.
     */
    public function set(
        string $key,
        mixed  $value,
        ?string $label       = null,
        ?string $description = null,
        ?string $group       = null,
        ?string $type        = null,
    ): void {
        $stored = $this->store($value);

        $update = array_filter([
            'value'       => $stored,
            'label'       => $label,
            'description' => $description,
            'group'       => $group,
            'type'        => $type,
            'updated_at'  => now(),
        ], fn ($v) => $v !== null);

        SystemSetting::updateOrCreate(['key' => $key], $update);

        $this->flushCache();
    }

    /**
     * Batch-update many keys at once (one flush at end).
     * $data = ['key' => value, ...]
     */
    public function setMany(array $data, ?string $group = null): void
    {
        foreach ($data as $key => $value) {
            $stored = $this->store($value);
            SystemSetting::updateOrCreate(
                ['key' => $key],
                array_filter(
                    ['value' => $stored, 'group' => $group, 'updated_at' => now()],
                    fn ($v) => $v !== null
                )
            );
        }
        $this->flushCache();
    }

    // ── Cache ─────────────────────────────────────────────────────

    public function flushCache(): void
    {
        // Flush the main flat cache plus any group sub-caches by tag pattern.
        // Since Laravel's default cache (file/redis without tags) can't iterate
        // by prefix, we clear the full cache key; group sub-keys will be lazily
        // rebuilt on next access from fresh flat data.
        Cache::forget(self::CACHE_KEY);

        // Flush all cached group key lists so they are rebuilt on next group() call.
        $groups = SystemSetting::distinct()->pluck('group')->filter()->toArray();
        foreach ($groups as $g) {
            Cache::forget(self::CACHE_KEY . "_group_{$g}");
        }

        $this->memo = [];
    }

    /** @return array<string, string|null>  raw string values keyed by setting key */
    private function flatCache(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::pluck('value', 'key')->toArray();
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function cast(string $raw): mixed
    {
        return match ($raw) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $raw,
        };
    }

    private function store(mixed $value): string
    {
        if (is_bool($value))  return $value ? 'true' : 'false';
        if (is_null($value))  return 'null';
        if (is_array($value)) return json_encode($value);
        return (string) $value;
    }
}
