<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MenuBuilderController extends Controller
{
    public function edit($roleId)
    {
        $role = Role::findOrFail($roleId);

        $sections = MenuItem::where('scope_type', 'section')->orderBy('sort_order')->get();
        $portalItems = MenuItem::where('scope_type', 'item')->orderBy('sort_order')->get();

        $visibility = DB::table('role_menu_item')->where('role_id', $roleId)->pluck('is_visible', 'menu_item_id');
        $portalAssigned = DB::table('role_menu_item')->where('role_id', $roleId)
            ->whereIn('menu_item_id', $portalItems->pluck('id'))->pluck('menu_item_id')->toArray();

        return view('super-admin.menu-builder.edit', compact('role', 'sections', 'portalItems', 'visibility', 'portalAssigned'));
    }

    public function update(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        // Sections relevant to this role: only ones already seeded for it (built-in roles).
        $applicableSectionIds = DB::table('role_menu_item')
            ->join('menu_items', 'menu_items.id', '=', 'role_menu_item.menu_item_id')
            ->where('role_menu_item.role_id', $roleId)
            ->where('menu_items.scope_type', 'section')
            ->pluck('menu_items.id');

        $visibleSectionIds = collect($request->input('sections', []))->map(fn($v) => (int) $v);

        foreach ($applicableSectionIds as $menuItemId) {
            DB::table('role_menu_item')
                ->where('role_id', $roleId)->where('menu_item_id', $menuItemId)
                ->update(['is_visible' => $visibleSectionIds->contains($menuItemId), 'updated_at' => now()]);
        }

        // Portal items: fully replace the set for this role (custom-role dynamic menu).
        $portalItemIds = MenuItem::where('scope_type', 'item')->pluck('id');
        DB::table('role_menu_item')->where('role_id', $roleId)->whereIn('menu_item_id', $portalItemIds)->delete();

        $selectedPortalIds = collect($request->input('portal_items', []))->map(fn($v) => (int) $v);
        $rows = $selectedPortalIds->values()->map(fn($id, $i) => [
            'role_id' => $roleId, 'menu_item_id' => $id, 'is_visible' => true, 'sort_order' => $i,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($rows->isNotEmpty()) {
            DB::table('role_menu_item')->insert($rows->toArray());
        }

        return redirect()->route('super-admin.menu-builder.edit', $roleId)->with('success', "Menu updated for the \"{$role->name}\" role!");
    }
}
