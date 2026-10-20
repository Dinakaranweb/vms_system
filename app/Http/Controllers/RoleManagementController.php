<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    private const BUILT_IN_ROLES = ['super-admin', 'admin', 'tender-owner', 'vendor', 'committee-member', 'management'];

    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        return view('super-admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'other');
        return view('super-admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:100|unique:roles,name|regex:/^[a-z0-9\-]+$/',
            'permissions'     => 'nullable|array',
            'permissions.*'   => 'exists:permissions,name',
        ], [
            'name.regex' => 'Role name must be lowercase letters, numbers and hyphens only (e.g. "finance-viewer").',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
            $role->syncPermissions($request->permissions ?? []);

            // Every custom role automatically gets the generic Portal menu + a starter dashboard widget,
            // so it is immediately usable without any further code.
            if ($request->boolean('grant_portal_access') && in_array('view all tenders', $request->permissions ?? [])) {
                $portalItems = MenuItem::where('scope_type', 'item')->get();
                foreach ($portalItems as $i => $item) {
                    DB::table('role_menu_item')->insert([
                        'role_id' => $role->id, 'menu_item_id' => $item->id,
                        'is_visible' => true, 'sort_order' => $i,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $defaultWidget = DashboardWidget::where('key', 'tender_stats')->first();
                if ($defaultWidget) {
                    DB::table('role_dashboard_widget')->insert([
                        'role_id' => $role->id, 'widget_id' => $defaultWidget->id,
                        'is_active' => true, 'sort_order' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('super-admin.roles.index')->with('success', 'Role created successfully! Configure its menu and dashboard widgets next.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create role: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::orderBy('name')->get()->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'other');
        $assignedPermissions = $role->permissions->pluck('name')->toArray();
        $isBuiltIn = in_array($role->name, self::BUILT_IN_ROLES);

        return view('super-admin.roles.edit', compact('role', 'permissions', 'assignedPermissions', 'isBuiltIn'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return back()->withErrors(['error' => 'The Super Admin role always has full access and cannot be restricted.']);
        }

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('super-admin.roles.index')->with('success', 'Role permissions updated successfully!');
    }

    public function destroy($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if (in_array($role->name, self::BUILT_IN_ROLES)) {
            return response()->json(['success' => false, 'message' => 'This is a built-in role used by the application and cannot be deleted.'], 403);
        }

        if ($role->users_count > 0) {
            return response()->json(['success' => false, 'message' => "Cannot delete — {$role->users_count} user(s) still have this role. Reassign them first."], 400);
        }

        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role deleted successfully!']);
    }
}
