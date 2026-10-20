<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    use HasDashboardWidgets;

    public function dashboard()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'approved_vendors' => Vendor::where('status', 'approved')->count(),
            'total_admins' => User::role('admin')->count(),
        ];

        $widgetData = $this->getDashboardWidgetData();

        return view('super-admin.dashboard', compact('stats', 'widgetData'));
    }

    // ==================== ADMIN MANAGEMENT ====================

    public function admins()
    {
        $admins = User::role(['admin', 'super-admin'])
            ->with('roles')
            ->orderByDesc('created_at')
            ->get();

        return view('super-admin.admins.index', compact('admins'));
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $admin = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'is_active' => true,
            ]);
            $admin->assignRole('admin');

            AuditLog::log([
                'action'             => 'create',
                'module'             => 'admin',
                'record_id'          => $admin->id,
                'record_identifier'  => $admin->email,
                'description'        => "Admin account created: {$admin->email}",
            ]);

            DB::commit();

            return redirect()->route('super-admin.admins')->with('success', 'Admin account created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Failed to create admin: ' . $e->getMessage()]);
        }
    }

    public function deleteAdmin($id)
    {
        try {
            $admin = User::findOrFail($id);

            if ($admin->id === auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
            }

            if (!$admin->hasRole('admin') || $admin->hasRole('super-admin')) {
                return response()->json(['success' => false, 'message' => 'Only Admin accounts can be removed here.'], 403);
            }

            AuditLog::log([
                'action'            => 'delete',
                'module'            => 'admin',
                'record_id'         => $admin->id,
                'record_identifier' => $admin->email,
                'description'       => "Admin account deleted: {$admin->email}",
            ]);

            $admin->delete();

            return response()->json(['success' => true, 'message' => 'Admin account deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete admin: ' . $e->getMessage()], 500);
        }
    }

    // ==================== SETTINGS ====================

    public function settings()
    {
        $general = [
            'app_name'       => Setting::get('app_name', config('app.name')),
            'support_email'  => Setting::get('support_email', 'tenders.vmrfdu@vmu.edu.in'),
            'support_phone'  => Setting::get('support_phone', ''),
            'maintenance_mode' => Setting::get('maintenance_mode', '0'),
        ];

        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('super-admin.settings', compact('general', 'roles', 'permissions'));
    }

    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'app_name'      => 'required|string|max:255',
            'support_email' => 'required|email',
            'support_phone' => 'nullable|string|max:20',
        ]);

        Setting::setMany([
            'app_name'         => $request->app_name,
            'support_email'    => $request->support_email,
            'support_phone'    => $request->support_phone,
            'maintenance_mode' => $request->boolean('maintenance_mode') ? '1' : '0',
        ]);

        AuditLog::log([
            'action'      => 'update',
            'module'      => 'settings',
            'description' => 'General settings updated',
        ]);

        return back()->with('success', 'General settings updated successfully!');
    }

    public function updateRolePermissions(Request $request)
    {
        $request->validate([
            'role_id'      => 'required|exists:roles,id',
            'permissions'  => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);

        if ($role->name === 'super-admin') {
            return back()->withErrors(['general' => 'Super Admin permissions cannot be modified — it always has full access.']);
        }

        $role->syncPermissions($request->permissions ?? []);

        AuditLog::log([
            'action'            => 'update',
            'module'            => 'settings',
            'record_id'         => $role->id,
            'record_identifier' => $role->name,
            'description'       => "Permissions updated for role: {$role->name}",
        ]);

        return back()->with('success', "Permissions updated for \"{$role->name}\" role!");
    }

    // ==================== AUDIT LOGS ====================

    public function auditLogs(Request $request)
    {
        $query = AuditLog::query()->with('user')->orderByDesc('created_at');

        if ($request->filled('module')) {
            $query->byModule($request->module);
        }
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        $modules = AuditLog::query()->distinct()->pluck('module')->filter()->values();
        $actions = AuditLog::query()->distinct()->pluck('action')->filter()->values();

        return view('super-admin.audit-logs.index', compact('logs', 'modules', 'actions'));
    }

    public function auditLogDetails($id)
    {
        $log = AuditLog::with('user')->findOrFail($id);

        return view('super-admin.audit-logs.show', compact('log'));
    }
}
