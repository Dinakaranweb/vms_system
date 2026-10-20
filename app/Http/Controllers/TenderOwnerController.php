<?php

namespace App\Http\Controllers;

use App\Mail\CommitteeAssignedMail;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\User;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class TenderOwnerController extends Controller
{
   use HasDashboardWidgets;

   public function dashboard()
    {
        $user = auth()->user();
        $ownedIds = $user->ownedTenders()->pluck('id');

        $stats = [
            'total'              => $user->ownedTenders()->count(),
            'published'          => $user->ownedTenders()->where('status', 'published')->count(),
            'draft'              => $user->ownedTenders()->where('status', 'draft')->count(),
            'closed'             => $user->ownedTenders()->where('status', 'closed')->count(),
            'total_applications' => TenderApplication::whereIn('tender_id', $ownedIds)->count(),
        ];

        $appStats = [
            'submitted'   => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'shortlisted')->count(),
            'rejected'    => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'rejected')->count(),
            'awarded'     => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'awarded')->count(),
        ];

        // Top 5 tenders by application count
        $topTenders = $user->ownedTenders()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(5)->get();

        // Applications per month (last 6 months)
        $appMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $appMonthly[] = [
                'label' => $m->format('M y'),
                'count' => TenderApplication::whereIn('tender_id', $ownedIds)
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->count(),
            ];
        }

        $onlineCount = $user->ownedTenders()->where('tender_type', 'online')->count();
        $postalCount = $user->ownedTenders()->where('tender_type', 'postal')->count();
        $hybridCount = $user->ownedTenders()->where('tender_type', 'hybrid')->count();

        $tenders = $user->ownedTenders()->withCount('applications')->latest()->paginate(10);
        $widgetData = $this->getDashboardWidgetData();

        return view('tender-owner.dashboard', compact(
            'tenders', 'stats', 'appStats',
            'topTenders', 'appMonthly',
            'onlineCount', 'postalCount', 'hybridCount', 'widgetData'
        ));
    }
    
    public function showCommittee($id)
    {
        $tender = Tender::with('committeeMembers')->findOrFail($id);
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);

        if (!$isAdmin && $tender->owner_id !== auth()->id()) {
            abort(403, 'You do not have permission to manage committee for this tender.');
        }

        $users = User::whereDoesntHave('committeeTenders', function ($q) use ($id) {
            $q->where('tender_id', $id);
        })->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'tender-owner', 'committee-member']);
        })->get();

        $availableRoles = Role::whereIn('name', ['admin', 'tender-owner', 'committee-member'])->get();

        if ($isAdmin) {
            $segment = request()->segment(1); // 'admin' or 'super-admin'
            return view('admin.tenders.committee', compact('tender', 'users', 'availableRoles', 'segment'));
        }

        return view('tender-owner.committee', compact('tender', 'users', 'availableRoles'));
    }
    
    public function addCommitteeMember(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);
        
        DB::beginTransaction();
        
        try {
            $tender->committeeMembers()->attach($request->user_id, [
                'role' => $request->role,
                'designation' => $request->designation,
                'permissions' => json_encode($request->permissions ?? []),
                'added_by' => auth()->id(),
                'is_active' => true,
            ]);

            DB::commit();

            // Email the member
            try {
                $member = User::findOrFail($request->user_id);
                Mail::to($member->email)->send(new CommitteeAssignedMail($member, $tender, $request->role, $request->designation));
            } catch (\Exception $e) {
                \Log::error('Committee assignment email failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Committee member added successfully! A notification email has been sent.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add committee member: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function removeCommitteeMember($tenderId, $userId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        try {
            $tender->committeeMembers()->detach($userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Committee member removed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove committee member'
            ], 500);
        }
    }
    
    /**
     * Create a brand-new user and immediately add them to the tender committee.
     */
    public function createAndAddCommitteeMember(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8',
            'spatie_role'  => 'required|string',
            'role'         => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation'  => 'nullable|string|max:255',
            'permissions'  => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Ensure committee-member role exists
            $spatieRoleName = $request->spatie_role;
            Role::firstOrCreate(['name' => $spatieRoleName, 'guard_name' => 'web']);

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'is_active' => true,
            ]);
            $user->assignRole($spatieRoleName);

            $tender->committeeMembers()->attach($user->id, [
                'role'        => $request->role,
                'designation' => $request->designation,
                'permissions' => json_encode($request->permissions ?? []),
                'added_by'    => auth()->id(),
                'is_active'   => true,
            ]);

            DB::commit();

            // Email the new user
            try {
                Mail::to($user->email)->send(new CommitteeAssignedMail($user, $tender, $request->role, $request->designation));
            } catch (\Exception $e) {
                \Log::error('Committee assignment email failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "User '{$user->name}' created and added to committee! A notification email has been sent.",
                'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateCommitteeMember(Request $request, $tenderId, $userId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'role' => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        
        try {
            $tender->committeeMembers()->updateExistingPivot($userId, [
                'role' => $request->role,
                'designation' => $request->designation,
                'is_active' => $request->is_active ?? true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Committee member updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update committee member'
            ], 500);
        }
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('tender-owner.notifications.index', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}