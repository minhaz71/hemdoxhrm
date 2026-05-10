<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use App\Models\UserMenuOverride;
use App\Services\MenuService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    // ── Admin index ───────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $menus       = $this->menuService->allForAdmin();
        $permissions = app(PermissionService::class)->allGroupedByModule();
        $users       = User::with('roles')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.menus.index', compact('menus', 'permissions', 'users'));
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'         => ['required', 'string', 'max:100'],
            'type'          => ['required', 'in:link,header,divider'],
            'icon'          => ['nullable', 'string', 'max:60'],
            'route'         => ['nullable', 'string', 'max:120'],
            'route_pattern' => ['nullable', 'string', 'max:120'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'roles'         => ['nullable', 'array'],
            'roles.*'       => ['string'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['boolean'],
        ]);

        $data['is_system']   = false;
        $data['sort_order'] ??= Menu::max('sort_order') + 10;

        $menu = Menu::create($data);
        $this->menuService->flush();

        return response()->json([
            'success' => true,
            'message' => "Menu item \"{$menu->label}\" created.",
            'menu'    => $menu,
        ], 201);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $data = $request->validate([
            'label'         => ['required', 'string', 'max:100'],
            'type'          => ['required', 'in:link,header,divider'],
            'icon'          => ['nullable', 'string', 'max:60'],
            'route'         => ['nullable', 'string', 'max:120'],
            'route_pattern' => ['nullable', 'string', 'max:120'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'roles'         => ['nullable', 'array'],
            'roles.*'       => ['string'],
            'is_active'     => ['boolean'],
        ]);

        $menu->update($data);
        $this->menuService->flush();

        return response()->json(['success' => true, 'message' => "Menu updated.", 'menu' => $menu->fresh()]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->is_system) {
            return response()->json(['success' => false, 'message' => 'System menu items cannot be deleted.'], 422);
        }

        $menu->delete();
        $this->menuService->flush();

        return response()->json(['success' => true, 'message' => 'Menu item deleted.']);
    }

    // ── Toggle active ─────────────────────────────────────────────

    public function toggle(Menu $menu): JsonResponse
    {
        $menu->update(['is_active' => ! $menu->is_active]);
        $this->menuService->flush();

        return response()->json([
            'success'   => true,
            'is_active' => $menu->is_active,
            'message'   => $menu->is_active ? "Enabled." : "Disabled.",
        ]);
    }

    // ── Reorder ───────────────────────────────────────────────────

    /**
     * Accept a new ordered array of menu IDs and reassign sort_order values.
     * Body: { ids: [3, 1, 7, 2, …] }
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:menus,id'],
        ]);

        // Build a single bulk CASE WHEN statement instead of one UPDATE per item
        $items = [];
        foreach ($data['ids'] as $position => $id) {
            $items[(int) $id] = ($position + 1) * 10;
        }

        foreach (array_chunk($items, 50, true) as $chunk) {
            $cases = collect($chunk)
                ->map(fn ($ord, $id) => "WHEN {$id} THEN {$ord}")
                ->implode(' ');
            $ids = implode(',', array_keys($chunk));
            \DB::statement("UPDATE menus SET sort_order = CASE id {$cases} END WHERE id IN ({$ids})");
        }

        $this->menuService->flush();

        return response()->json(['success' => true, 'message' => 'Menu order saved.']);
    }

    // ── User overrides ────────────────────────────────────────────

    /**
     * GET /menus/user-overrides?user_id=X
     * Returns all menus with the user's current override state.
     */
    public function userOverrides(Request $request): JsonResponse
    {
        $userId   = $request->validate(['user_id' => ['required', 'exists:users,id']])['user_id'];
        $user     = User::findOrFail($userId);
        $menus    = $this->menuService->allForAdmin();
        $overrides= $this->menuService->getUserOverrides($user);

        $result = $menus->map(fn (Menu $m) => [
            'id'       => $m->id,
            'label'    => $m->label,
            'type'     => $m->type,
            'icon'     => $m->icon,
            'override' => $overrides->has($m->id) ? (bool) $overrides->get($m->id) : null,
        ]);

        return response()->json(['success' => true, 'menus' => $result]);
    }

    /**
     * POST /menus/user-overrides
     * Body: { user_id: X, overrides: { menu_id: true|false|null, … } }
     */
    public function saveUserOverrides(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'       => ['required', 'exists:users,id'],
            'overrides'     => ['required', 'array'],
            'overrides.*'   => ['nullable', 'boolean'],
        ]);

        $user = User::findOrFail($data['user_id']);

        foreach ($data['overrides'] as $menuId => $visible) {
            $menu = Menu::find($menuId);
            if ($menu) {
                $this->menuService->setUserOverride($user, $menu, $visible);
            }
        }

        return response()->json(['success' => true, 'message' => "Menu overrides saved for {$user->name}."]);
    }
}
