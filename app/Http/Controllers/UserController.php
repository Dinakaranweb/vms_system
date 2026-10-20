<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Models\AuditLog;
use App\Models\TenderApplication;
use App\Mail\PasswordResetByAdminMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    private function isActorSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    private function denyIfSuperAdmin(User $target): bool
    {
        return !$this->isActorSuperAdmin() && $target->hasRole('super-admin');
    }

    private function allowedRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->isActorSuperAdmin()
            ? Role::all()
            : Role::where('name', '!=', 'super-admin')->get();
    }

    private function baseQuery()
    {
        $q = User::with('roles', 'vendor');
        if (!$this->isActorSuperAdmin()) {
            $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'super-admin'));
        }
        return $q;
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = $this->baseQuery()->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'total'         => $this->baseQuery()->count(),
            'active'        => $this->baseQuery()->where('is_active', true)->count(),
            'inactive'      => $this->baseQuery()->where('is_active', false)->count(),
            'admins'        => User::role('admin')->count(),
            'vendors'       => User::role('vendor')->count(),
            'tender_owners' => User::role('tender-owner')->count(),
        ];

        if ($this->isActorSuperAdmin()) {
            $stats['super_admins'] = User::role('super-admin')->count();
        }

        $roles = $this->allowedRoles();

        return view('admin.users.index', compact('users', 'stats', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles   = $this->allowedRoles();
        $vendors = Vendor::where('status', 'approved')->get();

        return view('admin.users.create', compact('roles', 'vendors'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        if (!$this->isActorSuperAdmin() && $request->role === 'super-admin') {
            return back()->withErrors(['role' => 'You are not allowed to assign the Super Admin role.'])->withInput();
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8|confirmed',
            'role'      => 'required|exists:roles,name',
            'vendor_id' => 'required_if:role,vendor|nullable|exists:vendors,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'is_active' => $request->is_active ?? true,
                'vendor_id' => $request->role === 'vendor' ? $request->vendor_id : null,
            ]);
            $user->assignRole($request->role);
            DB::commit();

            $route = $this->isActorSuperAdmin() ? 'super-admin.users' : 'admin.users';
            return redirect()->route($route)->with('success', 'User created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with('roles', 'vendor')->findOrFail($id);

        if ($this->denyIfSuperAdmin($user)) {
            abort(403, 'Access denied.');
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);

        if ($this->denyIfSuperAdmin($user)) {
            abort(403, 'Access denied.');
        }

        $roles     = $this->allowedRoles();
        $vendors   = Vendor::where('status', 'approved')->get();
        $userRoles = $user->getRoleNames()->toArray();

        $hasApplications = $user->vendor_id
            ? TenderApplication::where('vendor_id', $user->vendor_id)->exists()
            : false;

        return view('admin.users.edit', compact('user', 'roles', 'vendors', 'userRoles', 'hasApplications'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($this->denyIfSuperAdmin($user)) {
            abort(403, 'Access denied.');
        }

        if (!$this->isActorSuperAdmin() && $request->role === 'super-admin') {
            return back()->withErrors(['role' => 'You are not allowed to assign the Super Admin role.'])->withInput();
        }

        // Block role change away from vendor if they have submitted any tender applications
        $currentRole = $user->getRoleNames()->first();
        if ($currentRole === 'vendor' && $request->role !== 'vendor' && $user->vendor_id) {
            $hasApplications = TenderApplication::where('vendor_id', $user->vendor_id)->exists();
            if ($hasApplications) {
                return back()->withErrors([
                    'role' => 'This vendor has existing tender applications and cannot be reassigned to a different role. Deactivate the account instead.',
                ])->withInput();
            }
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $id,
            'role'      => 'required|exists:roles,name',
            'vendor_id' => 'required_if:role,vendor|nullable|exists:vendors,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $user->update([
                'name'      => $request->name,
                'email'     => $request->email,
                'is_active' => $request->is_active ?? true,
                'vendor_id' => $request->role === 'vendor' ? $request->vendor_id : null,
            ]);

            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8|confirmed']);
                $user->update(['password' => Hash::make($request->password)]);
            }

            $user->syncRoles([$request->role]);
            DB::commit();

            $route = $this->isActorSuperAdmin() ? 'super-admin.users' : 'admin.users';
            return redirect()->route($route)->with('success', 'User updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update user: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
            }

            if ($this->denyIfSuperAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'You cannot delete a Super Admin.'], 403);
            }

            $user->delete();

            return response()->json(['success' => true, 'message' => 'User deleted successfully!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth()->id()) {
                return response()->json(['success' => false, 'message' => 'You cannot change your own status.'], 400);
            }

            if ($this->denyIfSuperAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'You cannot change the status of a Super Admin.'], 403);
            }

            $user->update(['is_active' => !$user->is_active]);

            return response()->json(['success' => true, 'message' => 'User status updated successfully!']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update user status.'], 500);
        }
    }

    /**
     * Super Admin resets any user's password directly (no reset-link flow, no old password needed).
     */
    public function resetPassword(Request $request, $id)
    {
        if (!$this->isActorSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only a Super Admin can reset another user\'s password.'], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::log([
            'action'            => 'password_reset',
            'module'            => 'user',
            'record_id'         => $user->id,
            'record_identifier' => $user->email,
            'description'       => "Password reset for user: {$user->name} ({$user->email}) by " . auth()->user()->name,
        ]);

        try {
            Mail::to($user->email)->send(new PasswordResetByAdminMail($user));
        } catch (\Exception $e) {
            \Log::error('Password reset notification email failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => "Password reset successfully for {$user->name}. They have been notified by email."]);
    }

    /**
     * Bulk action on users.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'     => 'required|in:activate,deactivate,delete',
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $query = User::whereIn('id', $request->user_ids)
                ->where('id', '!=', auth()->id());

            if (!$this->isActorSuperAdmin()) {
                $query->whereDoesntHave('roles', fn($r) => $r->where('name', 'super-admin'));
            }

            switch ($request->action) {
                case 'activate':
                    $query->update(['is_active' => true]);
                    $message = 'Selected user(s) activated successfully!';
                    break;
                case 'deactivate':
                    $query->update(['is_active' => false]);
                    $message = 'Selected user(s) deactivated successfully!';
                    break;
                case 'delete':
                    $query->delete();
                    $message = 'Selected user(s) deleted successfully!';
                    break;
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to process bulk action.'], 500);
        }
    }
}