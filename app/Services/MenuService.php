<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use App\Models\UserMenuOverride;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MenuService
{
    private const CACHE_KEY = 'menus_all';
    private const CACHE_TTL = 3600;

    // ── Sidebar builder ───────────────────────────────────────────

    /**
     * Return the ordered, permission-filtered list of menu items for a user.
     * Headers are automatically suppressed when none of their following links
     * are visible.
     */
    public function forUser(User $user): Collection
    {
        $allMenus  = $this->allCached();
        $overrides = $this->userOverridesCached($user->id);

        // Filter each item
        $visible = $allMenus->filter(
            fn (Menu $m) => $this->itemVisible($m, $user, $overrides)
        )->values();

        // Suppress orphaned headers (header with no visible link after it, before next header)
        return $this->suppressOrphanedHeaders($visible);
    }

    // ── Admin queries ─────────────────────────────────────────────

    /** All menus regardless of is_active — for admin UI. */
    public function allForAdmin(): Collection
    {
        return Menu::orderBy('sort_order')->get();
    }

    // ── Cache ─────────────────────────────────────────────────────

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function flushUserOverrides(int $userId): void
    {
        Cache::forget("menu_overrides_{$userId}");
    }

    private function userOverridesCached(int $userId): Collection
    {
        $data = Cache::remember("menu_overrides_{$userId}", self::CACHE_TTL, function () use ($userId) {
            return UserMenuOverride::where('user_id', $userId)
                ->pluck('visible', 'menu_id')
                ->toArray();
        });

        return collect($data);
    }

    private function allCached(): Collection
    {
        // Cache raw DB rows (stdClass) — not Eloquent objects or cast arrays —
        // so serialization is stable and hydration re-applies all casts cleanly.
        $raw = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DB::table('menus')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();
        });

        return Menu::hydrate($raw);
    }

    // ── Visibility logic ──────────────────────────────────────────

    /**
     * Determine whether a single menu item is visible for the given user.
     *
     * Priority:
     *  1. User-level override (force-show / force-hide)
     *  2. Explicit role restriction (canAny of the item's required roles)
     *  3. Admin role → visible for unrestricted/permission-only items
     *  4. Permission check (canAny of the item's permissions array)
     */
    public function itemVisible(Menu $menu, User $user, Collection $overrides): bool
    {
        // 1. User override
        if ($overrides->has($menu->id)) {
            return (bool) $overrides->get($menu->id);
        }

        // 2. Explicit role restriction (canAny)
        // This keeps employee-only self-service links off admin accounts that
        // do not have a linked employee profile.
        if (! empty($menu->roles)) {
            if (! $user->hasRole($menu->roles)) {
                return false;
            }
        }

        // 3. Admin sees unrestricted / permission-only menus.
        if ($user->hasRole('admin')) {
            return true;
        }

        // 4. Permission gate check (canAny)
        if (! empty($menu->permissions)) {
            $canAny = collect($menu->permissions)
                ->some(fn (string $perm) => Gate::forUser($user)->check($perm));
            if (! $canAny) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove headers that have zero visible link/divider siblings following them
     * (i.e. before the next header or end-of-list).
     */
    private function suppressOrphanedHeaders(Collection $items): Collection
    {
        $result  = collect();
        $pending = null; // current header waiting to see if it has children

        foreach ($items as $item) {
            if ($item->type === 'header') {
                // Push the previous header only if it got at least one child
                // (handled below). Just store the new one as pending.
                $pending = $item;
            } else {
                // Non-header item — materialise any pending header first
                if ($pending !== null) {
                    $result->push($pending);
                    $pending = null;
                }
                $result->push($item);
            }
        }
        // Trailing header with no children → drop it (pending stays null-ed)

        return $result;
    }

    // ── User override helpers ─────────────────────────────────────

    /** Save (or delete) an override for a user+menu. */
    public function setUserOverride(User $user, Menu $menu, ?bool $visible): void
    {
        if ($visible === null) {
            UserMenuOverride::where('user_id', $user->id)
                ->where('menu_id', $menu->id)
                ->delete();
        } else {
            UserMenuOverride::updateOrCreate(
                ['user_id' => $user->id, 'menu_id' => $menu->id],
                ['visible' => $visible]
            );
        }

        $this->flushUserOverrides($user->id);
    }

    /** Get all overrides for a user keyed by menu_id. */
    public function getUserOverrides(User $user): Collection
    {
        return $this->userOverridesCached($user->id);
    }
}
